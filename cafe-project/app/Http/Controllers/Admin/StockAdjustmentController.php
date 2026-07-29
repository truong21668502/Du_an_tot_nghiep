<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Material;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\StockMovement;
use App\Models\ImportReceiptDetail;
use Illuminate\Support\Facades\Log;
use App\Services\MaterialExpiryService;

class StockAdjustmentController extends Controller
{
    public function create(MaterialExpiryService $expiryService)
    {
        $materials = Material::select(
            'id',
            'material_name',
            'base_unit',
            'input_unit',
            'exchange_rate',
            'quantity_in_stock',
            'min_stock',
            'max_stock',
            'shelf_life_after_opening_days'
        )
            ->with('adjustableExpiryDetail')
            ->orderBy('material_name')
            ->get()
            ->each(function ($m) use ($expiryService) {
                $batch = $m->adjustableExpiryDetail;

                $m->expiry_date = $batch?->expiry_date?->format('Y-m-d');
                $m->nearest_expiry_detail_id = $batch?->id;
                $m->expiry_is_past = $batch?->expiry_date
                    ? \Carbon\Carbon::parse($batch->expiry_date)->isPast()
                    : false;

                $m->expiry_breakdown = $batch
                    ? collect($expiryService->breakdown($batch, $m))->map(fn($part) => [
                        'label' => $part['label'],
                        'quantity_input_unit' => round($part['quantity_input_unit'], 2),
                        'expiry_date' => $part['expiry_date']?->format('Y-m-d'),
                        'is_past' => $part['expiry_date']?->isPast() ?? false,
                    ])->values()
                    : [];

                unset($m->adjustableExpiryDetail);
            });

        return Inertia::render('Admin/Warehouse/StockAdjustmentCreate', [
            'materials' => $materials,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                $material = Material::lockForUpdate()->find($validated['material_id']);

                if (!$material) {
                    throw new \Exception('Nguyên liệu không tồn tại.');
                }

                $before = (float) $material->quantity_in_stock;
                $after = (float) $validated['actual_quantity'];
                $change = $after - $before;

                $adjustment = StockAdjustment::create([
                    'material_id' => $material->id,
                    'user_id' => Auth::id(),
                    'reason' => $validated['reason'],
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'change_amount' => $change,
                    'note' => $validated['note'] ?? null,
                ]);

                $updateData = ['quantity_in_stock' => $after];

                if (($validated['min_stock'] ?? null) !== null) {
                    $updateData['min_stock'] = $validated['min_stock'];
                }
                if (($validated['max_stock'] ?? null) !== null) {
                    $updateData['max_stock'] = $validated['max_stock'];
                }

                $material->update($updateData);

                if ($change != 0) {
                    StockMovement::create([
                        'material_id' => $material->id,
                        'movement_type' => 'adjust',
                        'quantity_change' => $change,
                        'reference_type' => 'stock_adjustment',
                        'reference_id' => $adjustment->id,
                        'moved_by' => Auth::id(),
                        'note' => "Điều chỉnh kiểm kê ({$validated['reason']})" . (!empty($validated['note']) ? " — {$validated['note']}" : ''),
                        'moved_at' => now(),
                    ]);
                }

                // --- MỚI: đồng bộ remaining_quantity của các lô để không phá vỡ FIFO ---
                if ($change < 0) {
                    $this->reduceBatchesFefo($material, abs($change));
                } elseif ($change > 0) {
                    $this->increaseSurplusBatch($material->id, $change);
                }

                // Cập nhật hạn sử dụng của lô gần hạn nhất (nếu có gửi lên) — giữ nguyên như cũ
                if (!empty($validated['import_receipt_detail_id']) && !empty($validated['expiry_date'])) {
                    $detail = ImportReceiptDetail::lockForUpdate()
                        ->where('id', $validated['import_receipt_detail_id'])
                        ->where('material_id', $material->id)
                        ->first();

                    if (!$detail) {
                        throw new \Exception('Lô hàng không hợp lệ hoặc không thuộc nguyên liệu này.');
                    }

                    $oldExpiry = $detail->expiry_date;

                    if ((string) $oldExpiry !== (string) $validated['expiry_date']) {
                        $detail->update(['expiry_date' => $validated['expiry_date']]);

                        $adjustment->update([
                            'note' => trim(($adjustment->note ? $adjustment->note . ' | ' : '')
                                . "Hạn sử dụng: {$oldExpiry} → {$validated['expiry_date']}"),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast-error', 'Lỗi khi điều chỉnh tồn kho: ' . $e->getMessage());
        }

        return redirect()->route('admin.kho.dieu-chinh.index')->with('toast-success', 'Điều chỉnh tồn kho thành công!');
    }

    /**
     * Kiểm kê phát hiện HAO HỤT: trừ dần theo FEFO (lô hết hạn sớm nhất trừ trước),
     * tự động đánh dấu opened_at khi bắt đầu dùng dở 1 đơn vị mới trong lô.
     */
    private function reduceBatchesFefo(Material $material, float $qty): void
    {
        $remainingToReduce = $qty;
        $rate = $material->exchange_rate ?: 1;

        $batches = ImportReceiptDetail::query()
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipt_details.material_id', $material->id)
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            // Lô chưa có hạn (expiry_date null) xếp cuối cùng, không ưu tiên trừ trước
            ->orderByRaw('import_receipt_details.expiry_date IS NULL, import_receipt_details.expiry_date ASC')
            ->select('import_receipt_details.*')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToReduce <= 0)
                break;

            $deduct = min((float) $batch->remaining_quantity, $remainingToReduce);

            // Dư = 0 nghĩa là đang đứng ở ranh giới chai nguyên -> deduction này mở 1 đơn vị mới
            $remainderBefore = fmod((float) $batch->remaining_quantity, $rate);
            $extra = [];
            if ($remainderBefore == 0.0 && $batch->remaining_quantity > 0) {
                $extra['opened_at'] = now();
            }

            $batch->decrement('remaining_quantity', $deduct, $extra);
            $remainingToReduce -= $deduct;
        }

        if ($remainingToReduce > 0.001) {
            Log::warning("Kiểm kê nguyên liệu #{$material->id}: còn {$remainingToReduce} chưa trừ được vào lô nào — dữ liệu lô có thể đã lệch từ trước.");
        }
    }

    /**
     * Kiểm kê phát hiện THỪA (change > 0): GIỚI HẠN ĐÃ BIẾT — không thể xác định
     * số thừa này thuộc lô nào (không có phiếu nhập tương ứng), nên gộp tạm vào
     * lô còn hàng mới nhất làm xấp xỉ giá vốn. Nếu cần chính xác tuyệt đối,
     * nên yêu cầu tạo phiếu nhập bổ sung thay vì kiểm kê.
     */
    private function increaseSurplusBatch(int $materialId, float $qty): void
    {
        $batch = ImportReceiptDetail::query()
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipt_details.material_id', $materialId)
            ->where('import_receipts.status', 'active')
            ->orderByDesc('import_receipt_details.created_at')
            ->select('import_receipt_details.*')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            $batch->increment('remaining_quantity', $qty);
        } else {
            Log::warning("Kiểm kê nguyên liệu #{$materialId}: phát hiện thừa {$qty} nhưng chưa có lô nhập nào để gán vào — cân nhắc tạo phiếu nhập bổ sung.");
        }
    }

    public function index(Request $request)
    {
        $query = StockAdjustment::query()
            ->with([
                'material:id,material_name,base_unit,input_unit,exchange_rate',
                'user',
            ]);

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->material_id);
        }
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $adjustments = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $materials = Material::select('id', 'material_name')->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/StockAdjustmentHistory', [
            'adjustments' => $adjustments,
            'materials' => $materials,
            'filters' => $request->only(['material_id', 'reason', 'from_date', 'to_date']),
        ]);
    }
}
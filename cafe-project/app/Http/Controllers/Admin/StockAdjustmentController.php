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

class StockAdjustmentController extends Controller
{
    public function create()
    {
        $materials = Material::select(
            'id',
            'material_name',
            'base_unit',
            'input_unit',
            'exchange_rate',
            'quantity_in_stock',
            'min_stock',
            'max_stock'
        )
            ->with('adjustableExpiryDetail')
            ->orderBy('material_name')
            ->get()
            ->each(function ($m) {
                $m->expiry_date = $m->adjustableExpiryDetail?->expiry_date?->format('Y-m-d');
                $m->nearest_expiry_detail_id = $m->adjustableExpiryDetail?->id;
                $m->expiry_is_past = $m->adjustableExpiryDetail?->expiry_date
                    ? \Carbon\Carbon::parse($m->adjustableExpiryDetail->expiry_date)->isPast()
                    : false;
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
                    $this->reduceBatchesFifo($material->id, abs($change));
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
     * Kiểm kê phát hiện HAO HỤT (change < 0): trừ dần vào các lô theo FIFO,
     * lô cũ nhất còn hàng bị trừ trước — giống hệt logic xuất kho khi pha chế.
     */
    private function reduceBatchesFifo(int $materialId, float $qty): void
    {
        $remainingToReduce = $qty;

        $batches = ImportReceiptDetail::query()
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipt_details.material_id', $materialId)
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->orderBy('import_receipt_details.created_at')
            ->select('import_receipt_details.*')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToReduce <= 0)
                break;

            $deduct = min((float) $batch->remaining_quantity, $remainingToReduce);
            $batch->decrement('remaining_quantity', $deduct);
            $remainingToReduce -= $deduct;
        }

        // Kiểm kê là "nguồn sự thật" (người dùng đếm tay) — không throw exception ở đây
        // (khác với StockService::consume()), chỉ ghi log để biết dữ liệu lô đã lệch trước đó.
        if ($remainingToReduce > 0.001) {
            Log::warning("Kiểm kê nguyên liệu #{$materialId}: còn {$remainingToReduce} chưa trừ được vào lô nào — dữ liệu lô có thể đã lệch từ trước.");
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
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelImportReceiptRequest;
use App\Http\Requests\Admin\QuickStoreMaterialRequest;
use App\Http\Requests\Admin\StoreImportReceiptRequest;
use App\Http\Requests\Admin\UpdateImportReceiptRequest;
use App\Models\ImportReceipt;
use App\Models\ImportReceiptDetail;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\StockMovement;

class ImportReceiptController extends Controller
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
            'max_stock'
        )->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/ImportCreate', [
            'materials' => $materials,
        ]);
    }

    public function store(StoreImportReceiptRequest $request)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $totalCost = collect($data['items'])->sum(
                    fn($item) => $item['quantity'] * $item['unit_price']
                );

                $receipt = ImportReceipt::create([
                    'user_id' => Auth::id(),
                    'supplier_name' => $data['supplier_name'] ?? null,
                    'total_cost' => $totalCost,
                    'note' => $data['note'] ?? null,
                    'status' => 'active',
                ]);

                foreach ($data['items'] as $item) {
                    $material = Material::lockForUpdate()->find($item['material_id']);

                    if (!$material) {
                        throw new \Exception("Nguyên liệu ID {$item['material_id']} không tồn tại.");
                    }

                    $stockChange = $item['quantity'] * $material->exchange_rate;

                    $this->assertWithinMaxStock($material, $stockChange);

                    ImportReceiptDetail::create([
                        'receipt_id' => $receipt->id,
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'stock_change' => $stockChange,
                        'remaining_quantity' => $stockChange, // <-- MỚI: lô này ban đầu còn nguyên
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    $material->increment('quantity_in_stock', $stockChange);

                    StockMovement::create([
                        'material_id' => $material->id,
                        'movement_type' => 'import',
                        'quantity_change' => $stockChange,
                        'reference_type' => 'import_receipt',
                        'reference_id' => $receipt->id,
                        'moved_by' => Auth::id(),
                        'note' => "Nhập kho từ phiếu #{$receipt->id}" . (!empty($data['supplier_name']) ? " — NCC: {$data['supplier_name']}" : ''),
                        'moved_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast-error', 'Lỗi khi lưu phiếu nhập: ' . $e->getMessage());
        }

        return redirect()->route('admin.kho.index')->with('toast-success', 'Tạo phiếu nhập kho thành công!');
    }

    /**
     *  Lịch sử nhập hàng
     */
    public function index(Request $request)
    {
        $query = ImportReceipt::query()
            ->with('user')
            ->withCount('details')
            ->select('id', 'user_id', 'supplier_name', 'total_cost', 'note', 'status', 'created_at');

        if ($request->filled('supplier_name')) {
            $query->where('supplier_name', 'like', '%' . $request->supplier_name . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $receipts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Warehouse/ImportHistory', [
            'receipts' => $receipts,
            'filters' => $request->only(['supplier_name', 'from_date', 'to_date']),
        ]);
    }

    /**
     *  Chi tiết phiếu nhập (dùng cho modal lẫn deep-link)
     */
    public function show(Request $request, ImportReceipt $importReceipt)
    {
        $importReceipt->load([
            'user',
            'cancelledBy',
            'details.material:id,material_name,base_unit,input_unit',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['receipt' => $importReceipt]);
        }

        return Inertia::render('Admin/Warehouse/ImportShow', [
            'receipt' => $importReceipt,
        ]);
    }

    /**
     *  Trang sửa phiếu nhập
     */
    public function edit(ImportReceipt $importReceipt)
    {
        if (!$importReceipt->isActive()) {
            return redirect()->route('admin.kho.nhap.index')
                ->with('toast-error', 'Phiếu này đã bị huỷ, không thể sửa.');
        }

        $importReceipt->load('details');

        $materials = Material::select(
            'id',
            'material_name',
            'base_unit',
            'input_unit',
            'exchange_rate',
            'quantity_in_stock',
            'max_stock'
        )->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/ImportEdit', [
            'receipt' => $importReceipt,
            'materials' => $materials,
        ]);
    }

    /**
     *  Cập nhật phiếu nhập — revert tồn kho cũ, áp tồn kho mới
     */
    public function update(UpdateImportReceiptRequest $request, ImportReceipt $importReceipt)
    {
        if (!$importReceipt->isActive()) {
            return back()->with('toast-error', 'Phiếu này đã bị huỷ, không thể sửa.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $importReceipt) {

                // 1. Kiểm tra: không cho sửa nếu bất kỳ dòng nào đã bị tiêu thụ một phần
                $oldDetails = $importReceipt->details()->lockForUpdate()->get();

                foreach ($oldDetails as $old) {
                    if (bccomp((string) $old->remaining_quantity, (string) $old->stock_change, 2) !== 0) {
                        $material = Material::find($old->material_id);
                        throw new \Exception(
                            "Không thể sửa phiếu: nguyên liệu \"" . ($material->material_name ?? '#' . $old->material_id) . "\" "
                            . "đã bị sử dụng một phần (còn {$old->remaining_quantity}/{$old->stock_change}). "
                            . "Vui lòng tạo phiếu điều chỉnh (kiểm kê) riêng thay vì sửa phiếu này."
                        );
                    }
                }

                // 2. Revert tồn kho theo các dòng CŨ (an toàn vì bước 1 đã đảm bảo chưa tiêu thụ gì)
                foreach ($oldDetails as $old) {
                    $material = Material::lockForUpdate()->find($old->material_id);
                    if ($material) {
                        $material->decrement('quantity_in_stock', $old->stock_change);

                        StockMovement::create([
                            'material_id' => $material->id,
                            'movement_type' => 'import',
                            'quantity_change' => -$old->stock_change,
                            'reference_type' => 'import_receipt',
                            'reference_id' => $importReceipt->id,
                            'moved_by' => Auth::id(),
                            'note' => "Huỷ định lượng cũ do sửa phiếu nhập #{$importReceipt->id}",
                            'moved_at' => now(),
                        ]);
                    }
                }

                // 3. Xoá toàn bộ dòng cũ
                $importReceipt->details()->delete();

                // 4. Tạo lại dòng mới + cộng tồn kho mới (đã check max_stock)
                $totalCost = collect($data['items'])->sum(
                    fn($item) => $item['quantity'] * $item['unit_price']
                );

                foreach ($data['items'] as $item) {
                    $material = Material::lockForUpdate()->find($item['material_id']);

                    if (!$material) {
                        throw new \Exception("Nguyên liệu ID {$item['material_id']} không tồn tại.");
                    }

                    $stockChange = $item['quantity'] * $material->exchange_rate;

                    $this->assertWithinMaxStock($material, $stockChange);

                    ImportReceiptDetail::create([
                        'receipt_id' => $importReceipt->id,
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'stock_change' => $stockChange,
                        'remaining_quantity' => $stockChange, // <-- MỚI
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    $material->increment('quantity_in_stock', $stockChange);

                    StockMovement::create([
                        'material_id' => $material->id,
                        'movement_type' => 'import',
                        'quantity_change' => $stockChange,
                        'reference_type' => 'import_receipt',
                        'reference_id' => $importReceipt->id,
                        'moved_by' => Auth::id(),
                        'note' => "Áp định lượng mới do sửa phiếu nhập #{$importReceipt->id}",
                        'moved_at' => now(),
                    ]);
                }

                $importReceipt->update([
                    'supplier_name' => $data['supplier_name'] ?? null,
                    'note' => $data['note'] ?? null,
                    'total_cost' => $totalCost,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast-error', 'Lỗi khi cập nhật phiếu nhập: ' . $e->getMessage());
        }

        return redirect()->route('admin.kho.nhap.index')->with('toast-success', 'Cập nhật phiếu nhập thành công!');
    }

    /**
     *  Huỷ phiếu nhập (KHÔNG xoá record, chỉ đổi status + revert tồn kho)
     */
    public function destroy(CancelImportReceiptRequest $request, ImportReceipt $importReceipt)
    {
        if (!$importReceipt->isActive()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Phiếu này đã bị huỷ trước đó.'], 422);
            }
            return back()->with('toast-error', 'Phiếu này đã bị huỷ trước đó.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $importReceipt) {
                $details = $importReceipt->details()->lockForUpdate()->get();

                // Kiểm tra: không cho huỷ nếu bất kỳ dòng nào đã bị tiêu thụ một phần
                foreach ($details as $detail) {
                    if (bccomp((string) $detail->remaining_quantity, (string) $detail->stock_change, 2) !== 0) {
                        $material = Material::find($detail->material_id);
                        throw new \Exception(
                            "Không thể huỷ phiếu: nguyên liệu \"" . ($material->material_name ?? '#' . $detail->material_id) . "\" "
                            . "đã bị sử dụng một phần (còn {$detail->remaining_quantity}/{$detail->stock_change}). "
                            . "Vui lòng tạo phiếu điều chỉnh (kiểm kê) riêng thay vì huỷ phiếu này."
                        );
                    }
                }

                foreach ($details as $detail) {
                    $material = Material::lockForUpdate()->find($detail->material_id);
                    if ($material) {
                        $material->decrement('quantity_in_stock', $detail->stock_change);
                        $detail->update(['remaining_quantity' => 0]);

                        StockMovement::create([
                            'material_id' => $material->id,
                            'movement_type' => 'import',
                            'quantity_change' => -$detail->stock_change,
                            'reference_type' => 'import_receipt',
                            'reference_id' => $importReceipt->id,
                            'moved_by' => Auth::id(),
                            'note' => "Huỷ phiếu nhập #{$importReceipt->id}" . (!empty($data['cancel_reason']) ? " — Lý do: {$data['cancel_reason']}" : ''),
                            'moved_at' => now(),
                        ]);
                    }
                }

                $importReceipt->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => Auth::id(),
                    'cancel_reason' => $data['cancel_reason'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Lỗi khi huỷ phiếu nhập: ' . $e->getMessage()], 500);
            }
            return back()->with('toast-error', 'Lỗi khi huỷ phiếu nhập: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã huỷ phiếu nhập và hoàn lại tồn kho.']);
        }

        return redirect()->route('admin.kho.nhap.index')->with('toast-success', 'Đã huỷ phiếu nhập và hoàn lại tồn kho.');
    }

    public function quickStoreMaterial(QuickStoreMaterialRequest $request)
    {
        $validated = $request->validated();
        $initialQty = (float) ($validated['quantity_in_stock'] ?? 0);
        $initialUnitPrice = (float) ($validated['unit_price'] ?? 0); // thêm field vào Request

        $material = DB::transaction(function () use ($validated, $initialQty, $initialUnitPrice) {
            $material = Material::create([
                'material_name' => $validated['material_name'],
                'base_unit' => $validated['base_unit'],
                'input_unit' => $validated['input_unit'],
                'exchange_rate' => $validated['exchange_rate'],
                'quantity_in_stock' => 0,
                'min_stock' => $validated['min_stock'] ?? 0,
                'max_stock' => $validated['max_stock'] ?? null,
                'shelf_life_after_opening_days' => $validated['shelf_life_after_opening_days'] ?? null,
            ]);

            if ($initialQty > 0) {
                $this->assertWithinMaxStock($material, $initialQty);

                $receipt = ImportReceipt::create([
                    'user_id' => Auth::id(),
                    'supplier_name' => null,
                    'total_cost' => $initialUnitPrice * ($initialQty / $material->exchange_rate),
                    'note' => 'Tồn kho ban đầu khi tạo nhanh nguyên liệu',
                    'status' => 'active',
                ]);

                ImportReceiptDetail::create([
                    'receipt_id' => $receipt->id,
                    'material_id' => $material->id,
                    'quantity' => $initialQty / $material->exchange_rate,
                    'unit_price' => $initialUnitPrice,
                    'stock_change' => $initialQty,
                    'remaining_quantity' => $initialQty,
                    'expiry_date' => null,
                ]);

                $material->increment('quantity_in_stock', $initialQty);

                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'import',
                    'quantity_change' => $initialQty,
                    'reference_type' => 'import_receipt',
                    'reference_id' => $receipt->id,
                    'moved_by' => Auth::id(),
                    'note' => 'Tồn kho ban đầu (tạo nhanh nguyên liệu)',
                    'moved_at' => now(),
                ]);
            }

            return $material;
        });

        return response()->json([
            'material' => $material->only([
                'id',
                'material_name',
                'base_unit',
                'input_unit',
                'exchange_rate',
                'quantity_in_stock',
                'shelf_life_after_opening_days',
            ]),
        ]);
    }

    private function assertWithinMaxStock(Material $material, float $stockChange): void
    {
        if ($material->max_stock === null || (float) $material->max_stock <= 0) {
            return; // Không giới hạn nếu chưa cấu hình max_stock
        }

        $projected = (float) $material->quantity_in_stock + $stockChange;

        if ($projected > (float) $material->max_stock) {
            $maxQtyInInputUnit = floor(
                (((float) $material->max_stock - (float) $material->quantity_in_stock) / $material->exchange_rate) * 100
            ) / 100;
            $maxQtyInInputUnit = max(0, $maxQtyInInputUnit);

            throw new \Exception(
                "Nguyên liệu \"{$material->material_name}\" vượt quá sức chứa kho tối đa ({$material->max_stock} {$material->base_unit}). "
                . "Chỉ có thể nhập thêm tối đa {$maxQtyInInputUnit} {$material->input_unit}."
            );
        }
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportReceipt;
use App\Models\ImportReceiptDetail;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
            'quantity_in_stock'
        )->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/ImportCreate', [
            'materials' => $materials,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id|distinct',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $totalCost = collect($request->items)->sum(
                    fn($item) => $item['quantity'] * $item['unit_price']
                );

                $receipt = ImportReceipt::create([
                    'user_id' => Auth::id(),
                    'supplier_name' => $request->supplier_name,
                    'total_cost' => $totalCost,
                    'note' => $request->note,
                    'status' => 'active',
                ]);

                foreach ($request->items as $item) {
                    $material = Material::lockForUpdate()->find($item['material_id']);

                    if (!$material) {
                        throw new \Exception("Nguyên liệu ID {$item['material_id']} không tồn tại.");
                    }

                    $stockChange = $item['quantity'] * $material->exchange_rate;

                    ImportReceiptDetail::create([
                        'receipt_id' => $receipt->id,
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'stock_change' => $stockChange,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    $material->increment('quantity_in_stock', $stockChange);
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
            'quantity_in_stock'
        )->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/ImportEdit', [
            'receipt' => $importReceipt,
            'materials' => $materials,
        ]);
    }

    /**
     *  Cập nhật phiếu nhập — revert tồn kho cũ, áp tồn kho mới
     */
    public function update(Request $request, ImportReceipt $importReceipt)
    {
        if (!$importReceipt->isActive()) {
            return back()->with('toast-error', 'Phiếu này đã bị huỷ, không thể sửa.');
        }

        $request->validate([
            'supplier_name' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id|distinct',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($request, $importReceipt) {

                // 1. Revert tồn kho theo các dòng CŨ (dùng stock_change đã lưu, không phụ thuộc exchange_rate hiện tại)
                $oldDetails = $importReceipt->details()->lockForUpdate()->get();

                foreach ($oldDetails as $old) {
                    $material = Material::lockForUpdate()->find($old->material_id);
                    if ($material) {
                        $material->decrement('quantity_in_stock', $old->stock_change);
                    }
                }

                // 2. Xoá toàn bộ dòng cũ
                $importReceipt->details()->delete();

                // 3. Tạo lại dòng mới + cộng tồn kho mới
                $totalCost = collect($request->items)->sum(
                    fn($item) => $item['quantity'] * $item['unit_price']
                );

                foreach ($request->items as $item) {
                    $material = Material::lockForUpdate()->find($item['material_id']);

                    if (!$material) {
                        throw new \Exception("Nguyên liệu ID {$item['material_id']} không tồn tại.");
                    }

                    $stockChange = $item['quantity'] * $material->exchange_rate;

                    ImportReceiptDetail::create([
                        'receipt_id' => $importReceipt->id,
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'stock_change' => $stockChange,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);

                    $material->increment('quantity_in_stock', $stockChange);
                }

                $importReceipt->update([
                    'supplier_name' => $request->supplier_name,
                    'note' => $request->note,
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
    public function destroy(Request $request, ImportReceipt $importReceipt)
    {
        if (!$importReceipt->isActive()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Phiếu này đã bị huỷ trước đó.'], 422);
            }
            return back()->with('toast-error', 'Phiếu này đã bị huỷ trước đó.');
        }

        $request->validate([
            'cancel_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $importReceipt) {
                $details = $importReceipt->details()->lockForUpdate()->get();

                foreach ($details as $detail) {
                    $material = Material::lockForUpdate()->find($detail->material_id);
                    if ($material) {
                        $material->decrement('quantity_in_stock', $detail->stock_change);
                    }
                }

                $importReceipt->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => Auth::id(),
                    'cancel_reason' => $request->cancel_reason,
                ]);
            });
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Lỗi khi huỷ phiếu nhập: ' . $e->getMessage()], 500);
            }
            return back()->with('toast-error', 'Lỗi khi huỷ phiếu nhập: ' . $e->getMessage());
        }

        // QUAN TRỌNG: trả JSON thay vì redirect khi gọi qua axios/AJAX
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã huỷ phiếu nhập và hoàn lại tồn kho.']);
        }

        return redirect()->route('admin.kho.nhap.index')->with('toast-success', 'Đã huỷ phiếu nhập và hoàn lại tồn kho.');
    }

    public function quickStoreMaterial(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255|unique:materials,material_name',
            'base_unit' => 'required|string|max:50',
            'input_unit' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'quantity_in_stock' => 'nullable|numeric|min:0',
        ]);

        $material = Material::create([
            'material_name' => $validated['material_name'],
            'base_unit' => $validated['base_unit'],
            'input_unit' => $validated['input_unit'],
            'exchange_rate' => $validated['exchange_rate'],
            'quantity_in_stock' => $validated['quantity_in_stock'] ?? 0,
        ]);

        return response()->json([
            'material' => $material->only([
                'id',
                'material_name',
                'base_unit',
                'input_unit',
                'exchange_rate',
                'quantity_in_stock',
            ]),
        ]);
    }
}
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
        )
            ->orderBy('material_name')
            ->get();

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
                ]);

                foreach ($request->items as $item) {
                    $material = Material::find($item['material_id']);

                    if (!$material) {
                        throw new \Exception("Nguyên liệu ID {$item['material_id']} không tồn tại.");
                    }

                    ImportReceiptDetail::create([
                        'receipt_id' => $receipt->id,
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);

                    $material->increment(
                        'quantity_in_stock',
                        $item['quantity'] * $material->exchange_rate
                    );
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Lỗi khi lưu phiếu nhập: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.kho.index')
            ->with('success', 'Tạo phiếu nhập kho thành công!');
    }

    /**
     * 📜 Lịch sử nhập hàng — danh sách các phiếu nhập, có lọc/tìm kiếm
     */
    public function index(Request $request)
    {
        $query = ImportReceipt::query()
            ->with('user')
            ->withCount('details')
            ->select('id', 'user_id', 'supplier_name', 'total_cost', 'note', 'created_at');

        // Tìm theo tên nhà cung cấp
        if ($request->filled('supplier_name')) {
            $query->where('supplier_name', 'like', '%' . $request->supplier_name . '%');
        }

        // Lọc theo khoảng ngày
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $receipts = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Warehouse/ImportHistory', [
            'receipts' => $receipts,
            'filters' => $request->only(['supplier_name', 'from_date', 'to_date']),
        ]);
    }

    /**
     * 🔍 Chi tiết 1 phiếu nhập — xem đầy đủ các dòng nguyên liệu
     */
    public function show(Request $request, ImportReceipt $importReceipt)
    {
        $importReceipt->load([
            'user',
            'details.material:id,material_name,base_unit,input_unit',
        ]);

        // Nếu gọi từ axios (modal) → trả JSON
        if ($request->wantsJson()) {
            return response()->json([
                'receipt' => $importReceipt,
            ]);
        }

        // Nếu gọi trực tiếp qua URL (deep link) → vẫn render trang đầy đủ
        return Inertia::render('Admin/Warehouse/ImportShow', [
            'receipt' => $importReceipt,
        ]);
    }

    public function quickStoreMaterial(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255|unique:materials,material_name',
            'base_unit' => 'required|string|max:50',
            'input_unit' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'quantity_in_stock' => 'nullable|numeric|min:0',
        ], [
            'material_name.unique' => 'Nguyên liệu này đã tồn tại trong hệ thống.',
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
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
        )->orderBy('material_name')->get();

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

                $before = $material->quantity_in_stock;
                $after = $validated['actual_quantity'];
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
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast-error', 'Lỗi khi điều chỉnh tồn kho: ' . $e->getMessage());
        }

        return redirect()->route('admin.kho.dieu-chinh.index')->with('toast-success', 'Điều chỉnh tồn kho thành công!');
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
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            'quantity_in_stock'
        )->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/StockAdjustmentCreate', [
            'materials' => $materials,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'actual_quantity' => 'required|numeric|min:0',
            'reason' => 'required|in:kiem_ke,that_thoat,het_han,hu_hong,khac',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $material = Material::lockForUpdate()->find($validated['material_id']);

                if (!$material) {
                    throw new \Exception('Nguyên liệu không tồn tại.');
                }

                $before = $material->quantity_in_stock;
                $after = $validated['actual_quantity'];
                $change = $after - $before;

                StockAdjustment::create([
                    'material_id' => $material->id,
                    'user_id' => Auth::id(),
                    'reason' => $validated['reason'],
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'change_amount' => $change,
                    'note' => $validated['note'] ?? null,
                ]);

                $material->update(['quantity_in_stock' => $after]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Lỗi khi điều chỉnh tồn kho: ' . $e->getMessage());
        }

        return redirect()->route('admin.kho.dieu-chinh.index')->with('success', 'Điều chỉnh tồn kho thành công!');
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
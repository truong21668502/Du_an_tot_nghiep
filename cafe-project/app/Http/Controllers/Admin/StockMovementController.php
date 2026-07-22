<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::query()
            ->with([
                'material:id,material_name,base_unit,input_unit',
                'movedBy:id,full_name',
            ]);

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->material_id);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('moved_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('moved_at', '<=', $request->to_date);
        }

        $movements = $query->orderByDesc('moved_at')->paginate(20)->withQueryString();

        $materials = Material::select('id', 'material_name')->orderBy('material_name')->get();

        return Inertia::render('Admin/Warehouse/StockMovementHistory', [
            'movements' => $movements,
            'materials' => $materials,
            'filters' => $request->only(['material_id', 'movement_type', 'from_date', 'to_date']),
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportReceipt;
use App\Models\ImportReceiptDetail;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

            dump('Receipt created', $receipt->id);

            foreach ($request->items as $item) {

                dump('Creating detail', $item);

                $detail = ImportReceiptDetail::create([
                    'receipt_id' => $receipt->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                dump('Detail created', $detail->id);
            }

            dd('DONE');
        });

        return redirect()
            ->route('admin.kho.index')
            ->with('success', 'Tạo phiếu nhập kho thành công!');
    }
}
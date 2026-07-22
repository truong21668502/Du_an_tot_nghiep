<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;

class MaterialController extends Controller
{
    public function index()
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
            // Không select materials.price / materials.supplier nữa — lấy từ phiếu nhập
        )
            ->with([
                'nearestExpiryDetail' => function ($query) {
                    $query->select([
                        'import_receipt_details.id',
                        'import_receipt_details.material_id',
                        'import_receipt_details.expiry_date',
                        'import_receipt_details.receipt_id',
                    ]);
                },
                'latestImportDetail', // đã tự select đủ cột + supplier_name ở trong quan hệ
            ])
            ->orderBy('material_name')
            ->get()
            ->each(function ($m) {
                $m->expiry_date = $m->nearestExpiryDetail?->expiry_date;
                $m->price = $m->latestImportDetail?->unit_price;
                $m->supplier = $m->latestImportDetail?->supplier_name;

                unset($m->nearestExpiryDetail, $m->latestImportDetail);
            });

        $lowStockAlerts = $materials->filter(
            fn($m) =>
            (float) $m->quantity_in_stock <= 0 ||
            ($m->min_stock > 0 && (float) $m->quantity_in_stock <= (float) $m->min_stock)
        )->values();

        return Inertia::render('Admin/Warehouse/Index', [
            'materials' => $materials,
            'lowStockAlerts' => $lowStockAlerts,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function index()
    {
        $avgPrices = DB::table('import_receipt_details')
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->groupBy('import_receipt_details.material_id')
            ->select(
                'import_receipt_details.material_id',
                DB::raw('SUM(import_receipt_details.remaining_quantity * import_receipt_details.unit_price) / SUM(import_receipt_details.remaining_quantity) as avg_unit_price')
            )
            ->pluck('avg_unit_price', 'material_id');

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
            ->with([
                'nearestExpiryDetail' => function ($query) {
                    $query->select([
                        'import_receipt_details.id',
                        'import_receipt_details.material_id',
                        'import_receipt_details.expiry_date',
                        'import_receipt_details.receipt_id',
                    ]);
                },
                'oldestActiveDetail', // <-- thay latestImportDetail cho mục đích hiển thị supplier
                'latestImportDetail',  // giữ lại CHỈ để fallback khi hết hàng hoàn toàn
            ])
            ->orderBy('material_name')
            ->get()
            ->each(function ($m) use ($avgPrices) {
                $m->expiry_date = $m->nearestExpiryDetail?->expiry_date?->format('Y-m-d');
                $m->nearest_expiry_detail_id = $m->nearestExpiryDetail?->id;

                $m->price = $avgPrices[$m->id] ?? $m->latestImportDetail?->unit_price;

                // Ưu tiên NCC của lô cũ nhất còn tồn (đúng hàng đang dùng);
                // fallback về phiếu gần nhất nếu không còn lô nào tồn (vd. vừa hết hàng).
                $m->supplier = $m->oldestActiveDetail?->supplier_name ?? $m->latestImportDetail?->supplier_name;

                unset($m->nearestExpiryDetail, $m->oldestActiveDetail, $m->latestImportDetail);
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

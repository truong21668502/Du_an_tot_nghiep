<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;
use App\Services\MaterialExpiryService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\UpdateMaterialRequest;


class MaterialController extends Controller
{
    public function index(MaterialExpiryService $expiryService)
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
            'max_stock',
            'shelf_life_after_opening_days'
        )
            ->with([
                'oldestActiveDetail',
                'latestImportDetail',
            ])
            ->orderBy('material_name')
            ->get()
            ->each(function ($m) use ($avgPrices, $expiryService) {
                // Hạn hiển thị: hạn sớm nhất trong tất cả các lô, có tính cả
                // phần đang mở dở (opened_at + shelf_life_after_opening_days)
                $m->expiry_date = $expiryService->nearestExpiry($m->id)?->format('Y-m-d');

                // Breakdown chi tiết từng phần (nguyên/đang mở) của tất cả lô còn hàng,
                // sắp theo hạn tăng dần — dùng để hiện tách dòng ở cột Hạn SD khi cần.
                $m->expiry_breakdown = collect(
                    $m->importReceiptDetails()
                        ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
                        ->where('import_receipts.status', 'active')
                        ->where('import_receipt_details.remaining_quantity', '>', 0)
                        ->select('import_receipt_details.*')
                        ->get()
                        ->flatMap(fn($batch) => $expiryService->breakdown($batch, $m))
                )
                    ->sortBy('expiry_date')
                    ->map(fn($part) => [
                        'label' => $part['label'],
                        'quantity_input_unit' => round($part['quantity_input_unit'], 2),
                        'expiry_date' => $part['expiry_date']?->format('Y-m-d'),
                    ])
                    ->values();

                $m->price = $avgPrices[$m->id] ?? $m->latestImportDetail?->unit_price;

                // Ưu tiên NCC của lô đang tiêu thụ theo FEFO;
                // fallback về phiếu gần nhất nếu không còn lô nào tồn (vd. vừa hết hàng).
                $m->supplier = $m->oldestActiveDetail?->supplier_name ?? $m->latestImportDetail?->supplier_name;

                unset($m->oldestActiveDetail, $m->latestImportDetail);
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

    public function edit(Material $material)
    {
        // Cảnh báo: đổi exchange_rate khi đã có lô nhập sẽ làm sai lệch
        // quy đổi của các lô cũ (remaining_quantity đang lưu theo base_unit,
        // không tự tính lại theo tỉ lệ mới) — chỉ nên sửa khi chưa từng nhập kho.
        $hasBatches = $material->importReceiptDetails()->exists();

        return Inertia::render('Admin/Warehouse/MaterialEdit', [
            'material' => $material->only([
                'id',
                'material_name',
                'base_unit',
                'input_unit',
                'exchange_rate',
                'min_stock',
                'max_stock',
                'shelf_life_after_opening_days',
            ]),
            'hasBatches' => $hasBatches,
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material)
    {
        $material->update($request->validated());

        return redirect()->route('admin.kho.index')->with('toast-success', 'Cập nhật nguyên liệu thành công!');
    }
}
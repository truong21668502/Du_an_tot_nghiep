<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class MaterialCostService
{
    /**
     * Tính giá vốn bình quân gia quyền của 1 nguyên liệu, quy về đơn giá / 1 base_unit.
     * Chỉ tính trên các phiếu nhập còn hiệu lực (không bị hủy).
     */
    public function averageCostPerBaseUnit(int $materialId): array
    {
        $result = DB::table('import_receipt_details as ird')
            ->join('import_receipts as ir', 'ir.id', '=', 'ird.receipt_id')
            ->join('materials as m', 'm.id', '=', 'ird.material_id')
            ->where('ird.material_id', $materialId)
            ->where('ir.status', 'active')
            ->selectRaw('
            SUM((ird.unit_price / m.exchange_rate) * ird.stock_change) as weighted_cost,
            SUM(ird.stock_change) as total_stock_change
        ')
            ->first();

        if (!$result || !$result->total_stock_change || $result->total_stock_change == 0) {
            return ['cost' => 0.0, 'has_data' => false];
        }

        return [
            'cost' => round($result->weighted_cost / $result->total_stock_change, 4),
            'has_data' => true,
        ];
    }

    public function calculateVariantCost(ProductVariant $variant): array
    {
        $variant->loadMissing('recipes.material:id,material_name,base_unit');

        $breakdown = [];
        $totalCost = 0;
        $hasMissingCostData = false;

        foreach ($variant->recipes as $recipe) {
            $costInfo = $this->averageCostPerBaseUnit($recipe->material_id);
            $lineCost = $costInfo['cost'] * $recipe->quantity_needed;
            $totalCost += $lineCost;

            if (!$costInfo['has_data']) {
                $hasMissingCostData = true;
            }

            $breakdown[] = [
                'material_id' => $recipe->material_id,
                'material_name' => $recipe->material->material_name ?? null,
                'quantity_needed' => (float) $recipe->quantity_needed,
                'unit' => $recipe->material->base_unit ?? null,
                'cost_per_unit' => $costInfo['cost'],
                'line_cost' => round($lineCost, 2),
                'has_cost_data' => $costInfo['has_data'], // ⭐ mới
            ];
        }

        return [
            'total_cost' => round($totalCost, 2),
            'breakdown' => $breakdown,
            'has_missing_cost_data' => $hasMissingCostData, // ⭐ mới
        ];
    }
}
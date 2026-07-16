<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class MaterialCostService
{
    /**
     * Tính giá vốn bình quân gia quyền DI ĐỘNG (moving weighted average).
     * Chỉ xét các lần nhập gần nhất, cộng dồn vừa đủ để khớp với
     * quantity_in_stock hiện tại — không tính các lô cũ đã tiêu thụ hết.
     */
    public function averageCostPerBaseUnit(int $materialId): array
    {
        $material = Material::find($materialId);

        if (!$material || $material->quantity_in_stock <= 0) {
            return ['cost' => 0.0, 'has_data' => false];
        }

        $currentStock = (float) $material->quantity_in_stock;

        // Lấy toàn bộ lịch sử nhập còn hiệu lực, MỚI NHẤT trước
        $imports = DB::table('import_receipt_details as ird')
            ->join('import_receipts as ir', 'ir.id', '=', 'ird.receipt_id')
            ->where('ird.material_id', $materialId)
            ->where('ir.status', 'active')
            ->orderByDesc('ir.created_at')
            ->limit(50)
            ->select('ird.unit_price', 'ird.stock_change')
            ->get();

        if ($imports->isEmpty()) {
            return ['cost' => 0.0, 'has_data' => false];
        }

        $remaining = $currentStock;
        $weightedCost = 0.0;
        $totalCounted = 0.0;

        foreach ($imports as $import) {
            if ($remaining <= 0) {
                break;
            }

            $stockChange = (float) $import->stock_change;
            if ($stockChange <= 0) {
                continue; // bỏ qua dòng nhập lỗi/âm nếu có
            }

            $takeQty = min($remaining, $stockChange);
            $pricePerBaseUnit = $import->unit_price / $material->exchange_rate;

            $weightedCost += $pricePerBaseUnit * $takeQty;
            $totalCounted += $takeQty;
            $remaining -= $takeQty;
        }

        if ($totalCounted <= 0) {
            return ['cost' => 0.0, 'has_data' => false];
        }

        // Nếu lịch sử nhập KHÔNG đủ để phủ hết tồn kho hiện tại
        // (thường do dữ liệu nhập bị thiếu/nhập tay chỉnh tay quantity_in_stock),
        // vẫn trả về giá bình quân của phần đã tính được, kèm cờ cảnh báo.
        $isPartialData = $remaining > 0.01;

        return [
            'cost' => round($weightedCost / $totalCounted, 4),
            'has_data' => true,
            'is_partial_data' => $isPartialData,
        ];
    }

    /**
     * Giá vốn của toàn bộ 1 ly/sản phẩm (theo variant), dựa trên công thức
     * + giá vốn bình quân di động của từng nguyên liệu.
     */
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
                'has_cost_data' => $costInfo['has_data'],
                'is_partial_data' => $costInfo['is_partial_data'] ?? false,
            ];
        }

        return [
            'total_cost' => round($totalCost, 2),
            'breakdown' => $breakdown,
            'has_missing_cost_data' => $hasMissingCostData,
        ];
    }
}
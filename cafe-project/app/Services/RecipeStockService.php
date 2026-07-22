<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StockMovement;

class RecipeStockService
{
    /**
     * Gộp nhu cầu nguyên liệu của toàn bộ đơn hàng theo material_id
     * (1 đơn có thể có nhiều dòng cùng dùng chung 1 nguyên liệu)
     */
    protected function calculateRequiredMaterials(Order $order): array
    {
        $order->loadMissing('details.variant.recipes');

        $required = [];
        foreach ($order->details as $detail) {
            if (!$detail->variant) {
                continue;
            }
            foreach ($detail->variant->recipes as $recipe) {
                $required[$recipe->material_id] = ($required[$recipe->material_id] ?? 0)
                    + ($recipe->quantity_needed * $detail->quantity);
            }
        }

        return $required;
    }

    /**
     * Kiểm tra tồn kho có đủ để hoàn thành đơn hay không.
     * Trả về mảng nguyên liệu thiếu (rỗng = đủ hàng).
     */
    public function checkAvailability(Order $order): array
    {
        $required = $this->calculateRequiredMaterials($order);
        if (empty($required)) {
            return [];
        }

        $materials = Material::whereIn('id', array_keys($required))->get()->keyBy('id');
        $insufficient = [];

        foreach ($required as $materialId => $neededQty) {
            $material = $materials->get($materialId);
            if (!$material || $material->quantity_in_stock < $neededQty) {
                $insufficient[] = [
                    'material_id' => $materialId,
                    'material_name' => $material?->material_name ?? "Nguyên liệu #{$materialId}",
                    'needed' => round($neededQty, 2),
                    'available' => (float) ($material?->quantity_in_stock ?? 0),
                    'unit' => $material?->base_unit,
                ];
            }
        }

        return $insufficient;
    }

    /**
     * Trừ kho thực tế sau khi đơn được xác nhận hoàn thành.
     */
    public function deductStock(Order $order): void
    {
        $required = $this->calculateRequiredMaterials($order);
        if (empty($required)) {
            return;
        }

        DB::transaction(function () use ($required, $order) {
            foreach ($required as $materialId => $qty) {
                Material::where('id', $materialId)->decrement('quantity_in_stock', $qty);

                StockMovement::create([
                    'material_id' => $materialId,
                    'movement_type' => 'export',
                    'quantity_change' => -$qty,   // âm = xuất
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'moved_by' => Auth::id(),
                    'note' => "Trừ kho khi hoàn thành đơn #{$order->id}",
                    'moved_at' => now(),
                ]);
            }
        });
    }

    /**
     * Danh sách variant trong đơn chưa có công thức nào (không trừ được kho, cần cảnh báo).
     */
    public function findVariantsWithoutRecipe(Order $order): array
    {
        $order->loadMissing('details.variant.recipes', 'details.product:id,product_name');

        $missing = [];
        foreach ($order->details as $detail) {
            if ($detail->variant && $detail->variant->recipes->isEmpty()) {
                $missing[] = [
                    'variant_id' => $detail->variant->id,
                    'product_name' => $detail->product->product_name ?? null,
                    'size' => $detail->variant->size,
                ];
            }
        }

        return $missing;
    }
}
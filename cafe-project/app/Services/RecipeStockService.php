<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class RecipeStockService
{
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
     * Trừ theo FIFO (lô nhập cũ nhất còn hàng bị trừ trước) qua StockService.
     */
    public function deductStock(Order $order): void
    {
        $required = $this->calculateRequiredMaterials($order);
        if (empty($required)) {
            return;
        }

        DB::transaction(function () use ($required, $order) {
            $stockService = app(StockService::class);

            foreach ($required as $materialId => $qty) {
                $stockService->consume(
                    materialId: $materialId,
                    qtyBase: $qty,
                    referenceType: 'order',
                    referenceId: $order->id,
                    note: "Trừ kho khi hoàn thành đơn #{$order->id}"
                );
            }
        });
    }

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
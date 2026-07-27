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

        // Sắp xếp theo material_id tăng dần để tránh deadlock
        // khi 2 đơn tiêu thụ chung nguyên liệu theo thứ tự khác nhau
        ksort($required);

        return $required;
    }

    /**
     * Chỉ đọc, dùng để hiển thị cảnh báo trước khi bấm "Hoàn thành"
     * (KHÔNG dùng để quyết định cho phép trừ kho — dễ bị race condition,
     * chỉ dùng cho mục đích hiển thị UI/preview).
     */
    public function checkAvailability(Order $order): array
    {
        $required = $this->calculateRequiredMaterials($order);
        if (empty($required)) {
            return [];
        }

        $materials = Material::whereIn('id', array_keys($required))->get()->keyBy('id');

        return $this->buildInsufficientList($required, $materials);
    }

    /**
     * Kiểm tra tồn kho VÀ trừ kho trong CÙNG 1 transaction có lock.
     * Đây là hàm duy nhất nên được gọi khi đơn chuyển sang COMPLETED.
     *
     * @return array Danh sách nguyên liệu thiếu (rỗng nếu đủ và đã trừ thành công)
     */
    public function checkAndDeduct(Order $order): array
    {
        $required = $this->calculateRequiredMaterials($order);
        if (empty($required)) {
            return [];
        }

        return DB::transaction(function () use ($required, $order) {
            // Lock ngay trong transaction này — không tách rời check và deduct
            $materials = Material::whereIn('id', array_keys($required))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $insufficient = $this->buildInsufficientList($required, $materials);

            if (!empty($insufficient)) {
                // Không có thao tác ghi nào xảy ra, transaction commit rỗng — an toàn
                return $insufficient;
            }

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

            return [];
        });
    }

    protected function buildInsufficientList(array $required, $materials): array
    {
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
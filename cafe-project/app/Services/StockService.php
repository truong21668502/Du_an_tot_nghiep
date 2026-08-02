<?php

namespace App\Services;

use App\Models\ImportReceiptDetail;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function consume(int $materialId, float $qtyBase, string $referenceType, int $referenceId, ?string $note = null): void
    {
        DB::transaction(function () use ($materialId, $qtyBase, $referenceType, $referenceId, $note) {
            $material = Material::lockForUpdate()->find($materialId);

            if (!$material) {
                throw new \Exception("Không tìm thấy nguyên liệu ID {$materialId}.");
            }

            if ((float) $material->quantity_in_stock < $qtyBase) {
                throw new \Exception("Nguyên liệu \"{$material->material_name}\" không đủ tồn kho.");
            }

            $remainingToConsume = $qtyBase;

            $batches = ImportReceiptDetail::query()
                ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
                ->where('import_receipt_details.material_id', $materialId)
                ->where('import_receipts.status', 'active')
                ->where('import_receipt_details.remaining_quantity', '>', 0)
                ->where('import_receipt_details.expiry_date', '>=', now()->toDateString())
                ->orderByRaw('import_receipt_details.expiry_date IS NULL, import_receipt_details.expiry_date ASC')
                ->select('import_receipt_details.*')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0)
                    break;

                $deduct = min((float) $batch->remaining_quantity, $remainingToConsume);

                $batch->decrement('remaining_quantity', $deduct);
                $remainingToConsume -= $deduct;
            }

            if ($remainingToConsume > 0.001) {
                throw new \Exception(
                    "Dữ liệu lô hàng của \"{$material->material_name}\" không khớp với tồn kho tổng — "
                    . "thiếu {$remainingToConsume} " . $material->base_unit . " chưa được gán vào lô nào."
                );
            }

            $material->decrement('quantity_in_stock', $qtyBase);

            StockMovement::create([
                'material_id' => $materialId,
                'movement_type' => 'export',
                'quantity_change' => -$qtyBase,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'moved_by' => Auth::id(),
                'note' => $note,
                'moved_at' => now(),
            ]);
        });
    }
}
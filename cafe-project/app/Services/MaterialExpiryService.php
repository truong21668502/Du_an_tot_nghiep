<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ImportReceiptDetail;
use Carbon\Carbon;

class MaterialExpiryService
{
    public function breakdown(ImportReceiptDetail $batch, Material $material): array
    {
        $remaining = (float) $batch->remaining_quantity;
        if ($remaining <= 0)
            return [];

        if (!$material->shelf_life_after_opening_days) {
            return [
                [
                    'label' => null,
                    'quantity_base_unit' => $remaining,
                    'quantity_input_unit' => $remaining / ($material->exchange_rate ?: 1),
                    'expiry_date' => $batch->expiry_date,
                ]
            ];
        }

        $rate = $material->exchange_rate ?: 1;
        $remainder = fmod($remaining, $rate);
        $sealedQty = $remaining - $remainder;

        $result = [];
        if ($sealedQty > 0) {
            $result[] = [
                'label' => 'nguyên',
                'quantity_base_unit' => $sealedQty,
                'quantity_input_unit' => $sealedQty / $rate,
                'expiry_date' => $batch->expiry_date,
            ];
        }
        if ($remainder > 0) {
            $result[] = [
                'label' => 'đang mở',
                'quantity_base_unit' => $remainder,
                'quantity_input_unit' => $remainder / $rate,
                'expiry_date' => $this->openedExpiry($batch, $material),
            ];
        }
        return $result;
    }

    public function openedExpiry(ImportReceiptDetail $batch, Material $material): ?Carbon
    {
        if (!$batch->opened_at || !$material->shelf_life_after_opening_days) {
            return $batch->expiry_date;
        }
        $afterOpening = $batch->opened_at->copy()->addDays($material->shelf_life_after_opening_days);
        if (!$batch->expiry_date)
            return $afterOpening;
        return $afterOpening->lt($batch->expiry_date) ? $afterOpening : $batch->expiry_date;
    }

    public function nearestExpiry(int $materialId): ?Carbon
    {
        $material = Material::find($materialId);
        if (!$material)
            return null;

        $batches = ImportReceiptDetail::query()
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->where('import_receipt_details.material_id', $materialId)
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->select('import_receipt_details.*')
            ->get();

        $earliest = null;
        foreach ($batches as $batch) {
            foreach ($this->breakdown($batch, $material) as $part) {
                if (!$part['expiry_date'])
                    continue;
                if (!$earliest || $part['expiry_date']->lt($earliest)) {
                    $earliest = $part['expiry_date'];
                }
            }
        }
        return $earliest;
    }

    // Thêm vào MaterialExpiryService, giữ nguyên các method cũ (breakdown, openedExpiry, nearestExpiry)

    public function nearestExpiryForMany(array $materialIds): array
    {
        $materialIds = array_values(array_unique($materialIds));
        if (empty($materialIds)) {
            return [];
        }

        $materials = Material::query()
            ->whereIn('id', $materialIds)
            ->select('id', 'exchange_rate', 'shelf_life_after_opening_days')
            ->get()
            ->keyBy('id');

        $batchesByMaterial = ImportReceiptDetail::query()
            ->join('import_receipts', 'import_receipts.id', '=', 'import_receipt_details.receipt_id')
            ->whereIn('import_receipt_details.material_id', $materialIds)
            ->where('import_receipts.status', 'active')
            ->where('import_receipt_details.remaining_quantity', '>', 0)
            ->select('import_receipt_details.*')
            ->get()
            ->groupBy('material_id');

        $result = [];

        foreach ($materialIds as $materialId) {
            $material = $materials->get($materialId);
            if (!$material) {
                $result[$materialId] = null;
                continue;
            }

            $batches = $batchesByMaterial->get($materialId, collect());
            $earliest = null;

            foreach ($batches as $batch) {
                foreach ($this->breakdown($batch, $material) as $part) {
                    if (!$part['expiry_date'])
                        continue;
                    if (!$earliest || $part['expiry_date']->lt($earliest)) {
                        $earliest = $part['expiry_date'];
                    }
                }
            }

            $result[$materialId] = $earliest;
        }

        return $result;
    }
}
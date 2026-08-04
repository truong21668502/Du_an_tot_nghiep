<?php

namespace App\Actions;

use App\Models\ImportReceiptDetail;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class WriteOffExpiredBatchAction
{
    /**
     * Xóa sổ 1 lô hàng đã hết hạn: trừ quantity_in_stock, ghi stock_movement, reset remaining_quantity.
     */
    public function execute(ImportReceiptDetail $batch): void
    {
        DB::transaction(function () use ($batch) {
            // Lock theo thứ tự material_id -> batch id để tránh deadlock với consume()
            $material = Material::lockForUpdate()->find($batch->material_id);

            if (!$material) {
                return;
            }

            // Lock lại batch trong transaction để tránh race với consume() đang chạy song song
            $lockedBatch = ImportReceiptDetail::lockForUpdate()->find($batch->id);

            if (!$lockedBatch || $lockedBatch->expired_at !== null || (float) $lockedBatch->remaining_quantity <= 0) {
                // Đã được xử lý bởi 1 job/request khác, hoặc đã hết từ trước — bỏ qua an toàn
                return;
            }

            $writeOffQty = (float) $lockedBatch->remaining_quantity;

            StockMovement::create([
                'material_id' => $material->id,
                'movement_type' => 'expired_writeoff',
                'quantity_change' => -$writeOffQty,
                'reference_type' => 'import_receipt_detail',
                'reference_id' => $lockedBatch->id,
                'moved_by' => null, // hệ thống tự động, không có user thao tác
                'note' => "Xóa sổ lô hết hạn ngày {$lockedBatch->expiry_date}, còn lại {$writeOffQty} {$material->base_unit} chưa dùng.",
                'moved_at' => now(),
            ]);

            $material->decrement('quantity_in_stock', $writeOffQty);

            $lockedBatch->update([
                'remaining_quantity' => 0,
                'expired_at' => now(),
            ]);
        });
    }
}
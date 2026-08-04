<?php

namespace App\Console\Commands;

use App\Actions\WriteOffExpiredBatchAction;
use App\Models\ImportReceiptDetail;
use Illuminate\Console\Command;

class WriteOffExpiredStock extends Command
{
    protected $signature = 'stock:write-off-expired';
    protected $description = 'Xóa sổ các lô hàng đã hết hạn nhưng còn tồn kho, đồng bộ lại quantity_in_stock';

    public function handle(WriteOffExpiredBatchAction $action): int
    {
        $batches = ImportReceiptDetail::query()
            ->where('expiry_date', '<', now()->toDateString())
            ->where('remaining_quantity', '>', 0)
            ->whereNull('expired_at')
            ->orderBy('material_id') // giữ thứ tự nhất quán, tránh deadlock nếu chạy nhiều lô cùng material
            ->get();

        if ($batches->isEmpty()) {
            $this->info('Không có lô hàng nào cần xóa sổ.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($batches as $batch) {
            try {
                $action->execute($batch);
                $count++;
            } catch (\Throwable $e) {
                $this->error("Lỗi khi xóa sổ batch ID {$batch->id}: {$e->getMessage()}");
                // Không dừng cả job vì 1 batch lỗi — log lại và tiếp tục
            }
        }

        $this->info("Đã xóa sổ {$count}/{$batches->count()} lô hàng hết hạn.");
        return self::SUCCESS;
    }
}
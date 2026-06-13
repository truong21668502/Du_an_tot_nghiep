<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TableReservation;
use App\Events\TableStatusUpdated;
use App\Models\Table;

class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Giải phóng bàn khi reservation đã qua giờ mà vẫn PENDING';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expired = TableReservation::with('table')
            ->where('status', 'PENDING')
            ->where('reservation_time', '<', now()->subMinutes(20))// quá 20p thì coi như hết hạn
            ->get();

        foreach ($expired as $reservation) {
            /** @var TableReservation $reservation */
            $reservation->update(['status' => 'CANCELLED']);

            /** @var Table|null $table */
            $table = $reservation->table;

            if (!$table) {
                continue;
            }

            $stillHasActive = TableReservation::where('table_id', $table->id)
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->exists();

            if (!$stillHasActive && $table->status === 'RESERVED') {
                $table->update(['status' => 'EMPTY']);
                broadcast(new TableStatusUpdated($table->fresh()));
                $this->info("Đã mở lại bàn {$table->table_name}");
            }
        }

        $this->info("Xử lý {$expired->count()} reservation hết hạn.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TableReservation;
use App\Events\TableStatusUpdated;
class ActivateReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:activate-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // app/Console/Commands/ActivateReservations.php
    public function handle()
    {
        // Lấy các reservation sắp đến trong 30 phút tới
        $upcoming = TableReservation::with('table')
            ->where('status', 'PENDING')
            ->whereBetween('reservation_time', [now(), now()->addMinutes(30)])
            ->get();

        foreach ($upcoming as $reservation) {
            if ($reservation->table->status === 'EMPTY') {
                $reservation->table->update(['status' => 'RESERVED']);
                broadcast(new TableStatusUpdated($reservation->table->fresh()));
            }
        }
    }
}

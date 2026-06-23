<?php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function broadcastOn()
    {
        return new Channel('cafe-tables');
    }

    public function broadcastAs()
    {
        return 'TableUpdated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->table->id,
            'status' => $this->table->status,
            'table_name' => $this->table->table_name,
            'reservations' => $this->table->reservations()
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->with('user:id,full_name,phone_number')
                ->orderBy('reservation_time')
                ->get()
                ->toArray(),
        ];
    }
}
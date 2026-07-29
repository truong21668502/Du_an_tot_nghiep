<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [new Channel('staff-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'table_id' => $this->order->table_id,
            'status' => $this->order->status,
        ];
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('staff-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.status-updated';
    }

    public function broadcastWith(): array
    {
        Log::info('OrderStatusUpdated broadcast', [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
        ]);

        return [
            'order' => $this->order->fresh(['table', 'details.product', 'details.variant', 'payment'])->toArray(),
        ];
    }
}
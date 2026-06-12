<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        // Load kèm chi tiết đơn để hiển thị trên UI
        $this->order = $order->load(['orderDetails.product', 'table']);
    }

    public function broadcastOn()
    {
        return new Channel('staff-orders');
    }

    public function broadcastAs()
    {
        return 'OrderCreated';
    }
}
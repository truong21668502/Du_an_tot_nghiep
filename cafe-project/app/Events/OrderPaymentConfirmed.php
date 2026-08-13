<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaymentConfirmed implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('staff-orders'),
            new Channel('shipper-orders')
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.payment-confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_code' => 'DH' . str_pad($this->order->id, 8, '0', STR_PAD_LEFT),
            'status' => $this->order->status,
            'payment_status' => $this->order->payment->payment_status ?? 'PENDING',
        ];
    }
}
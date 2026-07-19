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

        // Chỉ gửi thông tin tối thiểu qua Pusher để tránh lỗi "Payload too large"
        // Frontend sẽ tự reload dữ liệu đầy đủ từ server
        $this->order->loadMissing('table');

        return [
            'order' => [
                'id' => $this->order->id,
                'status' => $this->order->status,
                'table_id' => $this->order->table_id,
                'table' => $this->order->table ? [
                    'id' => $this->order->table->id,
                    'table_name' => $this->order->table->table_name,
                ] : null,
            ],
        ];
    }
}
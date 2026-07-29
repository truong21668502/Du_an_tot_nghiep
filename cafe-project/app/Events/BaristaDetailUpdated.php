<?php

namespace App\Events;

use App\Models\OrderDetail;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaristaDetailUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderDetail $detail) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('barista-queue'),
            new Channel('staff-orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'barista.detail.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->detail->id,
            'order_id'       => $this->detail->order_id,
            'barista_status' => $this->detail->barista_status,
            'product_name'   => $this->detail->product?->product_name,
            'table_name'     => $this->detail->order?->table?->table_name,
        ];
    }
}

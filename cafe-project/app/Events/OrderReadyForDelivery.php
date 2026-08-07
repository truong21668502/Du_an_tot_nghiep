<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReadyForDelivery implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        // Vẫn có thể load user để dự phòng, nhưng ưu tiên dùng dữ liệu bảng orders
        $order->load(
            'user:id,full_name,phone_number',
            'details.product:id,product_name,image_url',
            'details.variant'
        );
    }

    public function broadcastOn(): array
    {
        return [new Channel('shipper-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.ready';
    }

    public function broadcastWith(): array
    {
        $order = $this->order;
        
        // Địa chỉ đầy đủ lấy chuẩn từ bảng orders
        $address = implode(', ', array_filter([
            $order->address_detail,
            $order->ward,
            $order->city,
        ]));

        return [
            'order' => [
                'id'           => $order->id,
                'code'         => $order->code ?? 'ĐH' . $order->id,
                'status'       => $order->status,
                'customer'     => $order->receiver_name ?? ($order->user->full_name ?? 'Khách lẻ'),
                'phone'        => $order->receiver_phone ?? ($order->user->phone_number ?? 'Không có SĐT'),
                'address'      => $address,
                'distance'     => $order->distance, 
                'shipping_fee' => $order->shipping_fee,
                'total'        => $order->final_amount,
                'created_at'   => $order->created_at->toIso8601String(),
                'latitude'     => $order->latitude,
                'longitude'    => $order->longitude,
                'is_my_order'  => false,
            ],
        ];
    }
}
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
        // Quan trọng: Dùng kênh Public ('cafe-tables') thay vì Private
        // Vì khách hàng ở nhà chưa đăng nhập vẫn phải xem được bàn nào đang trống
        return new Channel('cafe-tables');
    }

    public function broadcastAs()
    {
        return 'TableUpdated';
    }
}
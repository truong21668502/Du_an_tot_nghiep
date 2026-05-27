<?php

namespace App\Events;

use App\Models\TestRealTime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    // Thay đổi kiểu dữ liệu thành Model TestRealTime
    public $data;

    public function __construct(TestRealTime $data)
    {
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('test-channel'),
        ];
    }

    // Đặt tên tường minh cho Event để Vue nhận diện chuẩn xác
    public function broadcastAs(): string
    {
        return 'DataCreated';
    }
}


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
        // Đặt một cái bẫy ghi log ở đây
        info('HÀM CONSTRUCT ĐÃ TỰ CHẠY! Data content là: ' . $data->content);
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


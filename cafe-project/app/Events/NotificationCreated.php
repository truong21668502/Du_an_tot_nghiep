<?php

namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct(AdminNotification $notification)
    {
        $this->notification = [
            'id'       => $notification->id,
            'type'     => $notification->type,
            'title'    => $notification->title,
            'message'  => $notification->message,
            'link'     => $notification->link,
            'is_read'  => false,
            'time'     => 'Vừa xong',
        ];
    }

    // Kênh phát công khai cho admin
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
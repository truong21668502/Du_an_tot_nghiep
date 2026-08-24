<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class AdminNotification extends Model
{
    use Prunable;

    protected $table = 'admin_notifications';

    protected $fillable = [
        'dedup_key',
        'category',
        'type',
        'title',
        'message',
        'link',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(30));
    }

    /**
     * Tự động bắt sự kiện khi có bản ghi mới được tạo vào DB
     */
    protected static function booted(): void
    {
        static::saved(function (AdminNotification $notification) {
            if (is_null($notification->read_at)) {
                broadcast(new \App\Events\NotificationCreated($notification));
            }
        });
    }
}
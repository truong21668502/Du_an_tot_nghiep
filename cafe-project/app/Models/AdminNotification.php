<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class AdminNotification extends Model
{
    use Prunable;

    /**
     * Tự động xác định các bản ghi cần dọn dẹp
     * Ví dụ: Xóa các thông báo đã đọc quá 30 ngày, hoặc thông báo chưa đọc quá 60 ngày
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(30));
    }

    protected $fillable = [
        'type', 'category', 'title', 'message', 'link', 'dedup_key', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}

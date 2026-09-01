<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasFactory;

    use SoftDeletes;

    // Định nghĩa tên bảng thực tế trong Database
    protected $table = 'tables';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'table_name',
        'area',
        'capacity',
        'qr_code',
        'status',
        'parent_table_id',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ cơ sở dữ liệu ra.
     */
    protected $casts = [
        'capacity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một bàn có thể có nhiều đơn hàng (Orders) được tạo tại bàn đó.
     * Liên kết đến bảng orders qua khóa ngoại 'table_id' (nếu database của bạn có thiết lập luồng này)
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id', 'id');
    }
}
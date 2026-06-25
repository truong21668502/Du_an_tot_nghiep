<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng trong Database
    protected $table = 'coupons';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_value',
        'usage_limit',
        'used_count',
        'expiration_date',
        'status',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ database.
     */
    protected $casts = [
        'discount_value'      => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_value'     => 'decimal:2',
        'usage_limit'         => 'integer',
        'used_count'          => 'integer',
        'expiration_date'     => 'datetime', // Ép kiểu về Date để dễ xử lý so sánh ngày hết hạn
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    /**
     * Mối quan hệ: Một mã giảm giá có thể được áp dụng cho nhiều Đơn hàng (Orders)
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_id', 'id');
    }
}
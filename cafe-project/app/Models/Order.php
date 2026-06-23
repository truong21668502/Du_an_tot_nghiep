<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng trong Database
    protected $table = 'orders';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'user_id',
        'table_id',
        'coupon_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'payment_method',
        'payment_status',
        'order_type',
        'status',
        'delivery_address',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ database.
     */
    protected $casts = [
        'user_id'         => 'integer',
        'table_id'        => 'integer',
        'coupon_id'       => 'integer',
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount'    => 'decimal:2',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Mối quan hệ: Đơn hàng thuộc về một bàn cụ thể (nếu có)
     * Liên kết với bảng tables qua khóa ngoại 'table_id'
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    /**
     * Mối quan hệ: Đơn hàng thuộc về một Khách hàng (nếu có tài khoản)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Mối quan hệ: Đơn hàng có thể áp dụng một mã giảm giá (nếu có)
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * Mối quan hệ: Một đơn hàng có nhiều món ăn chi tiết (OrderDetails)
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CouponUser extends Pivot
{
    // Khai báo tên bảng chính xác (Laravel mặc định hiểu bảng pivot là số ít và xếp theo alphabet)
    protected $table = 'coupon_user';

    // Cho phép fill dữ liệu vào các trường này
    protected $fillable = [
        'user_id',
        'coupon_id',
        'is_used',
        'used_at'
    ];

    // Ép kiểu dữ liệu cho các thuộc tính đặc biệt
    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Định nghĩa mối quan hệ ngược về User (Nếu cần)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Định nghĩa mối quan hệ ngược về Coupon (Nếu cần)
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
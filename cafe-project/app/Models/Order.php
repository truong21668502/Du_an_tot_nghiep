<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


#[ObservedBy(OrderObserver::class)]
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
        'cart_token',
        'table_id',
        'coupon_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'shipping_fee',
        'order_type',
        'status',
        'source',
        'cancel_reason',
        'note',
        'receiver_name',
        'receiver_phone',
        'address_detail',
        'ward',
        'city',
        'latitude',
        'longitude', 
        'goong_place_id',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ database.
     */
    protected $casts = [
        'user_id' => 'integer',
        'table_id' => 'integer',
        'coupon_id' => 'integer',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['barista_progress'];
    /**
     * Mối quan hệ: Đơn hàng thuộc về một bàn cụ thể (nếu có)
     * Liên kết với bảng tables qua khóa ngoại 'table_id'
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getBaristaProgressAttribute(): array
    {
        $this->loadMissing('details');

        $activeDetails = $this->details->where('barista_status', '!=', 'CANCELLED');
        $total = $activeDetails->count();
        $done = $activeDetails->where('barista_status', 'COMPLETED')->count();

        return [
            'done' => $done,
            'total' => $total,
            'is_complete' => $total > 0 && $done === $total,
            'label' => "{$done}/{$total}",
        ];
    }
}
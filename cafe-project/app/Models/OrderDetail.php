<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    // Khai báo tên bảng thực tế trong Database
    protected $table = 'order_details';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'variant_id',
        'unit_price',
        'note',
        'barista_status',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ database ra Eloquent Model.
     */
    protected $casts = [
        'order_id'   => 'integer',
        'product_id' => 'integer',
        'quantity'   => 'integer',
        'variant_id' => 'integer',
        'unit_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Chi tiết này thuộc về một Đơn hàng tổng (Order)
     * Liên kết qua khóa ngoại 'order_id'
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Mối quan hệ: Chi tiết này liên kết tới một Sản phẩm gốc (Product)
     * Liên kết qua khóa ngoại 'product_id'
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Mối quan hệ: Chi tiết này liên kết tới một Biến thể kích cỡ cụ thể (ProductVariant)
     * Liên kết qua khóa ngoại 'variant_id'
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }
}
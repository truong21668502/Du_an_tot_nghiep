<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng thực tế trong Database của bạn
    protected $table = 'product_variants';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'product_id',
        'size',
        'price',
        'discount_price',
        'sale_date_start',
        'sale_date_end',
        'stock_quantity',
        'sold',
        'status',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ cơ sở dữ liệu ra Eloquent Model.
     */
    protected $casts = [
        'product_id'      => 'integer',
        'price'           => 'decimal:2',
        'discount_price'  => 'decimal:2',
        'sale_date_start' => 'datetime',
        'sale_date_end'   => 'datetime',
        'stock_quantity'  => 'integer',
        'sold'            => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Mối quan hệ: Một biến thể kích thước thuộc về một Sản phẩm chính (Product)
     * Liên kết thông qua khóa ngoại 'product_id'
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'variant_id', 'id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Đã thêm dấu ; ở đây

class Product extends Model
{
    use HasFactory;

    // Khai báo tên bảng thực tế trong Database
    protected $table = 'products';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'product_name',
        'slug',
        'short_description',
        'description',
        'image_url',
        'is_active',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ DB ra để dễ xử lý logic.
     */
    protected $casts = [
        'category_id' => 'integer',
        'brand_id'    => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Mối quan hệ: Một sản phẩm thuộc về một Danh mục (Category)
     * Liên kết thông qua trường khóa ngoại 'category_id'
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Mối quan hệ: Một sản phẩm thuộc về một Thương hiệu (Brand)
     * Liên kết thông qua trường khóa ngoại 'brand_id'
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * Mối quan hệ: Một sản phẩm thì có nhiều hình ảnh phụ (ProductImage)
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    /**
     * Mối quan hệ: Một sản phẩm chính thì có nhiều biến thể Size/Giá (ProductVariant)
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    // Khai báo tên bảng thực tế trong Database
    protected $table = 'brands';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'brand_name',
        'logo_url',
        'description',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ DB ra.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một Thương hiệu (Brand) có thể có nhiều Sản phẩm (Product)
     * Liên kết thông qua khóa ngoại 'brand_id' ở bảng products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }
}
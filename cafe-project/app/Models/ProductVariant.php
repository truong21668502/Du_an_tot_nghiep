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

    public function getAvailableQuantity(): int 
    {
        // Trường hợp 1: Sản phẩm KHÔNG có công thức (Ví dụ: Lon Coca, đồ bán sẵn)
        if ($this->recipes->isEmpty()) {
            return $this->status === 'AVAILABLE' ? 99 : 0; 
        }

        // Trường hợp 2: Sản phẩm CÓ công thức chế biến
        $stockQuantity = PHP_INT_MAX;
        $hasValidMaterial = false;

        foreach ($this->recipes as $recipe) {
            if ($recipe->material && $recipe->quantity_needed > 0) {
                $hasValidMaterial = true;
                
                // Ép kiểu về float để tính toán chính xác số thập phân (kg, lít...)
                $inStock = (float) $recipe->material->quantity_in_stock;
                $needed = (float) $recipe->quantity_needed;

                $possible = (int) floor($inStock / $needed);
                $stockQuantity = min($stockQuantity, $possible);
            }
        }

        // Nếu có công thức nhưng các công thức đều lỗi/không có nguyên liệu liên kết
        if (!$hasValidMaterial) {
            return $this->status === 'AVAILABLE' ? 99 : 0;
        }

        // Trả về số lượng tối đa có thể làm được từ kho nguyên liệu (giới hạn tối đa là 99 để tránh tràn số)
        return min($stockQuantity, 99); 
    }

}
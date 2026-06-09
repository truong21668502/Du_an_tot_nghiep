<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    use HasFactory;

    // Khai báo chính xác tên bảng trong Database
    protected $table = 'recipes';

    /**
     * Các thuộc tính cho phép gán dữ liệu hàng loạt (Mass Assignable).
     * Khớp chính xác 100% với các trường trong ảnh thiết kế của bạn.
     */
    protected $fillable = [
        'product_id',
        'material_id',
        'size',
        'quantity_needed',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ database ra Eloquent Object.
     */
    protected $casts = [
        'product_id'      => 'integer',
        'material_id'     => 'integer',
        'quantity_needed' => 'decimal:2', // Định dạng số thập phân cho định lượng (g, ml...)
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Mối quan hệ: Công thức này thuộc về một Sản phẩm (Product) nhất định.
     * Liên kết thông qua khóa ngoại 'product_id'
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Mối quan hệ: Công thức này sử dụng một Nguyên liệu (Material) nhất định.
     * Liên kết thông qua khóa ngoại 'material_id'
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id', 'id');
    }
}
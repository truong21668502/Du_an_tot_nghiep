<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    // Định nghĩa chính xác tên bảng trong cơ sở dữ liệu
    protected $table = 'product_images';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'product_id',
        'image_url',
    ];

    /**
     * Tự động ép kiểu dữ liệu ngày tháng khi lấy ra từ database.
     */
    protected $casts = [
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một ảnh phụ thuộc về một Sản phẩm (Product)
     * Liên kết thông qua khóa ngoại 'product_id'
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
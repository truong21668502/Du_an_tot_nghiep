<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng thực tế trong Database
    protected $table = 'materials';

    /**
     * Các thuộc tính có thể fill dữ liệu hàng loạt (Mass Assignable).
     */
    protected $fillable = [
        'material_name',
        'base_unit',
        'quantity_in_stock',
        'input_unit',
        'exchange_rate',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ cơ sở dữ liệu ra Eloquent Model.
     */
    protected $casts = [
        'quantity_in_stock' => 'decimal:2',
        'exchange_rate'     => 'decimal:2',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    /**
     * Mối quan hệ: Một nguyên liệu có thể nằm trong nhiều Công thức pha chế (Recipes) của các món khác nhau
     * Liên kết thông qua khóa ngoại 'material_id' ở bảng recipes
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'material_id', 'id');
    }
}
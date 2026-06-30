<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'material_id',
        'user_id',
        'reason',
        'quantity_before',
        'quantity_after',
        'change_amount',
        'note',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function reasonLabels(): array
    {
        return [
            'kiem_ke' => 'Kiểm kê định kỳ',
            'that_thoat' => 'Thất thoát',
            'het_han' => 'Hết hạn',
            'hu_hong' => 'Hư hỏng',
            'khac' => 'Khác',
        ];
    }
}
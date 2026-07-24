<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProhibitedWord extends Model
{
    use HasFactory;

    protected $table = 'prohibited_words';

    protected $fillable = [
        'word',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scope để lọc từ đang active
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope để tìm kiếm
    public function scopeSearch($query, $keyword)
    {
        return $query->where('word', 'LIKE', "%{$keyword}%");
    }
}
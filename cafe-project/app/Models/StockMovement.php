<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'material_id',
        'movement_type',
        'quantity_change',
        'reference_type',
        'reference_id',
        'moved_by',
        'note',
        'moved_at',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'moved_by');
    }
}
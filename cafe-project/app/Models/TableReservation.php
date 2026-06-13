<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableReservation extends Model
{
    protected $table = 'table_reservations';

    protected $fillable = [
        'user_id',
        'table_id',
        'phone_number',
        'reservation_time',
        'guest_count',
        'status',
        'note',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

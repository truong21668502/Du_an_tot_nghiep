<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

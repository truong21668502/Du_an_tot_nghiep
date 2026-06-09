<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'password',
        'role',
        'reward_points',
        'gender',
        'date_of_birth',
        'status',
        'is_email_verified',
        'google_id',
    ];

    /**
     * Tự động ép kiểu dữ liệu khi lấy từ Database ra Model.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'reward_points' => 'integer',
        'is_email_verified' => 'boolean', // Chuyển tinyint(4) thành true/false cho dễ dùng
        'password' => 'hashed', // Tự động hash password từ bản Laravel 10 trở lên
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;


    public const THEMES = ['light', 'dark'];

    public const TEXT_ALIGNS = ['left', 'center', 'right'];

    public const POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'center-left', 'center', 'center-right',
        'bottom-left', 'bottom-center', 'bottom-right',
    ];

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'cloudinary_public_id',
        'button_text',
        'button_url',
        'theme',
        'text_align',
        'position',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
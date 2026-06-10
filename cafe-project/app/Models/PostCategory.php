<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong database
    protected $table = 'post_categories';

    // Cho phép fill dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Mối quan hệ: Một danh mục có nhiều bài viết (Nếu sau này bạn làm Model Post)
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
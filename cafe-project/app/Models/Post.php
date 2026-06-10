<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong database ứng với ảnh sơ đồ
    protected $table = 'posts';

    // Cho phép fill dữ liệu hàng loạt (Mass Assignment) cho các trường của bảng posts
    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug', // Giả định cột "Đường dẫn URL thân thiện cho SEO" tên là slug
        'thumbnail_url',
        'content',
        'status',
        'published_at',
    ];

    // Ép kiểu dữ liệu cho các thuộc tính đặc biệt (ví dụ trường views là kiểu số nguyên)
    protected $casts = [
        'views' => 'integer',
    ];

    /**
     * 📌 Mối quan hệ: Nhiều bài viết thuộc về một Danh mục bài viết (Many-to-One)
     * Liên kết ngược lại với bảng post_categories qua khóa ngoại post_category_id
     */

    public function user()
    {
        // Giả định khóa ngoại là 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }
    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    /**
     * 📌 Mối quan hệ: Một bài viết có thể có nhiều Bình luận (One-to-Many)
     * Liên kết tới bảng post_comments (nếu bạn có phát triển chức năng này)
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }
}
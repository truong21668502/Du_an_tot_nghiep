<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong database
    protected $table = 'post_comments';

    // Cho phép điền dữ liệu hàng loạt (Mass Assignment) khi tạo hoặc cập nhật bình luận
    protected $fillable = [
        'post_id',
        'user_id',
        'rating',
        'content',
        'status',
    ];

    // Ép kiểu dữ liệu cho các thuộc tính đặc thù để bảo toàn định dạng dữ liệu đầu ra
    protected $casts = [
        'post_id' => 'integer',
        'user_id' => 'integer',
        'rating'  => 'integer',
    ];

    /**
     * 📌 Mối quan hệ: Nhiều bình luận thuộc về một Bài viết (Many-to-One)
     * Liên kết ngược lại với bảng posts qua khóa ngoại post_id
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * 📌 Mối quan hệ: Nhiều bình luận thuộc về một Người dùng (Many-to-One)
     * Liên kết ngược lại với bảng users qua khóa ngoại user_id để hiển thị tên/avatar người bình luận
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 💡 Local Scope: Tiện ích hỗ trợ truy vấn nhanh các bình luận đã được phê duyệt
     * Cách dùng trong Controller: PostComment::approved()->get();
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }
}
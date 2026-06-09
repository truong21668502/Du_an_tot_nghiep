<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id()->comment('Mã bình luận bài viết (Khóa chính)');
            
            // Khóa ngoại liên kết tới bài viết (posts)
            $table->foreignId('post_id')
                ->constrained('posts')
                ->onDelete('cascade') // Nếu bài viết bị xóa, tự động dọn dẹp sạch bình luận của bài đó
                ->comment('Mã bài viết được bình luận (Khóa ngoại → posts.id)');
                
            // Khóa ngoại liên kết tới người bình luận (users)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade') // Nếu tài khoản user bị xóa, xóa luôn các bình luận của họ
                ->comment('Mã người dùng bình luận (Khóa ngoại → users.id)');
                
            // Điểm đánh giá bài viết (Cho phép NULL nếu khách chỉ muốn bình luận chứ không chấm điểm)
            $table->integer('rating')->nullable()->comment('Điểm số đánh giá bài viết từ 1 đến 5 sao (Có thể để trống)');
            
            $table->text('content')->comment('Nội dung bình luận của khách hàng');
            
            $table->enum('status', ['APPROVED', 'HIDDEN'])
                ->default('APPROVED')
                ->comment('Trạng thái hiển thị (APPROVED: Được duyệt hiển thị, HIDDEN: Bị ẩn do vi phạm/spam)');

            // Thời gian tạo và cập nhật tự động đồng bộ theo chuẩn Laravel
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian khách gửi bình luận');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};



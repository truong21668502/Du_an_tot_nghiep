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
        Schema::create('posts', function (Blueprint $table) {
            $table->id()->comment('Mã bài viết (Khóa chính)');
            
            // Khóa ngoại liên kết tới danh mục bài viết
            $table->foreignId('category_id')
                ->constrained('post_categories')
                ->onDelete('restrict') // Chặn xóa danh mục nếu bên trong vẫn còn bài viết đang lưu trữ
                ->comment('Mã danh mục bài viết (Khóa ngoại → post_categories.id)');
                
            // Khóa ngoại liên kết tới người viết bài (Nhân viên/Admin)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict') // Chặn xóa tài khoản nhân viên nếu họ đang là tác giả của bài viết
                ->comment('Mã tác giả bài viết (Khóa ngoại → users.id)');
                
            $table->string('title', 255)->comment('Tiêu đề bài viết');
            
            // Slug dùng làm URL thân thiện cho bài viết, bắt buộc duy nhất để làm SEO
            $table->string('slug', 255)->unique()->comment('Đường dẫn URL thân thiện cho SEO (Duy nhất)');
            
            $table->string('thumbnail_url', 255)->nullable()->comment('Đường dẫn ảnh bìa đại diện cho bài viết');
            
            // Dùng longText để thoải mái lưu trữ dữ liệu HTML/Markdown từ trình soạn thảo văn bản
            $table->longText('content')->comment('Nội dung chi tiết của bài viết');
            
            $table->enum('status', ['DRAFT', 'PUBLISHED'])
                ->default('DRAFT')
                ->comment('Trạng thái bài viết (DRAFT: Bản nháp, PUBLISHED: Đã xuất bản)');
                
            $table->dateTime('published_at')->nullable()->comment('Ngày giờ chính xác bài viết được công khai lên hệ thống');

            // Thời gian tạo và cập nhật tự động đồng bộ theo chuẩn Laravel
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo bài viết');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian chỉnh sửa gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
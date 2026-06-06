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
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id()->comment('Mã danh mục bài viết (Khóa chính)');
            $table->string('name', 150)->comment('Tên danh mục (Ví dụ: Tin tức, Tuyển dụng...)');
            
            // Slug dùng làm URL thân thiện cho SEO, bắt buộc duy nhất
            $table->string('slug', 150)->unique()->comment('Đường dẫn URL thân thiện cho SEO (Duy nhất)');
            
            $table->text('description')->nullable()->comment('Mô tả chi tiết về danh mục bài viết');
            
            // Thời gian tạo và cập nhật tự động đồng bộ theo chuẩn Laravel
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo danh mục');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian chỉnh sửa gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
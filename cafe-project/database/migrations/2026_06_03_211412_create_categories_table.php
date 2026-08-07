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
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('Mã danh mục (Khóa chính)');
            $table->string('category_name', 100)->comment('Tên danh mục sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả chi tiết về danh mục');
            $table->string('slug', 150)->unique()->comment('Đường dẫn thân thiện (Slug), dùng cho URL và phải là duy nhất');
            $table->softDeletes();
            
            // Tự động tạo 2 cột created_at và updated_at chuẩn Laravel cho danh mục
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo danh mục');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật danh mục gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
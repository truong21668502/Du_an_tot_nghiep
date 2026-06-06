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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id()->comment('Mã hình ảnh (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng products
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade') // Nếu sản phẩm bị xóa, tự động xóa sạch các ảnh phụ liên quan
                ->comment('Mã sản phẩm (Khóa ngoại liên kết tới bảng products)');
                
            $table->string('image_url', 255)->comment('Đường dẫn link ảnh phụ của sản phẩm');
            
            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tải ảnh lên');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian chỉnh sửa ảnh gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
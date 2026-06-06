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
        Schema::create('favorite_products', function (Blueprint $table) {
            $table->id()->comment('Mã bản ghi yêu thích (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng sản phẩm (products)
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade') // Nếu sản phẩm bị xóa khỏi menu, tự động xóa khỏi danh sách yêu thích của khách
                ->comment('Mã sản phẩm được yêu thích (Khóa ngoại → products.id)');
                
            // Khóa ngoại liên kết tới bảng khách hàng (users)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade') // Nếu tài khoản user bị xóa, tự động dọn dẹp danh sách yêu thích của họ
                ->comment('Mã khách hàng (Khóa ngoại → users.id)');
                
            // Thời gian yêu thích (Chính là thời gian tạo bản ghi)
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian khách bấm yêu thích sản phẩm');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // CHỈ MỤC TỐI ƯU (Ràng buộc Unique tránh trùng lặp)
            // Đảm bảo một khách hàng không thể bấm yêu thích cùng một sản phẩm tận 2 lần
            $table->unique(['user_id', 'product_id'], 'user_product_favorite_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_products');
    }
};
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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id()->comment('Mã đánh giá (Khóa chính)');
            
            // Khóa ngoại liên kết tới sản phẩm được đánh giá
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade') // Nếu xóa sản phẩm, tự động xóa sạch các review liên quan
                ->comment('Mã sản phẩm (Khóa ngoại → products.id)');
                
            // Khóa ngoại liên kết tới khách hàng viết đánh giá
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade') // Nếu xóa tài khoản user, xóa luôn review của họ
                ->comment('Mã khách hàng viết đánh giá (Khóa ngoại → users.id)');
                
            // Khóa ngoại liên kết tới đơn hàng chứa sản phẩm đó (Chứng thực đã mua hàng)
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('Mã đơn hàng chứng thực đã mua (Khóa ngoại → orders.id)');
                
            // Điểm số từ 1 đến 5 sao
            $table->integer('rating')->comment('Điểm số đánh giá từ 1 đến 5 sao');
            
            $table->text('comment')->nullable()->comment('Nội dung bình luận chi tiết của khách');
            
            // Thời gian tạo (Chính là thời gian đánh giá)
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian khách gửi đánh giá');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // CHỈ MỤC TỐI ƯU: Đảm bảo 1 sản phẩm trong 1 đơn hàng cụ thể chỉ được đánh giá duy nhất 1 lần
            $table->unique(['order_id', 'product_id'], 'order_product_review_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
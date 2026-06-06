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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id()->comment('Mã chi tiết đơn hàng (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng đơn hàng tổng (orders)
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade') // Nếu hủy/xóa đơn hàng tổng, tự động xóa sạch các dòng chi tiết của nó
                ->comment('Mã đơn hàng tổng (Khóa ngoại → orders.id)');
                
            // Khóa ngoại liên kết tới bảng sản phẩm (products)
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict') // Chặn xóa sản phẩm khỏi hệ thống nếu nó đã nằm trong lịch sử mua hàng của khách
                ->comment('Mã sản phẩm (Khóa ngoại → products.id)');
                
            $table->integer('quantity')->default(1)->comment('Số lượng món ăn/nước uống khách đặt');
            $table->enum('size', ['M', 'L'])->default('M')->comment('Kích cỡ sản phẩm tại thời điểm mua');
            $table->decimal('unit_price', 10, 2)->comment('Giá bán của 1 sản phẩm tại thời điểm mua (Giúp giữ vững báo cáo tài chính)');
            $table->string('note', 255)->nullable()->comment('Ghi chú món ăn của khách (Ví dụ: Ít đường, không đá...)');
            
            // Trạng thái xử lý pha chế của riêng từng món trong quầy Bar
            $table->enum('barista_status', ['PENDING', 'PREPARING', 'COMPLETED', 'CANCELLED'])
                ->default('PENDING')
                ->comment('Trạng thái pha chế của Barista (PENDING: Chờ làm, PREPARING: Đang làm, COMPLETED: Đã xong, CANCELLED: Hủy món)');

            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // CHỈ MỤC TỐI ƯU: Đảm bảo trong 1 đơn hàng, cùng 1 món nước ở 1 size cố định sẽ không bị tách làm 2 dòng
            $table->unique(['order_id', 'product_id', 'size'], 'order_product_size_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('Mã đơn hàng (Khóa chính)');
            
            // Khóa ngoại liên kết tới khách hàng (Để NULL nếu là khách vãng lai mua tại quầy)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict')
                ->comment('Mã khách hàng (Khóa ngoại, NULL nếu là khách vãng lai)');
                
            // Khóa ngoại liên kết tới bàn ăn (Để NULL nếu khách mua mang về hoặc giao hàng)
            $table->foreignId('table_id')
                ->nullable()
                ->constrained('tables')
                ->onDelete('restrict')
                ->comment('Mã bàn ăn (Khóa ngoại, NULL nếu mang đi hoặc giao hàng)');
                
            // Khóa ngoại liên kết tới mã giảm giá (Nếu có áp dụng)
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->onDelete('restrict')
                ->comment('Mã giảm giá áp dụng cho đơn hàng (Khóa ngoại, NULL nếu không dùng)');

            // Các cột tính toán số tiền chuẩn kế toán DECIMAL(10,2)
            $table->decimal('total_amount', 10, 2)->comment('Tổng tiền ban đầu của các món ăn (Chưa giảm giá)');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->comment('Số tiền được giảm trừ từ coupon');
            $table->decimal('final_amount', 10, 2)->comment('Số tiền cuối cùng khách phải thanh toán (total - discount)');
            
            // Các chuỗi trạng thái và hình thức vận hành
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'MOMO', 'VNPAY'])
                ->nullable()
                ->comment('Phương thức thanh toán của khách hàng');
                
            $table->enum('payment_status', ['PENDING', 'PAID', 'REFUNDED'])
                ->default('PENDING')
                ->comment('Trạng thái thanh toán (PENDING: Chờ, PAID: Đã trả, REFUNDED: Đã hoàn tiền)');
                
            $table->enum('order_type', ['DINE_IN', 'TAKE_AWAY', 'DELIVERY'])
                ->comment('Hình thức mua hàng (DINE_IN: Tại chỗ, TAKE_AWAY: Mang đi, DELIVERY: Giao hàng)');
                
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])
                ->default('PENDING')
                ->comment('Trạng thái đơn hàng (PENDING: Chờ duyệt, PROCESSING: Đang pha chế, COMPLETED: Hoàn thành, CANCELLED: Đã hủy)');
                
            $table->text('delivery_address')->nullable()->comment('Địa chỉ nhận hàng (Chỉ bắt buộc nếu order_type là DELIVERY)');

            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian khách đặt hàng');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật trạng thái đơn gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
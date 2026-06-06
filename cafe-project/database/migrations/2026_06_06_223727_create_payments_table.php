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
        Schema::create('payments', function (Blueprint $table) {
            $table->id()->comment('Mã thanh toán (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng đơn hàng, đảm bảo UNIQUE (quan hệ 1-1)
            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->onDelete('restrict') // Chặn xóa đơn hàng nếu đã có bản ghi thanh toán tài chính liên quan
                ->comment('Mã đơn hàng được thanh toán (Khóa ngoại UNIQUE → orders.id)');
                
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'MOMO', 'VNPAY'])
                ->comment('Phương thức thanh toán sử dụng');
                
            $table->string('transaction_id', 100)
                ->nullable()
                ->comment('Mã giao dịch trả về từ ngân hàng hoặc ví điện tử (Dùng để đối soát tài chính)');
                
            $table->decimal('amount', 10, 2)->comment('Số tiền thực tế khách đã thanh toán');
            
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED', 'REFUNDED'])
                ->default('PENDING')
                ->comment('Trạng thái giao dịch (PENDING: Chờ, PAID: Thành công, FAILED: Thất bại, REFUNDED: Đã hoàn tiền)');
                
            $table->dateTime('payment_time')->nullable()->comment('Thời gian chính xác giao dịch được thực hiện thành công');

            // Thời gian hệ thống tự động ghi nhận bản ghi
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
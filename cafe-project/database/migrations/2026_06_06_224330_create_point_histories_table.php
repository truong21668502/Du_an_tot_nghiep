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
        Schema::create('point_histories', function (Blueprint $table) {
            $table->id()->comment('Mã lịch sử thay đổi điểm (Khóa chính)');
            
            // Khóa ngoại liên kết tới khách hàng
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict') // Chặn xóa khách hàng nếu đang có lịch sử điểm tài chính liên quan
                ->comment('Mã khách hàng được thay đổi điểm (Khóa ngoại → users.id)');
                
            // Khóa ngoại liên kết tới đơn hàng (Cho phép NULL nếu điểm tăng/giảm do Admin hoặc sự kiện khác)
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null') // Nếu đơn hàng cũ bị xóa/hủy lưu trữ, lịch sử điểm của khách vẫn được giữ lại và chuyển cột này về NULL
                ->comment('Mã đơn hàng liên quan (Khóa ngoại, có thể NULL → orders.id)');
                
            // Số điểm thay đổi (Có thể lưu số dương để cộng điểm hoặc số âm để trừ điểm)
            $table->integer('points_changed')->comment('Số lượng điểm thay đổi (+ hoặc -)');
            
            $table->string('reason', 255)
                ->default('Tích điểm thành viên')
                ->comment('Lý do thay đổi điểm (Ví dụ: Thưởng sinh nhật, Đổi quà, Hoàn điểm...)');

            // Thời gian thực hiện (Chính là thời gian tạo bản ghi)
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian thực hiện giao dịch điểm');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_histories');
    }
};
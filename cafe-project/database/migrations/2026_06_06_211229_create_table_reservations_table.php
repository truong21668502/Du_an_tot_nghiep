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
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id()->comment('Mã đặt bàn (Khóa chính)');
            
            // Khóa ngoại liên kết tới khách hàng đặt chỗ
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict') // Chặn xóa khách hàng nếu họ đang có lịch sử đặt bàn
                ->comment('Mã khách hàng đặt bàn (Khóa ngoại → users.id)');
                
            // Khóa ngoại liên kết tới bàn được đặt
            $table->foreignId('table_id')
                ->constrained('tables')
                ->onDelete('restrict') // Chặn xóa bàn nếu bàn đó đang có khách đặt trước
                ->comment('Mã bàn được chọn (Khóa ngoại → tables.id)');
                
            $table->dateTime('reservation_time')->comment('Ngày và giờ chính xác khách hẹn đến quán');
            $table->integer('guest_count')->comment('Số lượng khách đi cùng nhóm');
            
            // Chuỗi trạng thái đầy đủ cho vòng đời đặt bàn
            $table->enum('status', ['PENDING', 'CONFIRMED', 'ARRIVED', 'CANCELLED'])
                ->default('PENDING')
                ->comment('Trạng thái đặt bàn (PENDING: Chờ duyệt, CONFIRMED: Đã xác nhận, ARRIVED: Khách đã đến, CANCELLED: Đã hủy)');
                
            $table->string('note', 255)->nullable()->comment('Ghi chú của khách (Ví dụ: Ngồi gần cửa sổ, chuẩn bị hoa tặng sinh nhật...)');
            
            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian khách bấm đặt bàn');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật trạng thái gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
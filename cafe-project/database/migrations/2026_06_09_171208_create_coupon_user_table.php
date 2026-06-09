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
        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại liên kết tới bảng users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Nếu xóa user, các voucher trong ví của user đó cũng tự động xóa theo

            // Khóa ngoại liên kết tới bảng coupons
            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->onDelete('cascade'); // Nếu xóa mã coupon tổng, các mã trong ví user cũng tự động xóa

            // Thuộc tính mở rộng để quản lý trạng thái voucher của riêng User này
            $table->boolean('is_used')->default(false)->comment('false: Chưa dùng (Còn trong ví), true: Đã dùng'); // false: Chưa dùng (Còn trong ví), true: Đã dùng
            $table->timestamp('used_at')->nullable()->comment('Lưu chính xác thời gian user bấm áp dụng mã vào hóa đơn');    // Lưu chính xác thời gian user bấm áp dụng mã vào hóa đơn

            $table->timestamps(); // create_at (Thời gian đổi/nhận mã), updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_user');
    }
};

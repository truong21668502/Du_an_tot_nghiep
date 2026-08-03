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
        // Đổi tên bảng thành user_addresses theo đúng thiết kế của bạn
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id()->comment('Mã địa chỉ (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade') // Xóa tài khoản user thì tự động dọn sạch địa chỉ của user đó
                ->comment('Mã người dùng (Khóa ngoại liên kết tới bảng users)');
                
            $table->string('receiver_name', 100)->comment('Tên người nhận hàng');
            $table->string('receiver_phone', 15)->comment('Số điện thoại người nhận');
            $table->string('address_detail', 255)->comment('Chi tiết địa chỉ (Số nhà, tên đường,...)');
            $table->string('ward', 100)->nullable()->comment('Phường / Xã');
            $table->string('city', 100)->nullable()->comment('Tỉnh / Thành phố');
            // --- Bổ sung các trường hỗ trợ Goong Map ---
            $table->decimal('latitude', 10, 8)->nullable()->comment('Vĩ độ từ Goong Map (VD: 16.054407)');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Kinh độ từ Goong Map (VD: 108.202167)');
            $table->string('goong_place_id', 255)->nullable()->comment('Mã địa điểm Place ID của Goong Map');
            
            $table->boolean('is_default')->default(false)->comment('Là địa chỉ mặc định? (0: Không, 1: Có)');
            
            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
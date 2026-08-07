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
        // 1. BẢNG USERS (Đã bổ sung comment chi tiết cho từng trường)
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Mã định danh người dùng (Khóa chính)');
            $table->string('full_name', 100)->comment('Họ và tên đầy đủ của người dùng');
            $table->string('phone_number', 15)->unique()->nullable()->comment('Số điện thoại (Dùng để đăng nhập/liên hệ)');
            $table->string('email', 100)->unique()->nullable()->comment('Email của người dùng');
            $table->string('password', 255)->comment('Mật khẩu tài khoản (đã mã hóa)');
            
            $table->enum('role', ['ADMIN', 'STAFF', 'BARISTA', 'CUSTOMER', 'SHIPPER'])
                ->default('CUSTOMER')
                ->comment('Vai trò/Phân quyền trong hệ thống');
                
            $table->integer('reward_points')->nullable()->default(0)->comment('Điểm tích lũy cho khách hàng');
            
            $table->enum('gender', ['Nam', 'Nữ', 'Khác'])
                ->default('Khác')
                ->comment('Giới tính của người dùng');
                
            $table->date('date_of_birth')->nullable()->comment('Ngày tháng năm sinh');
            
            $table->enum('status', ['active', 'inactive', 'banned'])
                ->default('active')
                ->comment('Trạng thái tài khoản (Hoạt động, Không hoạt động, Bị khóa)');
                
            $table->string('google_id', 255)->nullable()->comment('ID tài khoản Google (nếu đăng nhập bằng Google)');

            $table->string('status_note', 255)->nullable()
                ->comment('Ghi chú chi tiết lý do khi chuyển đổi trạng thái tài khoản (Ví dụ: Nghỉ thai sản, nghỉ lý do gì...)');

            // Thêm dòng này ở cuối bảng để kích hoạt Xóa mềm
            $table->softDeletes()->comment('Thời điểm xóa mềm tài khoản (Null là chưa xóa)');

            // kiểm tra email đã xác thực hay chưa (dùng cho logic đăng nhập/đăng ký bằng email)
            $table->boolean('is_email_verified')->default(false)->comment('Trạng thái xác thực email (0: Chưa, 1: Rồi)');
            // ghi nhớ đăng nhập (dùng cho tính năng "Remember Me" khi đăng nhập)
            $table->rememberToken()->comment('Token ghi nhớ đăng nhập (dùng cho tính năng "Remember Me")');

            $table->timestamp('email_verified_at')->nullable();
            
            // Thời gian tạo và cập nhật chuẩn DB
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo tài khoản');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật thông tin gần nhất');
        });

        // 2. BẢNG MẶC ĐỊNH CỦA LARAVEL
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Email yêu cầu đổi mật khẩu');
            $table->string('token')->comment('Mã token xác thực đổi mật khẩu');
            $table->timestamp('created_at')->nullable()->comment('Thời gian tạo token');
        });

        // 3. BẢNG MẶC ĐỊNH CỦA LARAVEL
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Mã session định danh');
            $table->foreignId('user_id')->nullable()->index()->comment('ID người dùng liên kết (nếu có)');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP của thiết bị');
            $table->text('user_agent')->nullable()->comment('Thông tin trình duyệt/thiết bị');
            $table->longText('payload')->comment('Dữ liệu lưu trữ trong session');
            $table->integer('last_activity')->index()->comment('Thời điểm tương tác cuối cùng (Timestamp)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
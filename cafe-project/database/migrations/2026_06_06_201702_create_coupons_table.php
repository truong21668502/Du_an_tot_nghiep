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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id()->comment('Mã coupon (Khóa chính)');
            $table->string('code', 50)->unique()->comment('Mã giảm giá người dùng nhập vào (Viết hoa, viết liền, duy nhất)');
            
            $table->enum('discount_type', ['FIXED', 'PERCENTAGE'])
                ->default('FIXED')
                ->comment('Loại giảm giá (FIXED: Số tiền cố định, PERCENTAGE: Giảm theo %)');
                
            $table->decimal('discount_value', 10, 2)->comment('Giá trị giảm giá (Số tiền giảm hoặc số % giảm)');
            
            $table->decimal('max_discount_amount', 10, 2)
                ->nullable()
                ->comment('Số tiền giảm tối đa (Đặc biệt quan trọng khi chọn giảm theo %)');
                
            $table->decimal('min_order_value', 10, 2)
                ->default(0.00)
                ->comment('Giá trị đơn hàng tối thiểu bắt buộc để có thể áp dụng mã');
                
            $table->integer('usage_limit')->nullable()->comment('Tổng số lần mã này được phép sử dụng trên hệ thống (Để NULL nếu không giới hạn)');
            $table->integer('used_count')->default(0)->comment('Số lần mã này đã được sử dụng thực tế');
            
            $table->dateTime('expiration_date')->comment('Ngày và giờ chính xác mã hết hạn sử dụng');
            
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'EXPIRED'])
                ->default('ACTIVE')
                ->comment('Trạng thái hoạt động của mã');

            $table->softDeletes();

            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
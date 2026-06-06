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
        Schema::create('materials', function (Blueprint $table) {
            $table->id()->comment('Mã nguyên liệu (Khóa chính)');
            $table->string('material_name', 150)->comment('Tên nguyên liệu (Ví dụ: Cà phê hạt, Sữa tươi Vinamilk)');
            
            // Đơn vị gốc nhỏ nhất để hệ thống trừ kho chính xác khi bán hàng
            $table->string('base_unit', 50)->comment('Đơn vị nhỏ nhất để quản lý kho (g, ml, Trái, Gói)');
            
            // Số lượng tồn kho thực tế, LUÔN LUÔN lưu trữ dựa trên tính toán theo base_unit
            $table->decimal('quantity_in_stock', 10, 2)->default(0.00)->comment('Số lượng tồn kho thực tế (Tính theo base_unit)');
            
            // Đơn vị hiển thị trên giao diện nhập kho cho nhân viên dễ dùng
            $table->string('input_unit', 50)->comment('Đơn vị thường dùng khi nhập hàng (Kg, Lít, Hộp, Túi, Thùng)');
            
            // Tỷ lệ dùng để nhân số lượng tự động khi nhập kho
            $table->decimal('exchange_rate', 10, 2)->default(1.00)->comment('Tỷ lệ quy đổi: 1 input_unit = bao nhiêu base_unit');

            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo nguyên liệu');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật kho gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
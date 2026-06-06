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
        Schema::create('import_receipt_details', function (Blueprint $table) {
            $table->id()->comment('Mã chi tiết phiếu nhập (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng phiếu nhập tổng (import_receipts)
            $table->foreignId('receipt_id')
                    ->constrained('import_receipts')
                    ->onDelete('cascade') // Nếu xóa phiếu nhập tổng, tự động xóa sạch các dòng chi tiết của nó
                    ->comment('Mã phiếu nhập tổng (Khóa ngoại → import_receipts.id)');
                    
            // Khóa ngoại liên kết tới bảng nguyên liệu (materials)
            $table->foreignId('material_id')
                    ->constrained('materials')
                    ->onDelete('restrict') // Chặn không cho xóa nguyên liệu nếu nó đã từng được nhập kho (để giữ lịch sử kế toán)
                    ->comment('Mã nguyên liệu được nhập (Khóa ngoại → materials.id)');

            // Số lượng nhập thực tế (Tính theo đơn vị nhập input_unit của nguyên liệu)
            $table->decimal('quantity', 10, 2)->comment('Số lượng nguyên liệu nhập vào');
            
            // Giá tiền nhập cho một đơn vị nguyên liệu
            $table->decimal('unit_price', 10, 2)->comment('Đơn giá nhập của một đơn vị');

            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // CHỈ MỤC TỐI ƯU: Tránh việc trong cùng 1 phiếu nhập lại có 2 dòng nhập cùng 1 loại nguyên liệu tách rời
            $table->unique(['receipt_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_receipt_details');
    }
};
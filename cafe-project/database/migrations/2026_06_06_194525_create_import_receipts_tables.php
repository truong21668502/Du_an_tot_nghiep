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
        // BẢNG TỔNG: PHIẾU NHẬP KHO
        Schema::create('import_receipts', function (Blueprint $table) {
            $table->id()->comment('Mã phiếu nhập (Khóa chính)');
            
            // Khóa ngoại liên kết tới nhân viên tạo phiếu (users)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict') // Không cho xóa nhân viên nếu phiếu nhập của họ còn lưu trong lịch sử
                ->comment('Mã người tạo phiếu (Khóa ngoại liên kết tới bảng users)');
                
            $table->string('supplier_name', 150)->nullable()->comment('Tên nhà cung cấp hoặc nơi mua hàng');
            $table->decimal('total_cost', 10, 2)->default(0)->comment('Tổng số tiền của toàn bộ phiếu nhập kho');
            
            // Thời gian tạo phiếu (chính là thời gian nhập kho)
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian lập phiếu nhập kho');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_receipts');
    }
};
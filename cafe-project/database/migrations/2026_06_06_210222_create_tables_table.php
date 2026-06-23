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
        Schema::create('tables', function (Blueprint $table) {
            $table->id()->comment('Mã bàn ăn/bàn cà phê (Khóa chính)');
            $table->string('table_name', 50)->comment('Tên bàn hoặc số bàn (Ví dụ: Bàn 01, Bàn VIP 02)');
            
            // Cột mở rộng giúp phân loại khu vực phục vụ trong quán
            $table->string('area', 50)->nullable()->default('Tầng trệt')->comment('Khu vực hoặc vị trí tầng (Ví dụ: Tầng 1, Ngoài trời, Sân thượng)');

            $table->integer('capacity')->default(4)->comment('Sức chứa tối đa của bàn (số người)');
            
            // Đường dẫn URL tích hợp trong mã QR để khách quét gọi món tại bàn
            $table->string('qr_code', 255)->unique()->nullable()->comment('Link hoặc mã QR định danh phục vụ gọi món tại bàn (Duy nhất)');
            
            $table->enum('status', ['EMPTY', 'OCCUPIED'])
                ->default('EMPTY')
                ->comment('Trạng thái hiện tại của bàn (EMPTY: Bàn trống, OCCUPIED: Có khách)');

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
        Schema::dropIfExists('tables');
    }
};
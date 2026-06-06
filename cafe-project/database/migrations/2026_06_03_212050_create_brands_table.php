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
        Schema::create('brands', function (Blueprint $table) {
            $table->id()->comment('Mã thương hiệu (Khóa chính)');
            $table->string('brand_name', 150)->comment('Tên thương hiệu sản phẩm');
            $table->string('logo_url', 255)->nullable()->comment('Đường dẫn link ảnh logo thương hiệu');
            $table->text('description')->nullable()->comment('Mô tả chi tiết về thương hiệu');
            
            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo thương hiệu');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật thương hiệu gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
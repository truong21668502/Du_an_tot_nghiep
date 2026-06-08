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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id()->comment('Mã công thức (Khóa chính)');
            
            $table->foreignId('variant_id')
                    ->constrained('product_variants')
                    ->onDelete('cascade')
                    ->comment('Mã variant (công thức theo từng size)');

            // Khóa ngoại liên kết tới bảng nguyên liệu (materials)
            $table->foreignId('material_id')
                    ->constrained('materials')
                    ->onDelete('restrict') //hạn chế vì khi xoá nguyên liệu thì vẫn cần dữ lại công thức để dùng sau
                    ->comment('Mã nguyên liệu (Khóa ngoại liên kết tới bảng materials)');

            // Định lượng cần dùng (Ví dụ: 15.50 g cà phê hoặc 120.00 ml sữa)
            $table->decimal('quantity_needed', 10, 2)
                    ->comment('Lượng nguyên liệu tiêu hao cần thiết (Tính theo base_unit của nguyên liệu)');

            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo công thức');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật công thức gần nhất');

            // CHỈ MỤC TỐI ƯU (Ràng buộc Unique tránh trùng lặp)
            // Đảm bảo 1 sản phẩm ở 1 size cố định không thể bị trùng lặp cùng 1 loại nguyên liệu 2 lần
            $table->unique(['variant_id', 'material_id'], 'variant_material_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
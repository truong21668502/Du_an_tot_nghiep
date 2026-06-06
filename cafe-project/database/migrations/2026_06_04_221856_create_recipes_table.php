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
            
            // Khóa ngoại liên kết tới bảng sản phẩm (products)
            $table->foreignId('product_id')
                    ->constrained('products')
                    ->onDelete('cascade') // Nếu xóa sản phẩm, tự động xóa công thức của sản phẩm đó
                    ->comment('Mã sản phẩm (Khóa ngoại liên kết tới bảng products)');

            // Khóa ngoại liên kết tới bảng nguyên liệu (materials)
            $table->foreignId('material_id')
                    ->constrained('materials')
                    ->onDelete('restrict') // Nếu xóa nguyên liệu, tự động dọn dẹp hàng liên quan trong công thức
                    ->comment('Mã nguyên liệu (Khóa ngoại liên kết tới bảng materials)');
            
            // Phân loại định lượng nguyên liệu theo từng kích cỡ ly
            $table->enum('size', ['M', 'L'])
                    ->default('M')
                    ->comment('Kích cỡ sản phẩm áp dụng công thức này');

            // Định lượng cần dùng (Ví dụ: 15.50 g cà phê hoặc 120.00 ml sữa)
            $table->decimal('quantity_needed', 10, 2)
                    ->comment('Lượng nguyên liệu tiêu hao cần thiết (Tính theo base_unit của nguyên liệu)');

            // Thời gian tạo và cập nhật tự động
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo công thức');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật công thức gần nhất');

            // CHỈ MỤC TỐI ƯU (Ràng buộc Unique tránh trùng lặp)
            // Đảm bảo 1 sản phẩm ở 1 size cố định không thể bị trùng lặp cùng 1 loại nguyên liệu 2 lần
            $table->unique(['product_id', 'material_id', 'size'], 'product_material_size_unique');
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
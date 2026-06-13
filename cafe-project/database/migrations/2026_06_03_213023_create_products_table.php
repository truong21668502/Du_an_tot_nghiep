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
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Mã sản phẩm (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng categories
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('restrict') // Nếu xóa danh mục, xóa các sản phẩm thuộc danh mục đó
                ->comment('Mã danh mục (Khóa ngoại liên kết tới bảng categories)');
            
            // Khóa ngoại liên kết tới bảng brands (Bảng thương hiệu dùng id mặc định là BIGINT giống Laravel)
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->onDelete('set null') // Nếu xóa thương hiệu, sản phẩm vẫn giữ nguyên và thương hiệu chuyển thành NULL
                ->comment('Mã thương hiệu (Khóa ngoại liên kết tới bảng brands)');

            $table->string('product_name', 150)->comment('Tên sản phẩm');
            $table->string('slug', 150)->unique()->comment('Đường dẫn thân thiện (Slug) duy nhất dùng cho URL sản phẩm');
            $table->text('short_description')->nullable()->comment('Mô tả ngắn gọn về sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả chi tiết về sản phẩm');
            $table->string('image_url', 255)->nullable()->comment('Hình ảnh đại diện của sản phẩm');
            
            $table->enum('is_active', ['Đang bán', 'Ngừng kinh doanh'])->default('Đang bán')->comment('Trạng thái kinh doanh hiện tại');
            
            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo sản phẩm');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật sản phẩm gần nhất');
        });


        //chạy bảng này sau bảng products
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id()->comment('Mã biến thể sản phẩm (Khóa chính)');

            // Khóa ngoại liên kết tới sản phẩm gốc (products)
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade') // Nếu xóa sản phẩm gốc, tự động xóa sạch các size biến thể của nó
                ->comment('Mã sản phẩm cha (Khóa ngoại → products.id)');

            $table->string('size', 50)->comment('Kích cỡ của sản phẩm (Size M hoặc Size L...)');

            // Các trường tài chính quản lý giá của từng size độc lập
            $table->decimal('price', 10, 2)->comment('Giá bán gốc của sản phẩm tương ứng với size này');
            $table->decimal('discount_price', 10, 2)->nullable()->comment('Giá khuyến mãi/giảm giá của size này (Để NULL nếu không giảm giá)');

            // Thời gian áp dụng chương trình giảm giá
            $table->dateTime('sale_date_start')->nullable()->comment('Ngày bắt đầu áp dụng giá khuyến mãi');
            $table->dateTime('sale_date_end')->nullable()->comment('Ngày kết thúc áp dụng giá khuyến mãi');

            $table->integer('sold')->default(0)->comment('Tổng số lượng đã bán được tính riêng cho size này');

            $table->enum('status', ['AVAILABLE', 'OUT_OF_STOCK'])
                ->default('AVAILABLE')
                ->comment('Trạng thái kho của riêng size này (AVAILABLE: Còn hàng, OUT_OF_STOCK: Hết hàng)');

            // RÀNG BUỘC UNIQUE NHÓM: Đảm bảo một sản phẩm không thể có 2 dòng cấu hình cho cùng một size
            $table->unique(['product_id', 'size'], 'product_size_unique_index');

            // Thời gian hệ thống tự động ghi nhận
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo biến thể size này');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian chỉnh sửa thông tin biến thể gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_variants');
    }
};
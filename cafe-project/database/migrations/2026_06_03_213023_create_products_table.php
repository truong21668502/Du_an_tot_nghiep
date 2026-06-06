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
            
            // Định dạng tiền tệ DECIMAL(10, 2) chuẩn dữ liệu gốc của bạn
            $table->decimal('price', 10, 2)->comment('Giá gốc của sản phẩm');
            $table->decimal('discount_price', 10, 2)->nullable()->comment('Giá khuyến mãi của sản phẩm (nếu có)');
            
            $table->integer('sold')->default(0)->comment('Số lượng sản phẩm đã bán');
            
            $table->enum('size', ['M', 'L'])->default('M')->comment('Kích cỡ sản phẩm');
            $table->enum('status', ['AVAILABLE', 'OUT_OF_STOCK'])->default('AVAILABLE')->comment('Trạng thái kho (Còn hàng / Hết hàng)');
            $table->enum('is_active', ['Đang bán', 'Ngừng kinh doanh'])->default('Đang bán')->comment('Trạng thái kinh doanh hiện tại');
            
            $table->date('sale_date_start')->nullable()->comment('Thời gian bắt đầu áp dụng giảm giá');
            $table->date('sale_date_end')->nullable()->comment('Thời gian kết thúc áp dụng giảm giá');
            
            // Thời gian tạo và cập nhật tự động chuẩn database
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo sản phẩm');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Thời gian cập nhật sản phẩm gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
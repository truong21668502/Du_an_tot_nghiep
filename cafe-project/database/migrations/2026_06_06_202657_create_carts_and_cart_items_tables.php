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
        // 1. BẢNG TỔNG: GIỎ HÀNG (Carts)
        Schema::create('carts', function (Blueprint $table) {
            $table->id()->comment('Mã giỏ hàng (Khóa chính)');
            
            // Khóa ngoại liên kết tới người dùng, đảm bảo UNIQUE (mỗi người 1 giỏ)
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->onDelete('cascade') // Nếu xóa tài khoản user, tự động xóa sạch giỏ hàng của họ
                ->comment('Mã khách hàng sở hữu giỏ (Khóa ngoại UNIQUE → users.id)');
            $table->string('token', 255)->comment('Dùng cho trường hợp user không đăng nhập')->unique()->nullable();  
                
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // 2. BẢNG CHI TIẾT: MÓN ĂN TRONG GIỎ (Cart_items)
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id()->comment('Mã chi tiết sản phẩm trong giỏ (Khóa chính)');
            
            // Khóa ngoại liên kết tới bảng giỏ hàng tổng
            $table->foreignId('cart_id')
                ->constrained('carts')
                ->onDelete('cascade') // Nếu giỏ hàng bị xóa/hủy, tự động xóa hết các món bên trong
                ->comment('Mã giỏ hàng tổng (Khóa ngoại → carts.id)');
                
            // Khóa ngoại liên kết tới sản phẩm
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade') // Nếu sản phẩm bị xóa khỏi menu, tự động mất khỏi giỏ hàng của khách
                ->comment('Mã sản phẩm (Khóa ngoại → products.id)');
                
            $table->integer('quantity')->default(1)->comment('Số lượng sản phẩm khách chọn');

            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->onDelete('cascade')
                ->comment('Mã variant đã chọn');

            $table->string('note', 255)->nullable()->comment('Ghi chú đặc biệt của khách (ví dụ: ít đá, nhiều đường...)');
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // CHỈ MỤC TỐI ƯU: Tránh trùng lặp món cùng size trong giỏ hàng
            // Giúp Backend dễ xử lý logic: trùng món + trùng biến thể thì tự động CỘNG DỒN số lượng thay vì tạo dòng mới
            $table->unique(['cart_id', 'variant_id'], 'cart_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
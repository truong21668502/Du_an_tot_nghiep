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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('info')->comment('Loại thông báo');        // danger, warning, info, success
            $table->string('category')->comment('Loại thông báo');                    // stock, voucher, order, expiry
            $table->string('title')->comment('Tiêu đề thông báo, có thể chứa HTML');
            $table->text('message')->comment('Nội dung thông báo, có thể chứa HTML');
            $table->string('link')->nullable()->comment('Link điều hướng khi click vào thông báo');

            // Tránh tạo trùng lặp: ví dụ "expiry_material_5_2026-08-22"
            $table->string('dedup_key')->nullable()->unique(); 

            $table->timestamp('read_at')->nullable()->comment('Thời gian đọc thông báo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};

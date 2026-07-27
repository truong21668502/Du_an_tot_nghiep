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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            // 1. Cho phép title NULL (banner có thể chỉ có ảnh)
            $table->string('title')->nullable(); 
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->string('cloudinary_public_id')->nullable()->comment('Dùng để xóa ảnh cũ trên Cloudinary');
            $table->string('button_text', 100)->nullable();
            $table->string('button_url')->nullable();
            $table->enum('theme', ['light', 'dark'])->default('light');
            
            $table->string('text_align', 10)->default('center');
            
            $table->string('position', 20)->default('center');
            
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['position', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

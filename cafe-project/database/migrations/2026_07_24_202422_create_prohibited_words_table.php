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
    Schema::create('prohibited_words', function (Blueprint $table) {
        $table->id()->comment('Mã từ khóa vi phạm');
        $table->string('word', 100)->unique()->comment('Từ khóa bị cấm');
        $table->boolean('is_active')->default(true)->comment('Trạng thái áp dụng');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prohibited_words');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->onDelete('restrict')
                ->comment('Nguyên liệu được điều chỉnh tồn kho');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict')
                ->comment('Người thực hiện điều chỉnh');

            $table->enum('reason', ['kiem_ke', 'that_thoat', 'het_han', 'hu_hong', 'khac'])
                ->default('kiem_ke')
                ->comment('Lý do điều chỉnh tồn kho');

            $table->decimal('quantity_before', 12, 2)->comment('Tồn kho trước khi điều chỉnh');
            $table->decimal('quantity_after', 12, 2)->comment('Tồn kho sau khi điều chỉnh (số thực tế đếm được)');
            $table->decimal('change_amount', 12, 2)->comment('Chênh lệch = quantity_after - quantity_before, có thể âm');

            $table->string('note', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
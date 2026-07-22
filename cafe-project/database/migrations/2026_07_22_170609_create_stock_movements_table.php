<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->enum('movement_type', ['import', 'export', 'adjust']);
            $table->decimal('quantity_change', 12, 2); // Dương = nhập, Âm = xuất (theo base_unit)
            $table->string('reference_type')->nullable();      // 'order', 'import_receipt', 'stock_adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('moved_at')->useCurrent();

            $table->index(['material_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
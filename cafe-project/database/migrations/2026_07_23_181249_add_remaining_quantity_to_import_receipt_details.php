<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->decimal('remaining_quantity', 12, 2)
                ->default(0)
                ->after('stock_change')
                ->comment('Số lượng của lô này (theo base_unit) chưa bị tiêu thụ — dùng cho FIFO');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->dropColumn('remaining_quantity');
        });
    }
};
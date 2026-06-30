<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->decimal('stock_change', 12, 2)
                ->default(0)
                ->after('unit_price')
                ->comment('Số lượng đã cộng vào quantity_in_stock lúc tạo (theo base_unit). Dùng để revert chính xác khi sửa/huỷ phiếu, không phụ thuộc exchange_rate hiện tại.');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->dropColumn('stock_change');
        });
    }
};
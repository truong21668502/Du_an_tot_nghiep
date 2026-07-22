<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('stock_change')
                ->comment('Hạn sử dụng của lô hàng được nhập trong dòng này');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
    }
};

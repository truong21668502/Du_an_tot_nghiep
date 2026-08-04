<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('expiry_date');
            $table->index(['expiry_date', 'remaining_quantity', 'expired_at'], 'idx_expiry_writeoff');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->dropIndex('idx_expiry_writeoff');
            $table->dropColumn('expired_at');
        });
    }
};
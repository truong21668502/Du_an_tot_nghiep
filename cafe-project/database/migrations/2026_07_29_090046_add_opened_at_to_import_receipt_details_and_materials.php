<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('remaining_quantity');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedInteger('shelf_life_after_opening_days')->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipt_details', function (Blueprint $table) {
            $table->dropColumn('opened_at');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('shelf_life_after_opening_days');
        });
    }
};
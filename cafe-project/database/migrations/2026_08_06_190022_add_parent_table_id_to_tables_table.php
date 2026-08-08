<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_table_id')->nullable()->after('id')->comment('Nếu bị gộp, đây là ID của bàn đích');
            $table->foreign('parent_table_id')->references('id')->on('tables')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign(['parent_table_id']);
            $table->dropColumn('parent_table_id'); 
        });
    }
};

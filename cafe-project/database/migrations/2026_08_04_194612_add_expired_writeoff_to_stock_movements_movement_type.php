<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE stock_movements 
            MODIFY COLUMN movement_type 
            ENUM('import', 'export', 'adjust', 'expired_writeoff') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE stock_movements 
            MODIFY COLUMN movement_type 
            ENUM('import', 'export', 'adjust') NOT NULL
        ");
    }
};
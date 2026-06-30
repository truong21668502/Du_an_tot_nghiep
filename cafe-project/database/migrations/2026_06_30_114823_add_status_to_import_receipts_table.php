<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_receipts', function (Blueprint $table) {
            $table->enum('status', ['active', 'cancelled'])
                ->default('active')
                ->after('note')
                ->comment('Trạng thái phiếu nhập: active = đang hiệu lực, cancelled = đã huỷ');

            $table->timestamp('cancelled_at')->nullable()->after('status');

            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('import_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['status', 'cancelled_at', 'cancel_reason']);
        });
    }
};
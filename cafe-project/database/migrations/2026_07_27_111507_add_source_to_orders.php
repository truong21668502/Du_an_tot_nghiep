<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('source', ['CUSTOMER', 'STAFF'])
                ->default('STAFF')
                ->after('order_type')
                ->comment('Nguồn tạo đơn: CUSTOMER = khách tự đặt qua app, STAFF = nhân viên tạo tại quầy');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
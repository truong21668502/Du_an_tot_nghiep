<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('table_id')
                ->constrained('tables')
                ->onDelete('restrict');

            $table->string('phone_number', 20);
            $table->dateTime('reservation_time');
            $table->integer('guest_count');

            $table->enum('status', ['PENDING', 'CONFIRMED', 'ARRIVED', 'CANCELLED'])
                ->default('PENDING');

            $table->string('note', 255)->nullable();

            $table->index(['table_id', 'reservation_time']);
            $table->index(['reservation_time', 'status']);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
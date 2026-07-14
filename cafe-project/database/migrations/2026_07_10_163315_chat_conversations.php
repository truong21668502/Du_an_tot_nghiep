<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('NULL nếu là khách vãng lai');

            $table->string('session_token', 64)
                ->nullable()
                ->unique()
                ->comment('UUID lưu trong HttpOnly cookie cho guest');

            $table->text('summary')
                ->nullable()
                ->comment('Tóm tắt rolling sau mỗi N tin nhắn');

            $table->unsignedInteger('message_count')
                ->default(0)
                ->comment('Đếm tin nhắn của user — dùng để trigger summary');

            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
            $table->index('session_token');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->onDelete('cascade');

            $table->enum('role', ['user', 'assistant', 'tool'])
                ->comment('Role theo chuẩn OpenAI');

            $table->text('content')
                ->nullable()
                ->comment('NULL khi assistant chỉ trả về tool_calls');

            // Tool calling fields — chỉ dùng khi role = assistant (request) hoặc tool (result)
            $table->json('tool_calls')
                ->nullable()
                ->comment('Array tool calls AI yêu cầu thực thi');

            $table->string('tool_call_id', 100)
                ->nullable()
                ->comment('ID liên kết tool result với tool request');

            $table->string('tool_name', 100)
                ->nullable()
                ->comment('Tên tool đã thực thi');

            $table->json('tool_result')
                ->nullable()
                ->comment('Kết quả trả về từ tool execution');

            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
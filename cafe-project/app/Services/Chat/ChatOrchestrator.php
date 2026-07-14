<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

class ChatOrchestrator
{
    public function __construct(
        private ChatSessionService $sessionService,
        private ChatAiService      $aiService,
        private ChatToolService    $toolService,
        private ChatMessageBuilder $messageBuilder,
        private ChatSummaryService $summaryService,
    ) {}

    /**
     * Entry point duy nhất cho chat.
     *
     * @return array{message: string, conversation_id: int, session_token: string|null}
     */
    public function chat(string $message, array $context, ?int $userId, ?string $sessionToken): array
    {
        // 1. Tìm hoặc tạo conversation
        [$conversation] = $this->sessionService->resolve($userId, $sessionToken);

        // 2. Lưu user message
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $message,
        ]);
        $conversation->increment('message_count');

        // 3. Build messages cho AI (bao gồm system prompt + summary + history)
        $aiMessages = $this->messageBuilder->build($conversation, $context, $userId);

        // 4. Tool definitions (khác nhau tùy auth state)
        $tools = $this->toolService->getDefinitions($userId !== null);

        // Khởi tạo mảng hứng dữ liệu Tool để trả về cho Frontend
        $collectedToolResults = [];

        // 5. Chạy vòng lặp AI → tool → AI
        $finalText = $this->runAiConversation($conversation, $aiMessages, $tools, $userId, $collectedToolResults);

        // 6. Lưu assistant response cuối cùng
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $finalText,
        ]);

        // 7. Cập nhật last_activity
        $conversation->update(['last_activity_at' => now()]);

        // 8. Trigger summary nếu đủ điều kiện (sync — đổi thành Job nếu cần async)
        $this->summaryService->maybeSummarize($conversation->refresh());

        return [
            'message'         => $finalText,
            'conversation_id' => $conversation->id,
            'session_token'   => $conversation->session_token, // null nếu user đã đăng nhập
            'tool_results'    => $collectedToolResults, // Trả về cho Frontend nếu cần
        ];
    }

    // ─── Tool calling loop ──────────────────────────────────────────────────

    private function runAiConversation(
        ChatConversation $conversation,
        array $messages,
        array $tools,
        ?int $userId,
        array &$collectedToolResults
    ): string {
        $maxRounds = config('ai.max_tool_rounds', 3);

        for ($round = 0; $round < $maxRounds; $round++) {
            $aiResponse = $this->aiService->call($messages, $tools);

            // Không có tool calls → trả về text response
            if (empty($aiResponse['tool_calls'])) {
                return $aiResponse['content']
                    ?? 'Xin lỗi, tôi không thể trả lời lúc này. Vui lòng thử lại.';
            }

            // AI yêu cầu gọi tool — lưu assistant message với tool_calls
            ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'content'         => $aiResponse['content'] ?? null,
                'tool_calls'      => $aiResponse['tool_calls'],
            ]);

            // Thêm vào local history để gửi tiếp lên AI
            $messages[] = [
                'role'       => 'assistant',
                'content'    => $aiResponse['content'] ?? null,
                'tool_calls' => $aiResponse['tool_calls'],
            ];

            // Thực thi từng tool và trả kết quả
            foreach ($aiResponse['tool_calls'] as $toolCall) {
                $toolName   = $toolCall['function']['name'] ?? '';
                $toolArgs   = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                $toolCallId = $toolCall['id'] ?? ('call_' . uniqid());

                $result = $this->toolService->execute($toolName, $toolArgs, $userId);


                $collectedToolResults[] = [
                    'tool_name' => $toolName,
                    'data'      => $result
                ];
                // Lưu tool result vào DB (dùng cho history reconstruction)
                ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'role'            => 'tool',
                    'tool_call_id'    => $toolCallId,
                    'tool_name'       => $toolName,
                    'tool_result'     => $result,
                ]);

                // Thêm vào local history — content phải là string JSON
                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $toolCallId,
                    'name'         => $toolName,
                    'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        // Hết vòng lặp — buộc AI trả text (không tools) để tránh loop vô tận
        $forced = $this->aiService->call($messages, []);

        return $forced['content'] ?? 'Đã xử lý xong, vui lòng hỏi tiếp nếu cần.';
    }
}
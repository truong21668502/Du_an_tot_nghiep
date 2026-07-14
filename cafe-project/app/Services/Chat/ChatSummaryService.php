<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class ChatSummaryService
{
    public function __construct(private ChatAiService $aiService) {}

    /**
     * Trigger summary khi message_count chia hết cho threshold.
     * Gọi sau khi đã save cả user message lẫn assistant response.
     *
     * NOTE: Để không block response, có thể chuyển sang dispatch(new SummarizeJob($conversation->id))
     */
    public function maybeSummarize(ChatConversation $conversation): void
    {
        $threshold = config('ai.summary_threshold', 6);

        if ($conversation->message_count <= 0) {
            return;
        }

        if ($conversation->message_count % $threshold !== 0) {
            return;
        }

        $this->createSummary($conversation);
    }

    private function createSummary(ChatConversation $conversation): void
    {
        try {
            // Lấy tất cả user/assistant messages có content (bỏ qua tool messages)
            $messages = ChatMessage::where('conversation_id', $conversation->id)
                ->whereIn('role', ['user', 'assistant'])
                ->whereNotNull('content')
                ->orderBy('id', 'asc')
                ->get();

            if ($messages->count() < 3) {
                return;
            }

            $history = $messages
                ->map(fn ($m) => "[{$m->role}]: {$m->content}")
                ->implode("\n\n");

            $existingSummary = $conversation->summary
                ? "Tóm tắt cũ:\n{$conversation->summary}\n\nTin nhắn mới:\n"
                : "Hội thoại cần tóm tắt:\n";

            $summaryRequest = [
                [
                    'role'    => 'system',
                    'content' => 'Bạn là AI tóm tắt hội thoại. Tóm tắt ngắn gọn (tối đa 150 từ) bao gồm: sở thích khách, sản phẩm đã hỏi, vấn đề chưa giải quyết. Trả lời bằng tiếng Việt.',
                ],
                [
                    'role'    => 'user',
                    'content' => $existingSummary . $history,
                ],
            ];

            // Gọi AI không cần tools cho summary
            $response = $this->aiService->call($summaryRequest, []);

            if (!empty($response['content'])) {
                $conversation->update(['summary' => $response['content']]);
            }
        } catch (\Throwable $e) {
            // Summary lỗi không được break chat — log và bỏ qua
            Log::warning('ChatSummary failed', [
                'conversation_id' => $conversation->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
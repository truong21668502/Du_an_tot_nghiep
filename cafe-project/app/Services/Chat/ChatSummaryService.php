<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class ChatSummaryService
{
    public function __construct(private ChatAiService $aiService) {}

        public function maybeSummarize(ChatConversation $conversation): void
        {
            $threshold = config('ai.summary_threshold', 6);

            $userCount = ChatMessage::where('conversation_id', $conversation->id)
                ->where('role', 'user')
                ->count();

            if ($userCount === 0) {
                return;
            }

            if ($userCount % $threshold !== 0) {
                return;
            }

            $this->createSummary($conversation);
        }

    private function createSummary(ChatConversation $conversation): void
    {
        try {
            // Lấy messages nhưng xử lý cẩn thận với tool calls
            $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->whereIn('role', ['user','assistant'])
                ->orderBy('id', 'asc')
                ->get();

            if ($messages->count() < 3) {
                return;
            }

            // Chuyển đổi messages sang text thuần túy, bỏ qua technical details của tool calls
            $historyText = $this->formatMessagesToText($messages);

            $existingSummary = $conversation->summary
                ? "Tóm tắt cũ:\n{$conversation->summary}\n\nTin nhắn mới:\n"
                : "Hội thoại cần tóm tắt:\n";

            // Sử dụng messages format đơn giản, không có tool calls
            $summaryRequest = [
                [
                    'role'    => 'system',
                    'content' => 'Bạn là AI tóm tắt hội thoại. Tóm tắt ngắn gọn (tối đa 150 từ) bao gồm: sở thích khách, sản phẩm đã hỏi, vấn đề chưa giải quyết. Trả lời bằng tiếng Việt.',
                ],
                [
                    'role'    => 'user',
                    'content' => $existingSummary . $historyText,
                ],
            ];

            // Gọi AI không cần tools cho summary
            $response = $this->aiService->call($summaryRequest, []);

            if (!empty($response['content'])) {
                $conversation->update(['summary' => $response['content']]);
                Log::warning("Đã tóm tắt đoạn chat");
            }
        } catch (\Throwable $e) {
            Log::warning('ChatSummary failed', [
                'conversation_id' => $conversation->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format messages thành text thuần túy, xử lý đặc biệt cho tool calls
     */
    private function formatMessagesToText($messages): string
    {
        $text = '';
        $currentToolCalls = [];

        foreach ($messages as $message) {
        $data = $message->content;

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

            switch ($message->role) {
                case 'user':
                    // Nếu là text thông thường
                    if (is_string($data)) {
                        $text .= "[user]: {$data}\n";
                    } elseif (isset($data['text'])) {
                        $text .= "[user]: {$data['text']}\n";
                    }
                    break;

                case 'assistant':
                    // Nếu assistant message có content text
                    if (is_string($data)) {
                        $text .= "[assistant]: {$data}\n";
                    } elseif (isset($data['content']) && is_string($data['content'])) {
                        $text .= "[assistant]: {$data['content']}\n";
                    }
                    
                    // Nếu có tool calls, ghi nhận nhưng không đưa vào text
                    if (isset($data['tool_calls'])) {
                        $currentToolCalls = $data['tool_calls'];
                        foreach ($currentToolCalls as $toolCall) {
                            $functionName = $toolCall['function']['name'] ?? 'unknown';
                            $text .= "[assistant gọi function: {$functionName}]\n";
                        }
                    }
                    break;

                case 'tool':
                    // Tóm tắt kết quả tool thay vì đưa raw data
                    if (isset($data['name'])) {
                        $toolName = $data['name'];
                        $toolResult = $data['content'] ?? '';
                        
                        // Tạo mô tả ngắn gọn về kết quả tool
                        $summary = $this->summarizeToolResult($toolName, $toolResult);
                        $text .= "[kết quả từ {$toolName}]: {$summary}\n";
                    }
                    break;
            }
        }

        return trim($text);
    }

    /**
     * Tạo mô tả ngắn gọn về kết quả tool call
     */
    private function summarizeToolResult(string $toolName, $result): string
    {
        if (is_string($result)) {
            // Giới hạn độ dài
            return mb_substr($result, 0, 200);
        }
        
        if (is_array($result)) {
            $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);
            return mb_substr($resultJson, 0, 200);
        }
        
        return 'có kết quả';
    }
}
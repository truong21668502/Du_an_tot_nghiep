<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Tạo câu trả lời gợi ý cho bình luận của khách
     */
    public function generateReviewReply(string $userName, int $rating, string $comment, string $productName = ''): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash'); // Ví dụ: gemini-1.5-flash hoặc gemini-1.5-pro
        $maxTokens   = config('ai.max_tokens', 1024);
        $temperature = config('ai.temperature', 0.7);

        // URL endpoint chuẩn của Gemini REST API
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Prompt hệ thống
        $systemPrompt = "Bạn là Quản lý quán cà phê Nắng Coffee thông minh, lịch sự và chu đáo. "
            . "Nhiệm vụ của bạn là viết một câu phản hồi ngắn gọn, chân thành cho đánh giá của khách hàng. "
            . "Yêu cầu:\n"
            . "- Văn phong F&B Việt Nam thân thiện, lịch sự, ngắn dưới 80 từ.\n"
            . "- Nếu 1-2 sao: Chân thành xin lỗi, không bao biện, hứa cải thiện chất lượng.\n"
            . "- Nếu 4-5 sao: Cảm ơn nhiệt tình, hẹn gặp lại khách.\n"
            . "- Không tự xưng là AI hay Bot.";

        // Nội dung dữ liệu gửi lên
        $userPrompt = "Thông tin bình luận:\n"
            . "- Khách hàng: {$userName}\n"
            . "- Đánh giá: {$rating}/5 sao\n"
            . ($productName ? "- Món ăn/uống: {$productName}\n" : "")
            . "- Nội dung: \"{$comment}\"\n"
            . "Hãy viết câu trả lời phù hợp.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                // 1. Cấu hình Prompt Hệ Thống
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt]
                    ]
                ],
                // 2. Nội dung hội thoại (role bắt buộc là 'user')
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt]
                        ]
                    ]
                ],
                // 3. Cấu hình Tham số
                'generationConfig' => [
                    'temperature'     => (float) $temperature,
                    'maxOutputTokens' => (int) $maxTokens,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Lấy kết quả từ cấu trúc trả về của Gemini
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            // Log lỗi chi tiết nếu API không trả về 200 OK
            Log::error('Gemini API Error:', [
                'status' => $response->status(), 
                'body'   => $response->body()
            ]);
            
            return 'Cảm ơn bạn đã phản hồi! Quán đã ghi nhận và sẽ nâng cao chất lượng dịch vụ hơn nữa.';

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return null;
        }
    }
}
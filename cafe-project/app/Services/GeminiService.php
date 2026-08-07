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
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = config('ai.max_tokens', 1024);
        $temperature = config('ai.temperature', 0.7);

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = "Bạn là Quản lý quán cà phê Nắng Coffee thông minh, lịch sự và chu đáo.\n"
            . "Nhiệm vụ: Viết câu phản hồi ngắn gọn, chân thành cho đánh giá của khách hàng.\n"
            . "QUY TẮC:\n"
            . "- CHỈ trả lời phản hồi đánh giá của khách hàng về Nắng Coffee.\n"
            . "- Văn phong F&B Việt Nam thân thiện, lịch sự, ngắn dưới 80 từ.\n"
            . "- Nếu 1-2 sao: Chân thành xin lỗi, không bao biện, hứa cải thiện chất lượng.\n"
            . "- Nếu 4-5 sao: Cảm ơn nhiệt tình, hẹn gặp lại khách.\n"
            . "- Không tự xưng là AI hay Bot.\n"
            . "- Sử dụng emoji phù hợp ở đầu mỗi câu/gạch đầu dòng.";

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
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt]
                    ]
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => (float) $temperature,
                    'maxOutputTokens' => (int) $maxTokens,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

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

    /**
     * Phân tích dữ liệu kinh doanh và đưa ra gợi ý chiến lược
     */
    public function analyzeBusinessStrategy(array $salesData): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = config('ai.max_tokens', 600);
        $temperature = config('ai.temperature', 0.7);

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = "Bạn là Chuyên gia tư vấn chiến lược kinh doanh F&B cho quán Nắng Coffee.\n"
            . "Nhiệm vụ: Phân tích số liệu thực tế được cung cấp để giải quyết 3 vấn đề:\n"
            . "1. Khung giờ nào khách mua hàng nhiều nhất?\n"
            . "2. Sản phẩm nào bán chạy nhất (và sản phẩm nào ế ẩm)?\n"
            . "3. Gợi ý chiến lược kinh doanh tiếp theo cho Nắng Coffee.\n"
            . "QUY TẮC:\n"
            . "- CHỈ phân tích dữ liệu F&B của Nắng Coffee được cung cấp bên dưới.\n"
            . "- Trình bày trọng tâm, ngắn gọn dưới 300 từ, có gạch đầu dòng và emoji sinh động.";

        $dataJson = json_encode($salesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userPrompt = "Dưới đây là dữ liệu thống kê đơn hàng từ hệ thống quán Nắng Coffee:\n"
            . "```json\n{$dataJson}\n```\n\n"
            . "Hãy phân tích giúp tôi các vấn đề trên và đưa ra chiến lược kinh doanh tiếp theo cho quán.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userPrompt]]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => (float) $temperature,
                    'maxOutputTokens' => (int) $maxTokens,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini Business Analysis Error:', [
                'status' => $response->status(), 
                'body'   => $response->body()
            ]);
            
            return 'Hiện tại hệ thống AI đang bận, không thể phân tích dữ liệu lúc này. Vui lòng thử lại sau.';

        } catch (\Exception $e) {
            Log::error('Gemini Business Analysis Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Trò chuyện với Trợ lý AI (Có giới hạn phạm vi chặt chẽ)
     */
    public function chatWithAi(array $chatHistory, string $currentQuestion, array $salesData): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = 400;
        $temperature = 0.5; // Giảm temperature xuống 0.5 để AI tuân thủ luật nghiêm ngặt hơn

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // System Instruction có bổ sung Guardrails (Phạm vi)
        $systemPrompt = "Bạn là Trợ lý AI chuyên trách quản lý và vận hành cho dự án Nắng Coffee.\n\n"
            . "=== PHẠM VI HOẠT ĐỘNG (BẮT BUỘC TỦY THỦ) ===\n"
            . "1. Bạn CHỈ ĐƯỢC PHÉP trả lời các câu hỏi liên quan đến:\n"
            . "   - Quán Nắng Coffee (thực đơn, đồ uống, khuyến mãi, hoạt động của quán).\n"
            . "   - Dữ liệu kinh doanh, doanh thu, đơn hàng F&B được cung cấp bên dưới.\n"
            . "   - Kiến thức vận hành, tiếp thị và tư vấn chiến lược ngành F&B / Quán cà phê.\n\n"
            . "2. TỪ CHỐI TẤT CẢ CÁC CÂU HỎI NGOÀI PHẠM VI:\n"
            . "   - Nếu người dùng hỏi về các chủ đề không liên quan (ví dụ: lập trình, giải toán, thời tiết, tin tức xã hội, lịch sử thế giới, tư vấn tình cảm, công nghệ chung, trò chơi...):\n"
            . "   - Hãy TỪ CHỐI LỊCH SỰ và nhắc người dùng quay lại chủ đề Nắng Coffee.\n"
            . "   - Mẫu câu từ chối tham khảo: \"Dạ, tôi là Trợ lý AI chuyên trách của Nắng Coffee nên chỉ có thể hỗ trợ các vấn đề liên quan đến quán, thực đơn, khách hàng và chiến lược F&B. Bạn có cần hỗ trợ gì về hoạt động của quán không ạ? ☕\"\n\n"
            . "=== DỮ LIỆU KINH DOANH CỦA NẮNG COFFEE ===\n"
            . json_encode($salesData, JSON_UNESCAPED_UNICODE);

        $contents = [];
        foreach ($chatHistory as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }
        
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $currentQuestion]]
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($apiUrl, [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => (float) $temperature,
                    'maxOutputTokens' => (int) $maxTokens,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            return 'Xin lỗi, hệ thống AI đang bận. Vui lòng thử lại sau.';
        } catch (\Exception $e) {
            Log::error('Gemini Chat Exception: ' . $e->getMessage());
            return 'Lỗi kết nối đến máy chủ AI.';
        }
    }
}
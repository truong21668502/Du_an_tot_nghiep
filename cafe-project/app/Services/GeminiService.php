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
            . "- Không tự xưng là AI hay Bot."
            . "BẮT BUỘC sử dụng các icon (emoji) phù hợp ở đầu mỗi gạch đầu dòng hoặc tiêu đề để sinh động hơn (ví dụ: ⏰ cho giờ giấc, ☕ cho sản phẩm, 📊 cho doanh thu, 💡 cho chiến lược).";

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

    /**
     * Phân tích dữ liệu kinh doanh và đưa ra gợi ý chiến lược
     */
    public function analyzeBusinessStrategy(array $salesData): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = config('ai.max_tokens', 600); // Tăng token vì câu trả lời chiến lược cần dài hơn
        $temperature = config('ai.temperature', 0.7);

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Prompt hệ thống định hình vai trò chuyên gia F&B
        $systemPrompt = "Bạn là một Chuyên gia tư vấn chiến lược kinh doanh F&B (Quán cà phê) cực kỳ nhạy bén và am hiểu thị trường Việt Nam. "
            . "Nhiệm vụ của bạn là phân tích số liệu thực tế được cung cấp để trả lời các câu hỏi: "
            . "1. Khung giờ nào khách mua hàng nhiều nhất?\n"
            . "2. Sản phẩm nào bán chạy nhất (và sản phẩm nào ế ẩm)?\n"
            . "3. Gợi ý chiến lược kinh doanh tiếp theo (ví dụ: chương trình khuyến mãi giờ vàng, combo sản phẩm, đẩy mạnh món nào, cắt giảm chi phí ra sao).\n"
            . "Yêu cầu: Trình bày trọng tâm, rõ ràng, mạch lạc, thực tế, có gạch đầu dòng và văn phong chuyên nghiệp. Nội dung trả lời không dài quá 300 từ."
            . "BẮT BUỘC sử dụng các icon (emoji) phù hợp ở đầu mỗi gạch đầu dòng hoặc tiêu đề để sinh động hơn (ví dụ: ⏰ cho giờ giấc, ☕ cho sản phẩm, 📊 cho doanh thu, 💡 cho chiến lược).";

        // Chuyển dữ liệu mảng thống kê từ PHP thành JSON để AI đọc
        $dataJson = json_encode($salesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userPrompt = "Dưới đây là dữ liệu thống kê đơn hàng từ hệ thống quán cà phê hiện tại:\n"
            . "```json\n{$dataJson}\n```\n\n"
            . "Hãy phân tích giúp tôi các vấn đề: Khung giờ nào khách mua nhiều nhất? Sản phẩm nào bán chạy nhất? "
            . "Và dựa trên dữ liệu này, chiến lược kinh doanh tiếp theo cho quán là gì?";

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

    public function chatWithAi(array $chatHistory, string $currentQuestion, array $salesData): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = 400; // Đủ ngắn gọn cho bong bóng chat
        $temperature = 0.7;
    
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
        $systemPrompt = "Bạn là Trợ lý AI chiến lược F&B cho quán Nắng Coffee. "
            . "Nhiệm vụ: Trả lời ngắn gọn, súc tích, đi thẳng vào trọng tâm, dùng gạch đầu dòng khi cần. "
            . "Dưới đây là dữ liệu kinh doanh hiện tại của quán (để bạn tham khảo khi trả lời):\n"
            . json_encode($salesData, JSON_UNESCAPED_UNICODE);
    
        // Xây dựng cấu trúc mảng contents chứa lịch sử chat
        $contents = [];
        foreach ($chatHistory as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }
        
        // Thêm câu hỏi mới nhất của người dùng vào cuối
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
            return 'Lỗi kết nối đến máy chủ AI.';
        }
    }
}
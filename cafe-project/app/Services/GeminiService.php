<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProhibitedWord;

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

        //lấy dữ liệu từ khoá vi phạm để đảm bảo AI không trả lời ngoài phạm vi
        $prohibitedWords = ProhibitedWord::pluck('word')->toArray();

        $systemPrompt = "Bạn là Quản lý quán cà phê Nắng Coffee thông minh, lịch sự và chu đáo.\n"
            . "Nhiệm vụ: Viết câu phản hồi ngắn gọn, chân thành cho đánh giá của khách hàng.\n"
            . "QUY TẮC:\n"
            . "- CHỈ trả lời phản hồi đánh giá của khách hàng về Nắng Coffee.\n"
            . "- Văn phong F&B Việt Nam thân thiện, lịch sự, ngắn dưới 80 từ.\n"
            . "- Nếu 1-2 sao: Chân thành xin lỗi, không bao biện, hứa cải thiện chất lượng.\n"
            . "- Nếu 4-5 sao: Cảm ơn nhiệt tình, hẹn gặp lại khách.\n"
            . "- Không tự xưng là AI hay Bot.\n"
            . "- Sử dụng emoji phù hợp ở đầu mỗi câu/gạch đầu dòng."
            . "- KHÔNG sử dụng các từ ngữ vi phạm: " . implode(', ', $prohibitedWords) . ".\n"
            . "- KHÔNG trả lời các câu hỏi ngoài phạm vi quán cà phê Nắng Coffee.\n";

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

        //lấy dữ liệu từ khoá vi phạm để đảm bảo AI không trả lời ngoài phạm vi
        $prohibitedWords = ProhibitedWord::pluck('word')->toArray();

        $systemPrompt = "Bạn là Chuyên gia tư vấn chiến lược kinh doanh F&B cho quán Nắng Coffee.\n"
            . "Nhiệm vụ: Phân tích số liệu thực tế được cung cấp để giải quyết 3 vấn đề:\n"
            . "1. Khung giờ nào khách mua hàng nhiều nhất?\n"
            . "2. Sản phẩm nào bán chạy nhất (và sản phẩm nào ế ẩm)?\n"
            . "3. Gợi ý chiến lược kinh doanh tiếp theo cho Nắng Coffee.\n"
            . "QUY TẮC:\n"
            . "- CHỈ phân tích dữ liệu F&B của Nắng Coffee được cung cấp bên dưới.\n"
            . "- KHÔNG sử dụng các từ ngữ vi phạm: " . implode(', ', $prohibitedWords) . ".\n"
            . "- Trình bày trọng tâm, ngắn gọn dưới 300 từ, có gạch đầu dòng và emoji sinh động.";

        // Chuyển đổi dữ liệu doanh số thành JSON để gửi cho AI
        $dataJson = json_encode($salesData, JSON_UNESCAPED_UNICODE);

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
        // Tạo ngữ cảnh cho AI dựa trên dữ liệu kinh doanh hiện tại
        $apiKey      = config('ai.ai_key');

        // Sử dụng model Gemini-1.5-flash để có khả năng tuân thủ luật nghiêm ngặt hơn
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        
        // Giới hạn số lượng token để tránh trả lời quá dài
        $maxTokens   = 400;

        // Giảm temperature xuống 0.5 để AI tuân thủ luật nghiêm ngặt hơn
        $temperature = 0.5;

        // URL API của Gemini
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        //lấy dữ liệu từ khoá vi phạm để đảm bảo AI không trả lời ngoài phạm vi
        $prohibitedWords = ProhibitedWord::pluck('word')->toArray();

        // Tạo prompt hệ thống với hướng dẫn chi tiết và dữ liệu kinh doanh
        $systemPrompt = "Bạn là Trợ lý AI chuyên trách quản lý và vận hành cho dự án Nắng Coffee.\n\n"
            . "=== PHẠM VI HOẠT ĐỘNG (BẮT BUỘC TỦY THỦ) ===\n"
            . "1. Bạn CHỈ ĐƯỢC PHÉP trả lời các câu hỏi liên quan đến:\n"
            . "   - Quán Nắng Coffee (thực đơn, đồ uống, khuyến mãi, hoạt động của quán).\n"
            . "   - Dữ liệu kinh doanh, đơn hàng F&B được cung cấp bên dưới.\n"
            . "   - Kiến thức vận hành, tiếp thị và tư vấn chiến lược ngành F&B / Quán cà phê.\n\n"
            . "2. TỪ CHỐI TẤT CẢ CÁC CÂU HỎI NGOÀI PHẠM VI:\n"
            . "   - Nếu người dùng hỏi về các chủ đề không liên quan (ví dụ: lập trình, giải toán, thời tiết, tin tức xã hội, lịch sử thế giới, tư vấn tình cảm, công nghệ chung, trò chơi...):\n"
            . "   - Hãy TỪ CHỐI LỊCH SỰ và nhắc người dùng quay lại chủ đề Nắng Coffee.\n"
            . "   - Mẫu câu từ chối tham khảo: \"Dạ, tôi là Trợ lý AI chuyên trách của Nắng Coffee nên chỉ có thể hỗ trợ các vấn đề liên quan đến quán, thực đơn, khách hàng và chiến lược F&B. Bạn có cần hỗ trợ gì về hoạt động của quán không ạ? ☕\"\n\n"
            . "- KHÔNG sử dụng các từ ngữ vi phạm: " . implode(', ', $prohibitedWords) . ".\n"
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

    /**
     * Trò chuyện với Trợ lý AI dành cho Nhân viên (Staff)
     */
    public function chatWithStaffAi(array $chatHistory, string $currentQuestion, array $staffContext): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = 400;
        $temperature = 0.5;

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = "Bạn là Trợ lý AI dành riêng cho Nhân viên (Staff) của quán Nắng Coffee.\n\n"
            . "=== PHẠM VI HOẠT ĐỘNG (BẮT BUỘC TUÂN THỦ) ===\n"
            . "1. Bạn CHỈ ĐƯỢC PHÉP hỗ trợ nhân viên về:\n"
            . "   - Tình trạng các bàn hiện tại (bàn nào trống, bàn nào đang có khách).\n"
            . "   - Trạng thái các đơn hàng hiện tại (đơn nào đang chờ xử lý, đơn nào đang pha chế).\n"
            . "   - Hướng dẫn các nghiệp vụ trên hệ thống (cách tạo đơn mới, cách gộp bàn, tách bàn, xem chi tiết hóa đơn, v.v.).\n"
            . "   - Thực đơn của quán (các món ăn, đồ uống).\n\n"
            . "2. TỪ CHỐI CÁC CÂU HỎI NGOÀI PHẠM VI HOẶC BẢO MẬT:\n"
            . "   - TỪ CHỐI tuyệt đối các câu hỏi về doanh thu, lợi nhuận, chiến lược kinh doanh của quán (vì đây là dữ liệu bảo mật chỉ Quản lý mới được xem).\n"
            . "   - TỪ CHỐI các câu hỏi không liên quan đến quán (toán học, lập trình, xã hội, v.v.).\n"
            . "   - Mẫu từ chối tham khảo: \"Dạ, em là Trợ lý của Nhân viên nên chỉ có quyền hỗ trợ xem trạng thái bàn, đơn hàng và các nghiệp vụ bán hàng thôi ạ. Các thông tin khác em không có quyền truy cập nhé!\"\n\n"
            . "=== DỮ LIỆU NGỮ CẢNH HIỆN TẠI (Dành riêng cho Nhân viên) ===\n"
            . json_encode($staffContext, JSON_UNESCAPED_UNICODE);

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
            $response = Http::timeout(60)->withHeaders(['Content-Type' => 'application/json'])->post($apiUrl, [
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
            Log::error('Gemini Staff Chat Exception: ' . $e->getMessage());
            return 'Lỗi kết nối đến máy chủ AI.';
        }
    }

    /**
     * Trò chuyện với Trợ lý AI dành cho Pha chế (Barista)
     */
    public function chatWithBaristaAi(array $chatHistory, string $currentQuestion, array $baristaContext): ?string
    {
        $apiKey      = config('ai.ai_key');
        $model       = config('ai.models.primary', 'gemini-1.5-flash');
        $maxTokens   = 400;
        $temperature = 0.5;

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = "Bạn là Trợ lý AI dành riêng cho bộ phận Pha chế (Barista) của quán Nắng Coffee.\n\n"
            . "=== PHẠM VI HOẠT ĐỘNG (BẮT BUỘC TUÂN THỦ) ===\n"
            . "1. Bạn CHỈ ĐƯỢC PHÉP hỗ trợ pha chế về:\n"
            . "   - Số lượng và thông tin chi tiết các món đang chờ pha (PENDING) hoặc đang pha (PREPARING).\n"
            . "   - Hướng dẫn công thức pha chế của các đồ uống có trong danh sách yêu cầu.\n"
            . "   - Kiểm tra tình trạng nguyên liệu kho (số lượng, hạn sử dụng).\n"
            . "   - Hỗ trợ nghiệp vụ liên quan đến quầy pha chế.\n\n"
            . "2. TỪ CHỐI CÁC CÂU HỎI NGOÀI PHẠM VI HOẶC BẢO MẬT:\n"
            . "   - TỪ CHỐI các câu hỏi về doanh thu, thanh toán, quản lý bàn (đây là việc của nhân viên thu ngân/phục vụ).\n"
            . "   - TỪ CHỐI các câu hỏi không liên quan đến quán.\n"
            . "   - Mẫu từ chối tham khảo: \"Dạ, em là Trợ lý Pha chế nên chỉ có thể hỗ trợ anh/chị về các đơn hàng đồ uống cần làm, công thức và nguyên liệu thôi ạ!\"\n\n"
            . "=== DỮ LIỆU NGỮ CẢNH HIỆN TẠI (Dành riêng cho Pha chế) ===\n"
            . json_encode($baristaContext, JSON_UNESCAPED_UNICODE);

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
            $response = Http::timeout(60)->withHeaders(['Content-Type' => 'application/json'])->post($apiUrl, [
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
            Log::error('Gemini Barista Chat Exception: ' . $e->getMessage());
            return 'Lỗi kết nối đến máy chủ AI.';
        }
    }


}
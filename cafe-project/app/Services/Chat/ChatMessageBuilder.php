<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

class ChatMessageBuilder
{
    /**
     * Build toàn bộ messages array theo chuẩn OpenAI:
     * [system_prompt] → [summary] → [last N messages từ DB]
     */
    public function build(ChatConversation $conversation, array $context, ?int $userId): array
    {
        $messages = [];

        // 1. System prompt chính
        $messages[] = [
            'role'    => 'system',
            'content' => $this->buildSystemPrompt($userId, $context),
        ];

        // 2. Rolling summary (nếu có) — thay thế lịch sử cũ để giảm token
        if ($conversation->summary) {
            $messages[] = [
                'role'    => 'system',
                'content' => "Tóm tắt cuộc trò chuyện trước:\n{$conversation->summary}",
            ];
        }

        // 3. Lịch sử gần nhất từ DB (last N, giữ nguyên thứ tự chronological)
        // Dùng DESC + limit để lấy N gần nhất, rồi sortBy để đảo lại thứ tự đúng
        $history = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->limit(config('ai.history_limit', 20))
            ->get()
            ->sortBy('id')
            ->values();

        foreach ($history as $msg) {
            $messages[] = $this->formatForAi($msg);
        }

        return $messages;
    }

private function buildSystemPrompt(?int $userId, array $context): string
{
    $authStatus = $userId
        ? "✅ Khách đã đăng nhập (user_id: {$userId})"
        : "❌ Khách chưa đăng nhập — không trả về thông tin cá nhân";

    $contextBlock = !empty($context)
        ? "\n\nNgữ cảnh trang hiện tại của khách:\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : '';

    return <<<PROMPT
Bạn là trợ lý AI của quán cà phê (Nắng coffee), hỗ trợ khách hàng tư vấn menu và đơn hàng.

[KHẢ NĂNG]
- Tư vấn sản phẩm, giá cả, danh mục (dùng tool search_products, get_product_detail)
- Giới thiệu chương trình khuyến mãi (dùng tool get_active_coupons)  
- Xem lịch sử đơn hàng (chỉ khi đã đăng nhập — dùng tool get_my_orders, get_order_detail)

[QUY TẮC BẮT BUỘC - VI PHẠM SẼ BỊ PHẠT]
1. NGÔN NGỮ: Luôn trả lời bằng tiếng Việt, ngắn gọn, lịch sự và thân thiện.
2. ĐỊNH DẠNG: Chỉ trả về VĂN BẢN THUẦN TÚY (Plain text). TUYỆT ĐỐI KHÔNG dùng Markdown (không viết hoa đậm **, không gạch đầu dòng -, không gắn link).
3. NGUỒN THÔNG TIN: Chỉ sử dụng dữ liệu từ tool, không tự bịa thông tin.

[QUY TẮC HIỂN THỊ DỮ LIỆU TỪ TOOL - QUAN TRỌNG NHẤT]
- TUYỆT ĐỐI KHÔNG LIỆT KÊ, KHÔNG NHẮC LẠI, KHÔNG TÓM TẮT tên sản phẩm, tên coupon, danh mục hoặc mã đơn hàng mà tool đã trả về. Hệ thống giao diện (UI) đã tự động render các dữ liệu này cho khách nhìn thấy. Việc bạn liệt kê lại sẽ làm trùng lặp thông tin.
- Nếu tool trả về NHIỀU kết quả (>= 2): Bạn CHỈ ĐƯỢC PHÉP thông báo số lượng tìm thấy và mời khách xem hoặc chọn ở danh sách phía dưới.
- Nếu tool trả về MỘT kết quả duy nhất: Bạn có thể tư vấn, mô tả ngắn gọn về sản phẩm đó nhưng không dùng định dạng Markdown.

[VÍ DỤ ĐỂ LÀM THEO]
- Đúng khi tool trả về nhiều sản phẩm: "Nắng coffee có 3 loại trà sữa ngon lắm ạ, bạn xem danh sách phía dưới và chọn món mình thích nhé!"
- Sai (TUYỆT ĐỐI CẤM): "Nắng coffee có các loại trà sữa sau: **Trà sữa truyền thống**, **Trà sữa thái**..."

Trạng thái hệ thống hiện tại: {$authStatus}{$contextBlock}
PROMPT;
}


    /**
     * Convert ChatMessage model → định dạng OpenAI API message
     */
    private function formatForAi(ChatMessage $msg): array
    {
        // Tool result message
        if ($msg->role === 'tool') {
            return [
                'role'         => 'tool',
                'tool_call_id' => $msg->tool_call_id,
                'name'         => $msg->tool_name,
                'content'      => json_encode($msg->tool_result, JSON_UNESCAPED_UNICODE),
            ];
        }

        // Assistant message có tool_calls (chưa có content text)
        if ($msg->role === 'assistant' && !empty($msg->tool_calls)) {
            return [
                'role'       => 'assistant',
                'content'    => $msg->content,     // thường là null
                'tool_calls' => $msg->tool_calls,  // cast array từ JSON
            ];
        }

        // User hoặc assistant text thuần
        return [
            'role'    => $msg->role,
            'content' => $msg->content ?? '',
        ];
    }
}
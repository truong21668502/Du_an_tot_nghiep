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

        // System prompt
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildSystemPrompt($userId, $context),
        ];

        // Summary
        if ($conversation->summary) {
            $messages[] = [
                'role' => 'system',
                'content' => "Tóm tắt cuộc trò chuyện trước:\n{$conversation->summary}",
            ];
        }

        $history = ChatMessage::where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit(config('ai.history_limit', 20))
            ->get()
            ->sortBy('id')
            ->values();

        foreach ($history as $msg) {

            // BỎ toàn bộ tool cũ
            if ($msg->role === 'tool') {
                continue;
            }

            // BỎ assistant chỉ dùng để gọi tool
            if ($msg->role === 'assistant' && !empty($msg->tool_calls)) {
                continue;
            }

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
    Bạn là trợ lý AI thân thiện của Nắng Coffee, hỗ trợ khách hàng tư vấn menu, chương trình khuyến mãi và đơn hàng.

    [TÍNH NĂNG & CÔNG CỤ]
    - Menu & Sản phẩm: Dùng search_products, get_product_detail.
    - Khuyến mãi: Dùng get_active_coupons.
    - Đơn hàng (Cần đăng nhập): Dùng get_my_orders, get_order_detail.
    - Trạng thái hiện tại: {$authStatus}{$contextBlock}

    [QUY TẮC HIỂN THỊ - QUAN TRỌNG]
    1. Ngôn ngữ: Tiếng Việt, ngắn gọn, lịch sự. Định dạng Markdown rõ ràng, dễ đọc.
    2. Không trùng lặp: Tuyệt đối không liệt kê lại tên sản phẩm, mã đơn hàng hay coupon mà tool đã trả về (giao diện UI sẽ tự động hiển thị danh sách này).
    3. Phản hồi theo số lượng kết quả từ Tool:
    - Nếu >= 2 kết quả: Chỉ thông báo số lượng tìm thấy và mời khách tự xem/chọn ở danh sách phía dưới.
    - Nếu có 1 kết quả duy nhất: Mô tả ngắn gọn về sản phẩm/thông tin đó.
    [VÍ DỤ]
    - Đúng: "Nắng Coffee tìm thấy 3 loại trà trái cây thanh mát, bạn xem danh sách phía dưới và chọn món mình thích nhé!"
    - Sai (Cấm): "Nắng Coffee có các món: Trà đào, Trà vải, Trà dâu...
PROMPT;
}


    /**
     * Convert ChatMessage model → định dạng OpenAI API message
     */
private function formatForAi(ChatMessage $msg): array
{
    return [
        'role'    => $msg->role,
        'content' => $msg->content ?? '',
    ];
}
}
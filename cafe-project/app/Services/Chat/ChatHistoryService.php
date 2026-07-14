<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatHistoryService
{
    /**
     * Lấy lịch sử chat của conversation
     */
    public function getHistory(ChatConversation $conversation, array $options = []): LengthAwarePaginator
    {
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;

        $query = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc');

        return $query->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);
    }

    /**
     * Lấy conversation của user/guest
     */
    public function getConversation(?int $userId, ?string $sessionToken, int $conversationId): ?ChatConversation
    {
        $query = ChatConversation::where('id', $conversationId);

        if ($userId) {
            $query->where('user_id', $userId);
        } else if ($sessionToken) {
            $query->whereNull('user_id')->where('session_token', $sessionToken);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * Lấy danh sách conversations của user/guest
     */
    public function getConversations(?int $userId, ?string $sessionToken, int $limit = 10): array
    {
        $query = ChatConversation::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else if ($sessionToken) {
            $query->whereNull('user_id')->where('session_token', $sessionToken);
        } else {
            return [];
        }

        return $query->orderBy('last_activity_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($conv) => [
                'id' => $conv->id,
                'summary' => $conv->summary ?? 'Cuộc trò chuyện ' . $conv->created_at->format('d/m/Y H:i'),
                'message_count' => $conv->message_count,
                'last_activity_at' => $conv->last_activity_at?->format('Y-m-d H:i:s'),
                'created_at' => $conv->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    /**
     * Xóa một hoặc nhiều tin nhắn
     */
    public function deleteMessages(ChatConversation $conversation, array $messageIds): array
    {
        $deleted = [];
        $errors = [];

        foreach ($messageIds as $messageId) {
            $message = ChatMessage::where('conversation_id', $conversation->id)
                ->where('id', $messageId)
                ->first();

            if (!$message) {
                $errors[] = "Không tìm thấy tin nhắn ID: {$messageId}";
                continue;
            }

            // Không cho phép xóa tin nhắn tool
            if ($message->role === 'tool') {
                $errors[] = "Không thể xóa tin nhắn hệ thống (tool) ID: {$messageId}";
                continue;
            }

            try {
                $message->delete();
                $deleted[] = $messageId;
                
                // Giảm message_count
                $conversation->decrement('message_count');
            } catch (\Exception $e) {
                $errors[] = "Lỗi khi xóa tin nhắn ID: {$messageId} - {$e->getMessage()}";
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors,
        ];
    }

    /**
     * Xóa toàn bộ tin nhắn trong conversation
     */
    public function deleteAllMessages(ChatConversation $conversation): array
    {
        try {
            // Chỉ xóa tin nhắn user và assistant, giữ lại tool messages
            $deletedCount = ChatMessage::where('conversation_id', $conversation->id)
                ->whereIn('role', ['user', 'assistant'])
                ->delete();

            // Reset message_count
            $conversation->update([
                'message_count' => ChatMessage::where('conversation_id', $conversation->id)->count(),
                'summary' => null,
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Xóa toàn bộ conversation
     */
    public function deleteConversation(ChatConversation $conversation): array
    {
        try {
            // Xóa tất cả messages trước
            ChatMessage::where('conversation_id', $conversation->id)->delete();
            
            // Xóa conversation
            $conversation->delete();

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Tạo summary mới cho conversation (re-summarize)
     */
    public function regenerateSummary(ChatConversation $conversation): ?string
    {
        if ($conversation->message_count < 3) {
            return null;
        }

        $summaryService = app(ChatSummaryService::class);
        $summaryService->createSummary($conversation);

        return $conversation->refresh()->summary;
    }

    
}
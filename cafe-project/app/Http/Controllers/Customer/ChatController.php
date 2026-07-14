<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ChatRequest;
use App\Http\Requests\Customer\GetChatHistoryRequest;
use App\Http\Requests\Customer\DeleteChatMessageRequest;
use App\Services\Chat\ChatOrchestrator;
use App\Services\Chat\ChatSessionService;
use App\Services\Chat\ChatHistoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(
        private ChatOrchestrator $orchestrator,
        private ChatHistoryService $historyService
    ) {}

    /**
     * Endpoint duy nhất cho chat.
     * Frontend gọi: POST /chat với { message, context }
     */
    public function message(ChatRequest $request)
    {
        $userId = Auth::id();
        $sessionToken = $request->cookie(ChatSessionService::COOKIE_NAME);

        try {
            $result = $this->orchestrator->chat(
                message: $request->validated('message'),
                context: $request->validated('context', []),
                userId: $userId,
                sessionToken: $sessionToken,
            );

            $response = response()->json([
                'success' => true,
                'message' => $result['message'],
                'tool_results' => $result['tool_results'] ?? [],
                'conversation_id' => $result['conversation_id'],
            ]);

            // Set/refresh HttpOnly cookie cho guest
            if (!$userId && !empty($result['session_token'])) {
                $response = $response->cookie(
                    ChatSessionService::COOKIE_NAME,
                    $result['session_token'],
                    ChatSessionService::COOKIE_LIFETIME_MINUTES,
                    '/',
                    null,
                    app()->isProduction(),
                    true,
                    false,
                    'lax'
                );
            }

            return $response;
        } catch (AiUnavailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        } catch (\Throwable $e) {
            Log::error('ChatController error', [
                'error' => $e->getMessage(),
                'user' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi, vui lòng thử lại sau.',
            ], 500);
        }
    }

    /**
     * Lấy lịch sử tin nhắn của conversation hiện tại
     * GET /chat/history
     */
    public function getHistory(GetChatHistoryRequest $request)
    {
        $userId = Auth::id();
        $sessionToken = $request->cookie(ChatSessionService::COOKIE_NAME);
        $conversationId = $request->input('conversation_id');

        if (!$conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu conversation_id',
            ], 400);
        }

        $conversation = $this->historyService->getConversation($userId, $sessionToken, $conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện',
            ], 404);
        }

        $history = $this->historyService->getHistory($conversation, [
            'limit' => $request->input('limit', 20),
            'offset' => $request->input('offset', 0),
        ]);

        $messages = array_reverse($history->items());

        return response()->json([
            'success' => true,
            'data' => $messages,
            'pagination' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    /**
     * Lấy danh sách các cuộc trò chuyện
     * GET /chat/conversations
     */
    public function getConversations()
    {
        $userId = Auth::id();
        $sessionToken = request()->cookie(ChatSessionService::COOKIE_NAME);
        $limit = request()->input('limit', 10);

        $conversations = $this->historyService->getConversations($userId, $sessionToken, $limit);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Xóa tin nhắn trong conversation
     * DELETE /chat/messages
     */
    public function deleteMessages(DeleteChatMessageRequest $request)
    {
        $userId = Auth::id();
        $sessionToken = $request->cookie(ChatSessionService::COOKIE_NAME);
        $conversationId = $request->input('conversation_id');

        $conversation = $this->historyService->getConversation($userId, $sessionToken, $conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện',
            ], 404);
        }

        $result = $this->historyService->deleteMessages($conversation, $request->input('message_ids'));

        if (!empty($result['errors'])) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi xóa tin nhắn',
                'errors' => $result['errors'],
                'deleted' => $result['deleted'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa ' . count($result['deleted']) . ' tin nhắn',
            'deleted' => $result['deleted'],
        ]);
    }

    /**
     * Xóa toàn bộ tin nhắn trong conversation
     * DELETE /chat/messages/all
     */
    public function deleteAllMessages()
    {
        $userId = Auth::id();
        $sessionToken = request()->cookie(ChatSessionService::COOKIE_NAME);
        $conversationId = request()->input('conversation_id');

        if (!$conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu conversation_id',
            ], 400);
        }

        $conversation = $this->historyService->getConversation($userId, $sessionToken, $conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện',
            ], 404);
        }

        $result = $this->historyService->deleteAllMessages($conversation);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ tin nhắn',
            'deleted_count' => $result['deleted_count'],
        ]);
    }

    /**
     * Xóa toàn bộ cuộc trò chuyện
     * DELETE /chat/conversation
     */
    public function deleteConversation()
    {
        $userId = Auth::id();
        $sessionToken = request()->cookie(ChatSessionService::COOKIE_NAME);
        $conversationId = request()->input('conversation_id');

        if (!$conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu conversation_id',
            ], 400);
        }

        $conversation = $this->historyService->getConversation($userId, $sessionToken, $conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện',
            ], 404);
        }

        $result = $this->historyService->deleteConversation($conversation);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 500);
        }

        // Xóa cookie nếu là guest
        $response = response()->json([
            'success' => true,
            'message' => 'Đã xóa cuộc trò chuyện',
        ]);

        if (!$userId) {
            $response->cookie(
                ChatSessionService::COOKIE_NAME,
                '',
                -1,
                '/',
                null,
                app()->isProduction(),
                true,
                false,
                'lax'
            );
        }

        return $response;
    }

    /**
     * Tạo lại summary cho conversation
     * POST /chat/regenerate-summary
     */
    public function regenerateSummary()
    {
        $userId = Auth::id();
        $sessionToken = request()->cookie(ChatSessionService::COOKIE_NAME);
        $conversationId = request()->input('conversation_id');

        if (!$conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu conversation_id',
            ], 400);
        }

        $conversation = $this->historyService->getConversation($userId, $sessionToken, $conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện',
            ], 404);
        }

        $summary = $this->historyService->regenerateSummary($conversation);

        if (!$summary) {
            return response()->json([
                'success' => false,
                'message' => 'Cuộc trò chuyện quá ngắn để tạo summary',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
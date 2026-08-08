<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class StaffAiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Lấy danh sách các cuộc trò chuyện của nhân viên
     */
    public function getConversations()
    {
        $userId = Auth::id();
        $conversations = ChatConversation::where('user_id', $userId)
            ->withCount('messages')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        $conversations->transform(function ($conv) {
            $conv->summary = $conv->summary ?: 'Cuộc trò chuyện ' . $conv->created_at->format('d/m/Y H:i');
            return $conv;
        });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Lấy lịch sử chat của một phiên cụ thể
     */
    public function getHistory(Request $request)
    {
        $userId = Auth::id();
        $conversationId = $request->query('conversation_id');

        if (!$conversationId) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        $conversation = ChatConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy cuộc trò chuyện']);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content']);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    /**
     * Xóa một cuộc trò chuyện cụ thể
     */
    public function deleteConversation(Request $request)
    {
        $userId = Auth::id();
        $conversationId = $request->input('conversation_id');

        $conversation = ChatConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Gửi tin nhắn chat đến AI dành cho Nhân viên
     */
    public function sendChatMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'conversation_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        $userQuestion = $request->input('message');
        $conversationId = $request->input('conversation_id');

        // Tìm phiên chat hoặc tạo mới
        if ($conversationId) {
            $conversation = ChatConversation::where('id', $conversationId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$conversation) {
                // Tạo mới nếu ID không hợp lệ (bảo vệ)
                $conversation = ChatConversation::create([
                    'user_id' => $userId,
                    'last_activity_at' => now(),
                    'message_count' => 0
                ]);
            }
        } else {
            // Tạo mới phiên chat
            $conversation = ChatConversation::create([
                'user_id' => $userId,
                'last_activity_at' => now(),
                'message_count' => 0
            ]);
        }

        // Lưu câu hỏi của User
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userQuestion,
        ]);

        // Lấy lịch sử làm ngữ cảnh
        $history = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get(['role', 'content'])
            ->toArray();

        // Gom dữ liệu ngữ cảnh
        $tables = Table::withCount(['orders' => function ($query) {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        }])->get()->map(function ($table) {
            return [
                'table_name' => $table->table_name,
                'status' => $table->status,
                'active_orders_count' => $table->orders_count
            ];
        });

        $activeOrders = Order::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->select('id', 'table_id', 'status', 'total_amount', 'order_type')
            ->with('table:id,table_name')
            ->get();

        $staffContext = [
            'tables' => $tables,
            'active_orders' => $activeOrders,
        ];

        // Gọi AI
        $aiResponse = $this->geminiService->chatWithStaffAi($history, $userQuestion, $staffContext);

        // Lưu câu trả lời của AI
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        $conversation->increment('message_count', 2);
        $conversation->update(['last_activity_at' => now()]);

        return response()->json([
            'success' => true,
            'reply' => $aiResponse,
            'conversation_id' => $conversation->id
        ]);
    }
}

<?php

namespace App\Http\Controllers\Barista;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\OrderDetail;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BaristaAiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Lấy danh sách các cuộc trò chuyện của Barista
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
     * Gửi tin nhắn chat đến AI dành cho Barista
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
                $conversation = ChatConversation::create([
                    'user_id' => $userId,
                    'last_activity_at' => now(),
                    'message_count' => 0
                ]);
            }
        } else {
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

        // Gom dữ liệu ngữ cảnh cho Pha chế
        $pendingItems = OrderDetail::whereIn('barista_status', ['PENDING', 'PREPARING'])
            ->with(['order.table', 'item'])
            ->get()
            ->map(function ($detail) {
                return [
                    'item_name' => $detail->item->item_name ?? 'N/A',
                    'quantity' => $detail->quantity,
                    'note' => $detail->note,
                    'status' => $detail->barista_status,
                    'table' => $detail->order->table->table_name ?? 'Mang đi',
                ];
            });

        $materials = Material::select('material_name', 'quantity_in_stock', 'base_unit')->get();

        $baristaContext = [
            'pending_and_preparing_drinks' => $pendingItems,
            'materials_inventory' => $materials,
        ];

        // Gọi AI
        $aiResponse = $this->geminiService->chatWithBaristaAi($history, $userQuestion, $baristaContext);

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

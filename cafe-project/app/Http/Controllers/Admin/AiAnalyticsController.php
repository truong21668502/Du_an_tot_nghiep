<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AiAnalyticsController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function getStrategicAdvice()
    {
        $userId = Auth::id();

        // Tìm hoặc tạo phiên chat gần nhất cho quản lý này
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $userId],
            ['last_activity_at' => now()]
        );

        $userQuestion = "Dựa vào doanh thu, khung giờ mua hàng, đơn đặt và sản phẩm bán chạy, hãy gợi ý cho tôi chiến lược kinh doanh tiếp theo?";

        // Lưu tin nhắn của User vào database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userQuestion,
        ]);

        // Gom dữ liệu thống kê từ Database (orders & order_details)
        $salesData = [
            'overview' => [
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('status', 'COMPLETED')->sum('final_amount')
            ],
            'peak_hours' => Order::select(
                    DB::RAW('HOUR(created_at) as hour'),
                    DB::RAW('COUNT(*) as total_orders'),
                    DB::RAW('SUM(final_amount) as revenue')
                )->groupBy('hour')
                ->orderByDesc('total_orders')
                ->where('status', 'COMPLETED')
                ->limit(5)
                ->get(),
            'top_products' => OrderDetail::select(
                    'product_id',
                    DB::RAW('SUM(quantity) as total_sold'),
                    DB::RAW('SUM(quantity * unit_price) as total_revenue')
                )->groupBy('product_id')->with('product:id,product_name')->orderByDesc('total_sold')->limit(5)->get(),
        ];

        // Gọi Gemini Service xử lý phân tích (kèm giới hạn token ngắn gọn)
        $aiAdvice = $this->geminiService->analyzeBusinessStrategy($salesData);

        // Lưu câu trả lời của AI (Assistant) vào database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiAdvice,
        ]);

        // Cập nhật lại thông tin phiên chat
        $conversation->increment('message_count', 2);
        $conversation->update(['last_activity_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $aiAdvice
        ]);
    }

    /**
     * Gửi tin nhắn chat đến AI
     */
    public function sendChatMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $userId = Auth::id();
        $userQuestion = $request->input('message');

        // Lấy phiên chat của user
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $userId],
            ['last_activity_at' => now()]
        );

        // Lưu câu hỏi của User vào bảng chat_messages
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userQuestion,
        ]);

        // Lấy 10 tin nhắn gần nhất làm lịch sử ngữ cảnh cho AI
        $history = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get(['role', 'content'])
            ->toArray();

        // Gom dữ liệu thống kê quán
        $salesData = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'COMPLETED')->sum('final_amount'),
            'peak_hours' => Order::select(DB::RAW('HOUR(created_at) as hour'), DB::RAW('COUNT(*) as total'))->groupBy('hour')->get(),
            'top_products' => OrderDetail::select('product_id', DB::RAW('SUM(quantity) as total'))->groupBy('product_id')->with('product:id,product_name')->limit(3)->get()
        ];

        // Gọi AI xử lý kèm lịch sử chat
        $aiResponse = $this->geminiService->chatWithAi($history, $userQuestion, $salesData);

        // Lưu câu trả lời của AI vào database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        $conversation->increment('message_count', 2);
        $conversation->update(['last_activity_at' => now()]);

        return response()->json([
            'success' => true,
            'reply' => $aiResponse
        ]);
    }

    //lấy lịch sử chat
    public function getHistory()
    {
        $conversation = ChatConversation::where('user_id', Auth::id())->first();

        if (!$conversation) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get(['role', 'content']);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
}

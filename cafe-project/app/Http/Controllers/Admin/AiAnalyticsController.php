<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Coupon;
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
        // Lấy ID của quản lý hiện tại
        $userId = Auth::id();

        // Tìm hoặc tạo phiên chat gần nhất cho quản lý này
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $userId],
            ['last_activity_at' => now()]
        );

        // Câu hỏi mẫu từ User để gửi đến AI
        $userQuestion = "Dựa vào khung giờ mua hàng, đơn đặt và sản phẩm bán chạy, hãy gợi ý cho tôi chiến lược kinh doanh tiếp theo?";

        // Lưu tin nhắn của User vào database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userQuestion,
        ]);

        // Gom dữ liệu thống kê từ Database (orders & order_details)
        $salesData = [
            // Thống kê tổng số đơn hàng
            'overview' => [
                'total_orders' => Order::count(),
            ],

            // Thống kê khung giờ mua hàng (peak hours)
            'peak_hours' => Order::select(
                    DB::RAW('HOUR(created_at) as hour'),
                    DB::RAW('COUNT(*) as total_orders'),
                    DB::RAW('SUM(final_amount) as revenue')
                )->groupBy('hour')
                ->orderByDesc('total_orders')
                ->where('status', 'COMPLETED')
                ->limit(5)
                ->get(),

            // Thống kê sản phẩm bán chạy nhất
            'top_products' => OrderDetail::select(
                    'product_id',
                    DB::RAW('SUM(quantity) as total_sold'),
                    DB::RAW('SUM(quantity * unit_price) as total_revenue')
                )->groupBy('product_id')->with('product:id,product_name')->orderByDesc('total_sold')->limit(5)->get(),
            
            // Thống kê các chương trình khuyến mãi đang hoạt động
            'promotions' => Coupon::where('status', 'ACTIVE')
            ->get()
            ->map(function ($coupon, $index) {
                return [
                    'program_label'  => 'Mã ưu đãi #' . ($index + 1),
                    'description'  => $coupon->description,
                    'discount_type'  => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'max_discount_amount'   => $coupon->max_discount_amount ?? null,
                    'min_order_value'     => $coupon->min_order_value ?? null,
                    'usage_limit'     => $coupon->usage_limit ?? null,
                    'used_count'     => $coupon->used_count ?? 0,
                    'expiration_date' => $coupon->expiration_date ? $coupon->expiration_date->toDateString() : null,
                ];
            })
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

        // Tự động xóa bớt tin nhắn cũ nếu vượt quá ngưỡng cho phép
        $this->autoDeleteOldMessages();

        // Trả về dữ liệu phân tích cho Frontend
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
        // Validate input
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // Lấy ID của user hiện tại
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
            'peak_hours' => Order::select(DB::RAW('HOUR(created_at) as hour'), DB::RAW('COUNT(*) as total'))->groupBy('hour')->get(),
            'top_products' => OrderDetail::select('product_id', DB::RAW('SUM(quantity) as total'))->groupBy('product_id')->with('product:id,product_name')->limit(3)->get(),
            'promotions' => Coupon::where('status', 'ACTIVE')
            ->get()
            ->map(function ($coupon, $index) {
                return [
                    'program_label'  => 'Mã ưu đãi #' . ($index + 1),
                    'description'  => $coupon->description,
                    'discount_type'  => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'max_discount_amount'   => $coupon->max_discount_amount ?? null,
                    'min_order_value'     => $coupon->min_order_value ?? null,
                    'usage_limit'     => $coupon->usage_limit ?? null,
                    'used_count'     => $coupon->used_count ?? 0,
                    'expiration_date' => $coupon->expiration_date ? $coupon->expiration_date->toDateString() : null,
                ];
            })
        ];

        // Gọi AI xử lý kèm lịch sử chat
        $aiResponse = $this->geminiService->chatWithAi($history, $userQuestion, $salesData);

        // Lưu câu trả lời của AI vào database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        // Cập nhật lại thông tin phiên chat
        $conversation->increment('message_count', 2);
        $conversation->update(['last_activity_at' => now()]);

        // Tự động xóa bớt tin nhắn cũ nếu vượt quá ngưỡng cho phép
        $this->autoDeleteOldMessages();

        return response()->json([
            'success' => true,
            'reply' => $aiResponse
        ]);
    }

    //lấy lịch sử chat
    public function getHistory()
    {
        // Lấy phiên chat của user
        $conversation = ChatConversation::where('user_id', Auth::id())->first();

        // Nếu không có phiên chat, trả về mảng rỗng
        if (!$conversation) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        // Lấy tất cả tin nhắn của phiên chat, sắp xếp theo thời gian tăng dần
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get(['role', 'content']);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    /**
    * Tự động xóa bớt tin nhắn cũ nếu vượt quá ngưỡng cho phép
    */
    public function autoDeleteOldMessages()
    {
        // Giới hạn số lượng tin nhắn tối đa
        $maxMessages = 100; // Giới hạn số lượng tin nhắn tối đa
        $userId = Auth::id();

        // Lấy phiên chat của user
        $conversation = ChatConversation::where('user_id', $userId)->first();

        // Nếu có phiên chat, kiểm tra số lượng tin nhắn
        if ($conversation) {
            // Đếm số lượng tin nhắn hiện tại
            $messageCount = $conversation->messages()->count();

            // Nếu số lượng tin nhắn vượt quá giới hạn, xóa các tin nhắn cũ nhất
            if ($messageCount > $maxMessages) {
                // Xóa các tin nhắn cũ nhất để giữ lại số lượng tối đa
                $messagesToDelete = $conversation->messages()
                    ->orderBy('created_at', 'asc')
                    ->limit($messageCount - $maxMessages)
                    ->get();

                foreach ($messagesToDelete as $message) {
                    $message->delete();
                }
            }
        }
    }
}

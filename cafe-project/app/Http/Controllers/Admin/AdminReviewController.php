<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\ProhibitedWord;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReviewController extends Controller
{
    // Helper kiểm tra từ cấm
    private function checkProhibitedWords(string $text): ?string
    {
        // Lấy danh sách từ cấm đang Active
        $prohibitedWords = ProhibitedWord::active()->pluck('word')->toArray();

        foreach ($prohibitedWords as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            // Bọc regex với ranh giới từ (\b) và hỗ trợ Unicode (/u)
            // Dùng preg_quote để tránh lỗi nếu từ cấm chứa ký tự đặc biệt
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';

            if (preg_match($pattern, $text)) {
                return $word; // Chỉ trả về khi ĐÚNG là từ độc lập bị cấm
            }
        }

        return null;
    }

    // Danh sách bình luận
    public function index(Request $request)
    {
        $query = Review::with(['user:id,full_name', 'product:id,product_name', 'replies.user:id,full_name'])
            ->latest();

        if ($request->has('rating') && $request->rating != '') {
            $query->where('rating', $request->rating);
        }

        if ($request->has('status') && $request->status != '') {
            $isVisible = $request->status === 'visible';
            $query->where('is_visible', $isVisible);
        }

        return inertia('Admin/ReviewProduct/Index', [
            'reviews' => $query->paginate(10),
            'filters' => $request->only(['rating', 'status'])
        ]);
    }

    // Toggle Ẩn / Hiện
    public function toggleVisibility($id)
    {
        $review = Review::findOrFail($id);
        $review->is_visible = !$review->is_visible;
        $review->save();

        $message = $review->is_visible ? 'Đã hiển thị đánh giá!' : 'Đã ẩn đánh giá!';

        return redirect()->back()->with('toast-success', $message);
    }

    // Gửi phản hồi
    public function reply(Request $request, $id)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = $request->comment;

        // KIỂM TRA TỪ CẤM -> Trả về Toast Error
        $foundBadWord = $this->checkProhibitedWords($comment);
        if ($foundBadWord) {
            return redirect()->back()->with('toast-error', "Nội dung phản hồi chứa từ ngữ bị cấm: \"{$foundBadWord}\". Vui lòng chỉnh sửa lại!");
        }

        ReviewReply::create([
            'review_id' => $id,
            'user_id'   => Auth::id(),
            'comment'   => $comment
        ]);

        return redirect()->back()->with('toast-success', 'Đã gửi câu phản hồi thành công!');
    }

    // Xoá phản hồi của Admin
    public function destroyReply($replyId)
    {
        $reply = ReviewReply::findOrFail($replyId);
        $reply->delete();

        return redirect()->back()->with('toast-success', 'Đã xoá câu phản hồi thành công!');
    }

    // Gọi AI Gemini sinh câu trả lời (Giữ JSON vì gọi trực tiếp điền vào input)
    public function generateAiReply(Request $request, $id, GeminiService $geminiService)
    {
        $review = Review::with(['user', 'product'])->findOrFail($id);

        $aiReply = $geminiService->generateReviewReply(
            $review->user->full_name ?? 'Khách hàng',
            $review->rating,
            $review->comment,
            $review->product->product_name ?? ''
        );

        return response()->json([
            'status'   => true,
            'ai_reply' => $aiReply
        ]);
    }
}
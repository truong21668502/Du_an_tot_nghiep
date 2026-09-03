<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyReviewRequest;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class ReviewReplyController extends Controller
{
    // POST /admin/reviews/{review}/replies
    public function store(ReplyReviewRequest $request, Review $review)
    {
        try {
            // Kiểm tra admin đã trả lời review này chưa
            $existingReply = $review->replies()
                ->where('user_id', $request->user()->id)
                ->exists();

            if ($existingReply) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã trả lời đánh giá này rồi.'
                ], 422);
            }

            // Tạo phản hồi mới trong một transaction để đảm bảo tính toàn vẹn dữ liệu
            $reply = DB::transaction(function () use ($request, $review) {
                return $review->replies()->create([
                    'user_id' => $request->user()->id,
                    'comment' => $request->comment,
                ]);
            });

            // Load thông tin user để trả về trong response
            $reply->load('user:id,full_name,role');

            // Trả về phản hồi thành công với dữ liệu phản hồi mới
            return response()->json([
                'success' => true,
                'message' => 'Phản hồi đánh giá thành công.',
                'data'    => [
                    'id' => $reply->id,
                    'comment' => $reply->comment,
                    'user' => [
                        'id' => $reply->user->id,
                        'name' => $reply->user->full_name ?? 'Nhân viên',
                        'role' => $reply->user->role,
                        'avatar' => $reply->user->avatar ?? null,
                    ],
                    'created_at' => $reply->created_at,
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Lỗi trả lời bình luận: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
            ], 500);
        }
    }

    // app/Http/Controllers/Admin/ReviewReplyController.php

// PATCH /replies/{reply}
public function update(ReplyReviewRequest $request, ReviewReply $reply)
{
    $user = Auth::user();

    // Chỉ chủ sở hữu mới được sửa
    if ($user->id !== $reply->user_id) {
        return response()->json([
            'success' => false,
            'message' => 'Bạn không có quyền sửa phản hồi này.'
        ], 403);
    }

    // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
    try {
        DB::transaction(function () use ($request, $reply) {
            $reply->update([
                'comment' => $request->comment,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phản hồi thành công.',
            'data' => [
                'id' => $reply->id,
                'comment' => $reply->comment,
                'user' => [
                    'id' => $reply->user->id,
                    'name' => $reply->user->full_name ?? 'Nhân viên',
                    'role' => $reply->user->role,
                    'avatar' => $reply->user->avatar ?? null,
                ],
                'created_at' => $reply->created_at,
            ]
        ]);

    } catch (Exception $e) {
        Log::error('Lỗi cập nhật phản hồi: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
        ], 500);
    }
}

    // DELETE /admin/replies/{reply}
    public function destroy(ReviewReply $reply)
    {
        $user = Auth::user();

        // Chỉ ADMIN mới được xóa
        if ($user->role !== 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa phản hồi này.'
            ], 403);
        }

        try {
            DB::transaction(fn() => $reply->delete());

            return response()->json([
                'success' => true,
                'message' => 'Xóa phản hồi thành công.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Lỗi xóa phản hồi: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa phản hồi vào lúc này.'
            ], 500);
        }
    }
}
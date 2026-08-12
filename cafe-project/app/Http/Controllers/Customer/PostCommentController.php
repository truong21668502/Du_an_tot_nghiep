<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostComment;
use App\Services\ProfanityFilterService;

class PostCommentController extends Controller
{
    public function store(
        Request $request,
        int $postId,
        ProfanityFilterService $profanityFilter
    ) {
        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:1000',
            ],
            'rating' => 'nullable|integer|min:1|max:5',
        ], [
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.max' => 'Bình luận không được vượt quá 1000 ký tự.',
        ]);

        $validated['content'] = $profanityFilter->filter(
            $validated['content']
        );

        PostComment::create([
            'post_id' => $postId,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? null,
            'status' => 'APPROVED',
        ]);

        return back();
    }

    public function update(
        Request $request,
        int $postId,
        PostComment $comment,
        ProfanityFilterService $profanityFilter
    ) {
        if ($comment->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền sửa bình luận này.');
        }

        if ($comment->post_id !== $postId) {
            abort(404);
        }

        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:1000',
            ],
            'rating' => 'nullable|integer|min:1|max:5',
        ], [
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.max' => 'Bình luận không được vượt quá 1000 ký tự.',
        ]);

        $validated['content'] = $profanityFilter->filter(
            $validated['content']
        );

        $comment->update([
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? $comment->rating,
        ]);

        return back();
    }

    public function destroy(Request $request, int $postId, PostComment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xóa bình luận này.');
        }

        if ($comment->post_id !== $postId) {
            abort(404);
        }

        $comment->delete();

        return back();
    }
}

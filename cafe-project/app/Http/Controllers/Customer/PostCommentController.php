<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostComment;

class PostCommentController extends Controller
{
    public function store(Request $request, int $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ], [
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.max' => 'Bình luận không được vượt quá 1000 ký tự.',
        ]);

        PostComment::create([
            'post_id' => $postId,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? null,
            'status' => 'APPROVED',
        ]);

        return back();
    }
}

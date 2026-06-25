<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostCommentController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/PostComments/Index', [
            'comments' => PostComment::with(['post', 'user'])->latest()->paginate(15)
        ]);
    }

    // Dùng update để thay đổi trạng thái Duyệt/Ẩn
    public function update(Request $request, PostComment $postComment)
    {
        $data = $request->validate([
            'status' => 'required|in:PENDING,APPROVED,HIDDEN'
        ]);
        $postComment->update($data);
        return back();
    }

    public function destroy(PostComment $postComment)
    {
        $postComment->delete();
        return back();
    }
}
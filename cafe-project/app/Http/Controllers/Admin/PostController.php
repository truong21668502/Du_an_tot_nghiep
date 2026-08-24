<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::with(['category', 'user'])->latest()->paginate(10),
            'categories' => PostCategory::select('id', 'name')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create', [
            'categories' => PostCategory::select('id', 'name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:post_categories,id',
            'content' => 'required|string',
            'thumbnail_url' => 'nullable|string',
            'slug' => 'nullable|string',
            'status' => 'required|in:DRAFT,PUBLISHED'
        ]);

        $validated['slug'] = $request->slug ?: Str::slug($request->title);
        $validated['user_id'] = Auth::id();

        try {
            Post::create($validated);
        } catch (\Throwable $e) {
            return redirect()->back()->with('toast-error', 'Thêm bài viết thất bại, vui lòng thử lại!');
        }

        return redirect()->route('admin.posts.index')->with('toast-success', 'Thêm bài viết thành công!');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post,
            'categories' => PostCategory::select('id', 'name')->get()
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:post_categories,id',
            'content' => 'required|string',
            'thumbnail_url' => 'nullable|string',
            'slug' => 'nullable|string',
            'status' => 'required|in:DRAFT,PUBLISHED'
        ]);

        $validated['slug'] = $request->slug ?: Str::slug($request->title);

        try {
            $post->update($validated);
        } catch (\Throwable $e) {
            return redirect()->back()->with('toast-error', 'Cập nhật bài viết thất bại, vui lòng thử lại!');
        }

        return redirect()->route('admin.posts.index')->with('toast-success', 'Cập nhật bài viết thành công!');
    }

    public function destroy(Post $post)
    {
        try {
            $post->delete();
        } catch (\Throwable $e) {
            return redirect()->back()->with('toast-error', 'Xóa bài viết thất bại, có thể bài viết đang được liên kết!');
        }

        return redirect()->back()->with('toast-success', 'Đã xóa bài viết thành công!');
    }
}
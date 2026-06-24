<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::with(['category', 'user'])->latest()->paginate(10),
            // Truyền thêm categories để load vào thẻ select trong Modal
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
            'thumbnail_url' => 'nullable|string', // Nhận link ảnh
            'slug' => 'nullable|string'
        ]);

        $validated['slug'] = $request->slug ?: Str::slug($request->title);
        $validated['user_id'] = Auth::id();

        Post::create($validated);

        return redirect()->route('admin.posts.index')->with('message', 'Thêm bài viết thành công');
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
            'slug' => 'nullable|string'
        ]);

        $validated['slug'] = $request->slug ?: Str::slug($request->title);

        $post->update($validated);

        return redirect()->route('admin.posts.index')->with('message', 'Cập nhật thành công');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->back()->with('message', 'Đã xóa bài viết');
    }
}
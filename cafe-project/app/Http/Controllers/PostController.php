<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory; // Nhớ import model danh mục
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['category', 'user'])
            ->where('status', 'PUBLISHED');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        // Paginator của Laravel
        // Lưu ý: useBlog của bạn đang setup perPage mặc định là 9, nên ta để 9 luôn cho đồng bộ
        $paginator = $query->orderBy('published_at', 'desc')->paginate(9);

        $categories = PostCategory::all();

        return Inertia::render('Blog', [
            // 1. Chỉ lấy mảng dữ liệu thực tế (Array) để tránh lỗi [...posts.value]
            'posts' => $paginator->items(),

            'categories' => $categories,

            // 2. Nhồi thêm thông tin lastPage và total vào filters đúng như useBlog đang cần
            'filters' => array_merge(
                $request->only(['search', 'category', 'page']),
                [
                    'total' => $paginator->total(),
                    'lastPage' => $paginator->lastPage(),
                ]
            )
        ]);
    }

    public function show(string $slug)
    {
        // 1. Lấy bài viết chính kèm các quan hệ
        // Lưu ý: Mình giả định 'user' là 'author' như trong code Vue của bạn
        $post = Post::with(['category', 'user', 'comments'])
            ->where('slug', $slug)
            ->where('status', 'PUBLISHED')
            ->firstOrFail(); // Tự động trả về 404 nếu không tìm thấy

        // 2. Lấy bài viết liên quan (ví dụ: cùng danh mục, loại trừ bài hiện tại)
        $relatedPosts = Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->where('status', 'PUBLISHED')
            ->limit(3)
            ->get();

        // 3. Chuẩn hóa dữ liệu cho khớp với kỳ vọng của Vue
        // Vì Vue dùng post.author thay vì post.user, chúng ta có thể format nhẹ ở đây
        $postData = $post->toArray();
        $postData['author'] = $post->user; // Map user vào author

        // Nếu bạn có cột tags trong DB, đảm bảo nó là mảng
        $postData['tags'] = is_string($post->tags) ? json_decode($post->tags) : ($post->tags ?? []);

        return Inertia::render('Blog/Show', [
            'post' => $postData,
            'relatedPosts' => $relatedPosts,
            'comments' => $post->comments
        ]);
    }
}
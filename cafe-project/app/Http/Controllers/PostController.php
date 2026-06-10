<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory;
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
            $categorySlug = $request->category;

            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug); // Lọc qua slug của bảng categories
            });
        }

        // Paginator của Laravel
        $paginator = $query->orderBy('published_at', 'desc')->paginate(9);

        $categories = PostCategory::all();

        return Inertia::render('Blog', [
            'posts' => $paginator->items(),

            'categories' => $categories,

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
        $post = Post::with(['category', 'user', 'comments'])
            ->where('slug', $slug)
            ->where('status', 'PUBLISHED')
            ->firstOrFail(); // Tự động trả về 404 nếu không tìm thấy

        $relatedPosts = Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->where('status', 'PUBLISHED')
            ->limit(3)
            ->get();

        $postData = $post->toArray();
        $postData['author'] = $post->user; // Map user vào author

        $postData['tags'] = is_string($post->tags) ? json_decode($post->tags) : ($post->tags ?? []);

        return Inertia::render('Blog/Show', [
            'post' => $postData,
            'relatedPosts' => $relatedPosts,
            'comments' => $post->comments
        ]);
    }
}
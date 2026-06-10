<?php

namespace App\Http\Controllers\Customer;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\PostComment;

class PostController extends Controller
{

    public function dispatch(string $slug)
    {
        if (PostCategory::where('slug', $slug)->exists()) {
            return $this->index(request(), $slug);
        }
        return $this->show($slug);
    }

    public function index(Request $request, string $category = 'all')
    {
        $query = Post::with(['category', 'user'])
            ->where('status', 'PUBLISHED');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $activeCategory = $category !== 'all'
            ? $category
            : $request->get('category', 'all');

        if ($activeCategory !== 'all') {
            $query->whereHas('category', function ($q) use ($activeCategory) {
                $q->where('slug', $activeCategory);
            });
        }

        $paginator = $query->orderBy('published_at', 'desc')->paginate(9);

        return Inertia::render('Blog', [
            'posts' => $paginator->items(),
            'categories' => PostCategory::all(),
            'filters' => array_merge(
                $request->only(['search', 'page']),
                [
                    'category' => $activeCategory,
                    'total' => $paginator->total(),
                    'lastPage' => $paginator->lastPage(),
                ]
            )
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::with(['category', 'user', 'comments.user'])
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
            'comments' => PostComment::with('user')
                ->where('post_id', $post->id)
                ->where('status', 'APPROVED')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }
}
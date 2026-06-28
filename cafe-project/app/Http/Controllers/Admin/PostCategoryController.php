<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/PostCategories/Index', [
            // Lấy kèm số lượng bài viết trong mỗi danh mục (withCount)
            'categories' => PostCategory::withCount('posts')->latest()->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        PostCategory::create($data);
        return back();
    }

    public function update(Request $request, PostCategory $postCategory)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $postCategory->update($data);
        return back();
    }

    public function destroy(PostCategory $postCategory)
    {
        // Có thể kiểm tra nếu có bài viết thì không cho xóa
        if ($postCategory->posts()->count() > 0) {
            return back()->withErrors(['message' => 'Không thể xóa danh mục đang có bài viết!']);
        }
        $postCategory->delete();
        return back();
    }
}
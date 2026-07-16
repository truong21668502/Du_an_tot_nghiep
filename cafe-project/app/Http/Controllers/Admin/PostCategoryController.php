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

        try {
            PostCategory::create($data);
        } catch (\Throwable $e) {
            return back()->with('toast-error', 'Thêm danh mục thất bại, vui lòng thử lại!');
        }

        return back()->with('toast-success', 'Thêm danh mục thành công!');
    }

    public function update(Request $request, PostCategory $postCategory)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        try {
            $postCategory->update($data);
        } catch (\Throwable $e) {
            return back()->with('toast-error', 'Cập nhật danh mục thất bại, vui lòng thử lại!');
        }

        return back()->with('toast-success', 'Cập nhật danh mục thành công!');
    }

    public function destroy(PostCategory $postCategory)
    {
        // Kiểm tra nếu có bài viết thì không cho xóa
        if ($postCategory->posts()->count() > 0) {
            return back()->with('toast-error', 'Không thể xóa danh mục đang có bài viết!');
        }

        try {
            $postCategory->delete();
        } catch (\Throwable $e) {
            return back()->with('toast-error', 'Xóa danh mục thất bại, vui lòng thử lại!');
        }

        return back()->with('toast-success', 'Đã xóa danh mục thành công!');
    }
}
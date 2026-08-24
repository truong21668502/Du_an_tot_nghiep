<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Hiển thị trang danh sách danh mục (Bao gồm cả Modal Thêm/Sửa bên trong Vue)
     */
    public function index(): Response
    {
        $categories = Category::latest('id')->paginate(10);
        
        return Inertia::render('Admin/Category/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Xử lý lưu danh mục mới từ Modal gửi lên
     */
    public function store(StoreCategoryRequest $request)
    {
        //xét validated data từ request
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['category_name']);
            $slugCount = Category::where('slug', 'LIKE', $validated['slug'] . '%')->count();
            if ($slugCount > 0) {
                $validated['slug'] .= '-' . ($slugCount + 1);
            }
        }

        // Lưu dữ liệu vào database
        Category::create($validated);

        // Chỉ cần redirect back để Inertia tự làm mới data trên trang Index
        return redirect()->back()->with('toast-success', 'Thêm mới danh mục thành công!');
    }

    /**
     * Xử lý cập nhật danh mục từ Modal chỉnh sửa gửi lên
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['category_name']);
            $slugCount = Category::where('slug', 'LIKE', $validated['slug'] . '%')
                ->where('id', '!=', $category->id)
                ->count();
            if ($slugCount > 0) {
                $validated['slug'] .= '-' . ($slugCount + 1);
            }
        }

        // Cập nhật dữ liệu vào database
        $category->update($validated);

        return redirect()->back()->with('toast-success', 'Cập nhật danh mục thành công!');
    }

    /**
     * Xóa danh mục
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()->with('toast-success', 'Đã đưa danh mục vào thùng rác!');
    }

    /**
     * Hiển thị danh sách danh mục đã xóa (Trash)
     */
    public function trash()
    {
        $categories = Category::onlyTrashed()->paginate(10);
        return Inertia::render('Admin/Category/Trash', [
            'categories' => $categories
        ]);
    }

    /**
     * Khôi phục danh mục đã xóa
     */

    public function restore($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return back()->with('toast-success', 'Đã khôi phục danh mục thành công.');
    }

    /**
     * Xóa vĩnh viễn danh mục
     */

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        if ($category->products()->exists()) {
            return redirect()->back()->with('toast-error', 'Không thể xóa! Danh mục này đang chứa sản phẩm.');
        }

        $category->forceDelete();

        return back()->with('toast-success', 'Đã xóa vĩnh viễn danh mục.');
    }
}
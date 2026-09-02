<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Http\Requests\Admin\BrandStoreRequest;
use App\Http\Requests\Admin\BrandUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $query = Brand::query();

        // Lọc tìm kiếm theo tên hoặc mô tả thương hiệu
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('brand_name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $brands = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands'  => $brands,
            'filters' => $filters,
        ]);
    }

    public function store(BrandStoreRequest $request)
    {
        // Tạo thương hiệu mới từ dữ liệu hợp lệ
        Brand::create($request->validated());

        // Xóa cache thương hiệu toàn cục để cập nhật dữ liệu mới
        Cache::forget('global_brand');

        // Trả về thông báo thành công
        return redirect()->back()->with('toast-success', 'Thêm thương hiệu sản phẩm thành công!');
    }

    public function update(BrandUpdateRequest $request, $id)
    {
        // Cập nhật thương hiệu từ dữ liệu hợp lệ
        $brand = Brand::findOrFail($id);

        // Cập nhật dữ liệu thương hiệu
        $brand->update($request->validated());

        // Xóa cache thương hiệu toàn cục để cập nhật dữ liệu mới
        Cache::forget('global_brand');

        return redirect()->back()->with('toast-success', 'Cập nhật thương hiệu thành công!');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        
        // Kiểm tra xem thương hiệu này có sản phẩm nào không trước khi xóa
        if ($brand->products()->exists()) {
            return redirect()->back()->with('toast-error', 'Không thể xóa! Thương hiệu này đang có sản phẩm liên kết.');
        }

        $brand->delete();
        return redirect()->back()->with('toast-success', 'Xóa thương hiệu thành công!');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Hiển thị trang danh sách sản phẩm + Modal Thêm/Sửa
     */
    public function index(): Response
    {
        // Lấy sản phẩm kèm theo thông tin danh mục, thương hiệu và danh sách biến thể
        $products = Product::with(['category', 'brand', 'variants'])
            ->latest('id')
            ->paginate(10);

        // Lấy nhanh danh sách danh mục và thương hiệu để đổ vào thẻ <select> trong Modal
        $categories = Category::select('id', 'category_name')->get();
        $brands = Brand::select('id', 'brand_name')->get(); // Giả định bảng brands có cột 'name'

        return Inertia::render('Admin/Product/Index', [
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
        ]);
    }

    /**
     * Xử lý thêm mới sản phẩm và các biến thể size đi kèm
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // Tự động tạo slug nếu để trống
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['product_name']);
            $slugCount = Product::where('slug', 'LIKE', $validated['slug'] . '%')->count();
            if ($slugCount > 0) {
                $validated['slug'] .= '-' . ($slugCount + 1);
            }
        }

        // Tiến hành lưu đa bảng an toàn
        DB::transaction(function () use ($validated) {
            // 1. Tạo sản phẩm chính
            $product = Product::create($validated);

            // 2. Tạo các biến thể kích thước tương ứng
            foreach ($validated['variants'] as $variant) {
                $product->variants()->create($variant);
            }
        });

        return redirect()->back()->with('message', 'Thêm mới sản phẩm và các size thành công!');
    }

    /**
     * Xử lý cập nhật sản phẩm và đồng bộ lại các biến thể
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['product_name']);
            $slugCount = Product::where('slug', 'LIKE', $validated['slug'] . '%')
                ->where('id', '!=', $product->id)
                ->count();
            if ($slugCount > 0) {
                $validated['slug'] .= '-' . ($slugCount + 1);
            }
        }

        DB::transaction(function () use ($validated, $product) {
            // 1. Cập nhật thông tin sản phẩm chính
            $product->update($validated);

            // 2. Đồng bộ biến thể bằng cách xóa các biến thể cũ và ghi đè loạt biến thể mới
            $product->variants()->delete();
            foreach ($validated['variants'] as $variant) {
                $product->variants()->create($variant);
            }
        });

        return redirect()->back()->with('message', 'Cập nhật thông tin sản phẩm thành công!');
    }

    /**
     * Xóa sản phẩm (Sẽ tự động xóa sạch biến thể do có onDelete('cascade') ở database)
     */
    public function destroy(Product $product)
    {
        // Chỗ này bạn có thể bổ sung kiểm tra nếu sản phẩm đã nằm trong Đơn hàng (Order Details) thì không cho xóa
        $product->delete();

        return redirect()->back()->with('message', 'Xóa sản phẩm thành công!');
    }
}
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
use Illuminate\Http\Request;
use App\Models\ProductImage;

class ProductController extends Controller
{
    /**
     * Hiển thị trang danh sách sản phẩm + Modal Thêm/Sửa
     */
    public function index(Request $request): Response
    {
        // Bắt đầu câu lệnh truy vấn dữ liệu sản phẩm
        $query = Product::with(['category', 'brand', 'variants','images'])->latest('id');

        // 1. Lọc theo từ khóa tìm kiếm (Tìm theo Tên sản phẩm hoặc Slug)
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('product_name', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        });

        // 2. Lọc theo Danh mục sản phẩm
        $query->when($request->input('category_id'), function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        });

        // 3. Lọc theo Trạng thái kinh doanh
        $query->when($request->input('is_active'), function ($q, $isActive) {
            $q->where('is_active', $isActive);
        });

        // Thực thi lấy dữ liệu kèm phân trang, appends() dùng để giữ lại các bộ lọc trên URL khi bấm chuyển trang
        $products = $query->paginate(10)->withQueryString();

        // Lấy danh sách danh mục và thương hiệu để đổ vào các thẻ select
        $categories = Category::select('id', 'category_name')->get();
        $brands = Brand::select('id', 'brand_name')->get();

        return Inertia::render('Admin/Product/Index', [
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
            // Gửi ngược lại các giá trị lọc cũ về Vue để hiển thị lên thanh tìm kiếm
            'filters'    => $request->only(['search', 'category_id', 'is_active'])
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

        DB::transaction(function () use ($validated) {
            // 1. Tạo sản phẩm chính
            $product = Product::create($validated);

            // 2. Tạo các biến thể kích thước tương ứng (Thêm dấu & trước $variant)
            foreach ($validated['variants'] as &$variant) {
                $product->variants()->create($variant);
            }
        });

        return redirect()->back()->with('toast-success', 'Thêm mới sản phẩm và các size thành công!');
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

            // 2. Đồng bộ biến thể (Thêm dấu & trước $variant)
            $product->variants()->delete();
            foreach ($validated['variants'] as &$variant) {
                $product->variants()->create($variant);
            }
        });

        return redirect()->back()->with('toast-success', 'Cập nhật thông tin sản phẩm thành công!');
    }

    /**
     * Xóa sản phẩm (Sẽ tự động xóa sạch biến thể do có onDelete('cascade') ở database)
     */
    public function destroy(Product $product)
    {
        // Chỗ này bạn có thể bổ sung kiểm tra nếu sản phẩm đã nằm trong Đơn hàng (Order Details) thì không cho xóa
        $product->delete();

        return redirect()->back()->with('toast-success', 'Đã đưa sản phẩm vào thùng rác!');
    }

    //thùng rác sản phẩm
    public function trash()
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'variants', 'images'])
            ->latest('deleted_at')
            ->paginate(10);

        return Inertia::render('Admin/Product/Trash', [
            'products' => $products
        ]);
    }

    // Khôi phục sản phẩm từ thùng rác
    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return back()->with('toast-success', 'Khôi phục sản phẩm thành công.');
    }

    // Xóa vĩnh viễn sản phẩm khỏi cơ sở dữ liệu
    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        // Nâng cao: Có thể xóa file ảnh vật lý trên disk tại đây nếu cần

        $product->forceDelete(); // Xóa vĩnh viễn khỏi DB

        return back()->with('toast-success', 'Đã xóa vĩnh viễn sản phẩm.');
    }

    public function storeImage(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'image_url'  => 'required|string|max:255',
        ]);
    
        ProductImage::create($validated);
    
        return redirect()->back()->with('toast-success', 'Đã thêm ảnh phụ thành công!');
    }
    
    public function destroyImage(ProductImage $image)
    {
        $image->delete();
        return redirect()->back()->with('toast-success', 'Đã xóa ảnh phụ thành công!');
    }
}
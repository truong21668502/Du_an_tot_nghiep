<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;       // Giả định bảng Đơn hàng lưu doanh thu
use App\Models\OrderDetail; // Giả định bảng Chi tiết đơn hàng để đếm lượt mua sản phẩm
use App\Models\Material;  // Giả định bảng Nguyên liệu kho
use App\Models\Post;        // Bảng bài viết/tin tức
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        //TỔNG QUAN TÀI NGUYÊN (Counters)
        $counters = [
            'products' => [
                'total' => Product::count(),
                'active' => Product::where('is_active', 'Đang bán')->count(),
            ],
            'categories' => [
                'total' => Category::count(),
            ],
            'users' => [
                'total' => User::count(),
                'customers' => User::where('role', 'CUSTOMER')->count(),
                'staff' => User::where('role', '!=', 'CUSTOMER')->count(),
            ],
        ];

        //DOANH THU & ĐƠN HÀNG (Mặc định lấy trong tháng hiện tại)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $revenue = Order::where('status', 'COMPLETED')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('final_amount');

        $totalOrders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        //CẢNH BÁO NGUYÊN LIỆU SẮP HẾT (Tồn kho <= Hạn mức tối thiểu)
        // Giả sử bảng ingredients có cột 'quantity' và 'min_limit'
        // 🌟 3. XỬ LÝ KHO BIẾN THIÊN ĐƠN VỊ (KHÔNG CÓ MIN_LIMIT)
        // Định nghĩa bảng quy đổi hạn mức an toàn dựa theo TÊN đơn vị tính (unit)
        $unitAlertLimits = [
            'ml'    => 1000, // Siro, sữa dưới 1000ml (1 Lít) là báo động
            'g'     => 500,  // Dưới 500g là báo động
            'Hũ' => 5,    // Dưới 5 hũ là báo động
            'Quả' => 10,    // Dưới 10 quả là báo động
            'Cái' => 10,    // Dưới 10 cái là báo động
            'Lát' => 10,    // Dưới 10 lát là báo động
            'Miếng' => 10,    // Dưới 10 miếng là báo động
        ];

        // Lấy toàn bộ kho nguyên liệu ra để tính toán
        $allIngredients = Material::all(['material_name', 'quantity_in_stock', 'base_unit']);
        $lowStockIngredients = [];

        foreach ($allIngredients as $ing) {
            $unitLower = mb_strtolower($ing->base_unit, 'UTF-8');
            
            // Tìm hạn mức báo động tương ứng với đơn vị, mặc định nếu lạ quá thì dưới 5 là báo động
            $limit = $unitAlertLimits[$unitLower] ?? 5;

            if ($ing->quantity_in_stock <= $limit) {
                $lowStockIngredients[] = [
                    'material_name'     => $ing->material_name,
                    'quantity_in_stock' => (float)$ing->quantity_in_stock,
                    'base_unit'     => $ing->base_unit,
                    'limit'    => $limit // Gửi kèm hạn mức để frontend hiển thị trực quan
                ];
            }
        }

        //SẢN PHẨM ĐƯỢC YÊU THÍCH NHẤT & MUA NHIỀU NHẤT
        // Sản phẩm mua nhiều nhất (Dựa vào bảng chi tiết đơn hàng thành công)
        $topSellingProducts = Product::select('products.id', 'products.product_name', 'products.image_url', DB::raw('SUM(order_details.quantity) as total_sold'))
            ->join('order_details', 'products.id', '=', 'order_details.product_id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.status', 'COMPLETED')
            ->groupBy('products.id', 'products.product_name', 'products.image_url')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get();

        // SẢN PHẨM ĐƯỢC YÊU THÍCH NHẤT (Dựa trên số lượt lưu vào danh sách yêu thích)
        $topLikedProducts = Product::select('products.id', 'products.product_name', 'products.image_url')
            ->selectRaw('COUNT(favorite_products.product_id) as total_favorites')
            ->join('favorite_products', 'products.id', '=', 'favorite_products.product_id')
            ->groupBy('products.id', 'products.product_name', 'products.image_url')
            ->orderBy('total_favorites', 'desc')
            ->take(5)
            ->get();

        // BÀI VIẾT ĐƯỢC ĐÁNH GIÁ CAO NHẤT (Tính theo điểm sao trung bình từ bảng post_comments)
        $topPosts = Post::select('posts.id', 'posts.title')
            // Tính trung bình số sao và ép kiểu về dạng số thập phân 1 chữ số (Ví dụ: 4.8)
            ->selectRaw('ROUND(AVG(post_comments.rating), 1) as avg_rating')
            // Đếm tổng số lượt đánh giá của bài viết đó
            ->selectRaw('COUNT(post_comments.id) as total_reviews')
            ->join('post_comments', 'posts.id', '=', 'post_comments.post_id')
            // Chỉ tính các bình luận đã được phê duyệt (Tránh clone/spam)
            ->where('post_comments.status', 'APPROVED')

            ->groupBy('posts.id', 'posts.title')
            // Ưu tiên bài viết điểm cao trước, nếu bằng điểm thì bài nào nhiều lượt review hơn xếp trên
            ->orderBy('avg_rating', 'desc')
            ->orderBy('total_reviews', 'desc')
            ->take(5)
            ->get();

        // Trả dữ liệu sang Frontend thông qua Inertia
        return Inertia::render('Admin/DashBoard/Index', [
            'counters' => $counters,
            'revenue' => (float) $revenue,
            'totalOrders' => $totalOrders,
            'lowStockIngredients' => $lowStockIngredients,
            'topSellingProducts' => $topSellingProducts,
            'topLikedProducts' => $topLikedProducts,
            'topPosts' => $topPosts
        ]);
    }
}

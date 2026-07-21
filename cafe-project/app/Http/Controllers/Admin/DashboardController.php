<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order; 
use App\Models\Material; 
use App\Models\Post; 
use App\Models\User;
use App\Models\Recipe;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Lấy năm người dùng muốn xem
        $selectedYear = (int) $request->input('year', Carbon::now()->year);
        $currentYear = Carbon::now()->year;

        // TỔNG QUAN TÀI NGUYÊN (Counters)
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
            'ingredients' => [
                'total' => Material::count()
            ],
            'posts' => [
                'total' => Post::count()
            ],
            'recipes' => [
                'total' => Recipe::count()
            ],
            'tables' => [
                'total' => Table::count()
            ]
        ];

        // Tính doanh thu tổng toàn bộ từ trước đến nay
        $revenue = Order::where('status', 'COMPLETED')->sum('final_amount');

        // BÁO ĐỘNG KHO (Nguyên liệu)
        $unitAlertLimits = [
            'ml'    => 1000,
            'g'     => 500,
            'hũ'    => 5,
            'quả'   => 10,
            'cái'   => 10,
            'lát'   => 10,
            'miếng' => 10,
        ];

        $allIngredients = Material::all(['material_name', 'quantity_in_stock', 'base_unit']);
        $lowStockIngredients = [];

        foreach ($allIngredients as $ing) {
            $unitLower = mb_strtolower($ing->base_unit, 'UTF-8');
            $limit = $unitAlertLimits[$unitLower] ?? 5;

            if ($ing->quantity_in_stock <= $limit) {
                $lowStockIngredients[] = [
                    'material_name'     => $ing->material_name,
                    'quantity_in_stock' => (float)$ing->quantity_in_stock,
                    'base_unit'         => $ing->base_unit,
                    'limit'             => $limit
                ];
            }
        }

        // TOP SẢN PHẨM & BÀI VIẾT
        $topSellingProducts = Product::select('products.id', 'products.product_name', 'products.image_url', DB::raw('SUM(order_details.quantity) as total_sold'))
            ->join('order_details', 'products.id', '=', 'order_details.product_id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.status', 'COMPLETED')
            ->groupBy('products.id', 'products.product_name', 'products.image_url')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get();

        $topLikedProducts = Product::select('products.id', 'products.product_name', 'products.image_url')
            ->selectRaw('COUNT(favorite_products.product_id) as total_favorites')
            ->join('favorite_products', 'products.id', '=', 'favorite_products.product_id')
            ->groupBy('products.id', 'products.product_name', 'products.image_url')
            ->orderBy('total_favorites', 'desc')
            ->take(5)
            ->get();

        $topPosts = Post::select('posts.id', 'posts.title')
            ->selectRaw('ROUND(AVG(post_comments.rating), 1) as avg_rating')
            ->selectRaw('COUNT(post_comments.id) as total_reviews')
            ->selectRaw('SUM(post_comments.rating) as total_rating')
            ->join('post_comments', 'posts.id', '=', 'post_comments.post_id')
            ->where('post_comments.status', 'APPROVED')
            ->groupBy('posts.id', 'posts.title')
            ->orderBy('avg_rating', 'desc')
            ->orderBy('total_reviews', 'desc')
            ->orderBy('total_rating', 'desc')
            ->take(5)
            ->get();

        // =========================================================
        // XỬ LÝ DỮ LIỆU DOANH THU THEO NĂM ĐƯỢC CHỌN ($selectedYear)
        // =========================================================

        // Doanh thu 12 tháng của NĂM ĐƯỢC CHỌN
        $monthlyRevenue = Order::selectRaw('MONTH(created_at) as period, SUM(final_amount) as total')
            ->where('status', 'COMPLETED')
            ->whereYear('created_at', $selectedYear) // <-- Đã thay $currentYear bằng $selectedYear
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $monthsData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthsData[] = (float) ($monthlyRevenue[$m] ?? 0);
        }

        // Doanh thu 4 quý của NĂM ĐƯỢC CHỌN 
        $quarterlyRevenue = Order::selectRaw('QUARTER(created_at) as period, SUM(final_amount) as total')
            ->where('status', 'COMPLETED')
            ->whereYear('created_at', $selectedYear)
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $quartersData = [];
        for ($q = 1; $q <= 4; $q++) {
            $quartersData[] = (float) ($quarterlyRevenue[$q] ?? 0);
        }

        // Doanh thu 5 năm gần đây (So với năm hiện tại)
        $yearlyRevenue = Order::selectRaw('YEAR(created_at) as period, SUM(final_amount) as total')
            ->where('status', 'COMPLETED')
            ->where('created_at', '>=', Carbon::now()->subYears(4)->startOfYear())
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $yearsLabels = [];
        $yearsData = [];
        for ($i = 4; $i >= 0; $i--) {
            $y = $currentYear - $i;
            $yearsLabels[] = "Năm " . $y;
            $yearsData[] = (float) ($yearlyRevenue[$y] ?? 0);
        }

        // Danh sách các năm có trong hệ thống để Frontend render Dropdown/Select chọn năm
        $availableYears = Order::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Nếu chưa có đơn hàng nào, lấy mặc định năm hiện tại
        if (empty($availableYears)) {
            $availableYears = [$currentYear];
        }

        // TOP KHÁCH HÀNG CHI TIÊU NHIỀU NHẤT (Hoặc mua nhiều đơn nhất)
        $topCustomers = User::select(
                'users.id', 
                'users.full_name', 
                'users.email', 
                'users.phone_number',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.final_amount) as total_spent')
            )
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', 'COMPLETED')
            ->groupBy('users.id', 'users.full_name', 'users.email', 'users.phone_number')
            ->orderBy('total_spent', 'desc') // Xếp theo tổng tiền chi tiêu
            ->take(5) // Lấy top 5
            ->get();

        return Inertia::render('Admin/DashBoard/Index', [
            'counters' => $counters,
            'revenue' => (float) $revenue,
            'lowStockIngredients' => $lowStockIngredients,
            'topSellingProducts' => $topSellingProducts,
            'topLikedProducts' => $topLikedProducts,
            'topPosts' => $topPosts,
            'selectedYear' => $selectedYear,      // Trả lại năm đang chọn
            'availableYears' => $availableYears,  // Trả về danh sách các năm để lọc
            'topCustomers' => $topCustomers,      // Trả về danh sách top khách hàng

            'chartData' => [
                'months' => [
                    'labels' => ['Thg 1', 'Thg 2', 'Thg 3', 'Thg 4', 'Thg 5', 'Thg 6', 'Thg 7', 'Thg 8', 'Thg 9', 'Thg 10', 'Thg 11', 'Thg 12'],
                    'data'   => $monthsData
                ],
                'quarters' => [
                    'labels' => ['Quý 1', 'Quý 2', 'Quý 3', 'Quý 4'],
                    'data'   => $quartersData
                ],
                'years' => [
                    'labels' => $yearsLabels,
                    'data'   => $yearsData
                ],
            ]
        ]);
    }

    public function getDailyRevenue(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $dailyData = Order::selectRaw('DAY(created_at) as day, SUM(final_amount) as total')
            ->where('status', 'COMPLETED')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $labels = [];
        $data = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[] = "Ngày " . $d;
            $data[] = (float) ($dailyData[$d] ?? 0);
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'month' => $month,
            'year' => $year,
            'total_month' => array_sum($data)
        ]);
    }
}
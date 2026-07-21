<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostCommentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserVoucherController;
use App\Http\Controllers\Admin\ImportReceiptController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\DashboardController;


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:ADMIN'])->prefix('quan-tri')->name('admin.')->group(function () {

    // xử lý hiển thị trang chủ
    Route::get('/trang-chu', function () {
        return Inertia::render('Admin/DashBoardAdmin');
    })->name('dashboard');

    //xử lý quản lý danh mục
    Route::get('/danh-muc', [CategoryController::class, 'index'])->name('category.index');
    Route::post('/danh-muc', [CategoryController::class, 'store'])->name('category.store');
    Route::put('/danh-muc/{category}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/danh-muc/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');

    // Quản lý sản phẩm
    Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
    Route::post('/san-pham', [ProductController::class, 'store'])->name('products.store');
    Route::put('/san-pham/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/san-pham/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/hinh-anh-phu', [App\Http\Controllers\Admin\ProductController::class, 'storeImage'])->name('products.storeImage');
    Route::delete('/hinh-anh-phu/{image}', [App\Http\Controllers\Admin\ProductController::class, 'destroyImage'])->name('products.destroyImage');

    // Quản lý bàn ăn / bàn cà phê
    Route::get('/ban', [TableController::class, 'index'])->name('tables.index');
    Route::post('/ban', [TableController::class, 'store'])->name('tables.store');
    Route::put('/ban/{table}', [TableController::class, 'update'])->name('tables.update');
    Route::delete('/ban/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    Route::get('/quan-tri/ban/print', [TableController::class, 'print'])
        ->name('tables.print');

    // Quản lý thương hiệu
    Route::get('/thuong-hieu', [BrandController::class, 'index'])->name('brands.index');
    Route::post('/thuong-hieu', [BrandController::class, 'store'])->name('brands.store');
    Route::put('/thuong-hieu/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/thuong-hieu/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    // Quản lý mã giảm giá (Coupons)
    Route::get('/ma-giam-gia', [CouponController::class, 'index'])->name('coupons.index');
    Route::post('/ma-giam-gia', [CouponController::class, 'store'])->name('coupons.store');
    Route::put('/ma-giam-gia/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('/ma-giam-gia/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');


    // Quản lý người dùng
    Route::get('/nguoi-dung', [UserController::class, 'index'])->name('users.index');
    Route::post('/nguoi-dung', [UserController::class, 'store'])->name('users.store');
    Route::put('/nguoi-dung/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/nguoi-dung/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    //Route mới phục vụ riêng cho Thùng rác người dùng:
    Route::post('/nguoi-dung/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('/nguoi-dung/{id}/force-delete', [UserController::class, 'forceDelete'])->name('users.forceDelete');

    // Quản lý Ví Voucher khách hàng
    Route::get('/vi-voucher', [UserVoucherController::class, 'index'])->name('userVouchers.index');
    Route::delete('/vi-voucher/{id}', [UserVoucherController::class, 'destroy'])->name('userVouchers.destroy');
    Route::post('/vi-voucher', [UserVoucherController::class, 'store'])->name('userVouchers.store');

    //quản lý trang chủ dashboard admin
    Route::get('/trang-chu', [DashboardController::class, 'index'])->name('dashboard');
    // API lấy doanh thu theo ngày
    Route::get('/api/daily-revenue', [DashboardController::class, 'getDailyRevenue'])->name('admin.daily-revenue');


    // Quản lý đơn hàng
    Route::prefix('don-hang')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('updateStatus');
    });

    // Báo cáo
    Route::get('/reports', function () {
        return Inertia::render('Admin/Reports/Index');
    })->name('reports.index');


    Route::middleware(['auth'])->group(function () {
        // Quản lý Bài viết (Posts)
        Route::get('/bai-viet', [PostController::class, 'index'])->name('posts.index');
        Route::get('/bai-viet/tao-moi', [PostController::class, 'create'])->name('posts.create');
        Route::post('/bai-viet', [PostController::class, 'store'])->name('posts.store');
        Route::get('/bai-viet/{post}/sua', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/bai-viet/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/bai-viet/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

        Route::get('/danh-muc-bai-viet', [PostCategoryController::class, 'index'])->name('post-categories.index');
        Route::post('/danh-muc-bai-viet', [PostCategoryController::class, 'store'])->name('post-categories.store');
        Route::put('/danh-muc-bai-viet/{postCategory}', [PostCategoryController::class, 'update'])->name('post-categories.update');
        Route::delete('/danh-muc-bai-viet/{postCategory}', [PostCategoryController::class, 'destroy'])->name('post-categories.destroy');

        // Quản lý Bình luận bài viết
        Route::get('/binh-luan', [PostCommentController::class, 'index'])->name('post-comments.index');
        Route::put('/binh-luan/{postComment}', [PostCommentController::class, 'update'])->name('post-comments.update'); // Để duyệt/ẩn bình luận
        Route::delete('/binh-luan/{postComment}', [PostCommentController::class, 'destroy'])->name('post-comments.destroy');
    });

    Route::prefix('kho')->name('kho.')->group(function () {
        Route::get('/', [MaterialController::class, 'index'])->name('index');

        Route::get('/nhap', [ImportReceiptController::class, 'create'])->name('nhap.create');
        Route::post('/nhap', [ImportReceiptController::class, 'store'])->name('nhap.store');
        Route::get('/nhap/lich-su', [ImportReceiptController::class, 'index'])->name('nhap.index');
        Route::get('/nhap/{importReceipt}/sua', [ImportReceiptController::class, 'edit'])->name('nhap.edit');
        Route::put('/nhap/{importReceipt}', [ImportReceiptController::class, 'update'])->name('nhap.update');
        Route::delete('/nhap/{importReceipt}', [ImportReceiptController::class, 'destroy'])->name('nhap.destroy');
        Route::get('/nhap/{importReceipt}', [ImportReceiptController::class, 'show'])->name('nhap.show');

        Route::post('/nguyen-lieu/nhanh', [ImportReceiptController::class, 'quickStoreMaterial'])
            ->name('nguyen-lieu.quick-store');

        Route::get('/dieu-chinh-ton', [StockAdjustmentController::class, 'index'])->name('dieu-chinh.index');
        Route::get('/dieu-chinh-ton/tao', [StockAdjustmentController::class, 'create'])->name('dieu-chinh.create');
        Route::post('/dieu-chinh-ton', [StockAdjustmentController::class, 'store'])->name('dieu-chinh.store');
    });

    Route::get('cong-thuc', [RecipeController::class, 'index'])->name('recipes.index');
    Route::get('cong-thuc/{variant}', [RecipeController::class, 'show'])->name('recipes.show');
    Route::post('cong-thuc/{variant}', [RecipeController::class, 'sync'])->name('recipes.sync');
    Route::delete('cong-thuc/nguyen-lieu/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
    Route::post('cong-thuc/{fromVariant}/sao-chep/{toVariant}', [RecipeController::class, 'copy'])
        ->name('recipes.copy');
    Route::get('cong-thuc/{variant}/gia-von', [RecipeController::class, 'cost'])->name('recipes.cost');
});
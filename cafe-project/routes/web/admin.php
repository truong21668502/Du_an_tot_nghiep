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
use App\Http\Controllers\Admin\ImportReceiptController;
use App\Http\Controllers\Admin\MaterialController;


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
    Route::get('/danh-muc', [CategoryController::class, 'index'])->name('category.index');          // Trang danh sách
    Route::post('/danh-muc', [CategoryController::class, 'store'])->name('category.store');          // Xử lý lưu mới
    Route::put('/danh-muc/{category}', [CategoryController::class, 'update'])->name('category.update');      // Xử lý cập nhật
    Route::delete('/danh-muc/{category}', [CategoryController::class, 'destroy'])->name('category.destroy'); // Xử lý xóa

    // Quản lý sản phẩm
    Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
    Route::post('/san-pham', [ProductController::class, 'store'])->name('products.store');
    Route::put('/san-pham/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/san-pham/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/hinh-anh-phu', [App\Http\Controllers\Admin\ProductController::class, 'storeImage'])->name('products.storeImage');
    Route::delete('/hinh-anh-phu/{image}', [App\Http\Controllers\Admin\ProductController::class, 'destroyImage'])->name('products.destroyImage');

    // Quản lý bàn ăn / bàn cà phê
    Route::get('/ban', [TableController::class, 'index'])->name('tables.index');            // Tải danh sách & bộ lọc
    Route::post('/ban', [TableController::class, 'store'])->name('tables.store');          // Lưu bàn mới
    Route::put('/ban/{table}', [TableController::class, 'update'])->name('tables.update');   // Cập nhật thông tin bàn
    Route::delete('/ban/{table}', [TableController::class, 'destroy'])->name('tables.destroy'); // Xóa bàn

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
    Route::delete('/nguoi-dung/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    
    //Route mới phục vụ riêng cho Thùng rác:
    Route::post('/nguoi-dung/{id}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('/nguoi-dung/{id}/force-delete', [UserController::class, 'forceDelete'])->name('admin.users.forceDelete');

    
    // Quản lý đơn hàng
    Route::get('/orders', function () {
        return Inertia::render('Admin/Orders/Index');
    })->name('orders.index');

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
    });
});
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\PostController;


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

    // 2. THÊM VÀO ĐÂY: Quản lý đặt bàn (Khợp chính xác với URL bên Vue)
    Route::get('/dat-ban', [ReservationController::class, 'index'])->name('reservations.index'); // Trang giám sát chính
    Route::put('/dat-ban/ghi-de/{id}', [ReservationController::class, 'overrideStatus'])->name('reservations.override'); // Quyền Admin ghi đè trạng thái

    // Quản lý users
    Route::get('/users', function () {
        return Inertia::render('Admin/Users/Index');
    })->name('users.index');

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
    });
});
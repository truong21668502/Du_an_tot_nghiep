<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;


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
});
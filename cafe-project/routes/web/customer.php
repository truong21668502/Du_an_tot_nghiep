<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\BookingController;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:CUSTOMER'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Giỏ hàng
    Route::get('/gio-hang', [CartController::class, 'index'])->name('customer.cart.index');

    Route::post('/cart/add', [CartController::class, 'add'])->name('customer.cart.add');

    Route::delete('/cart', [CartController::class, 'clear'])->name('customer.cart.clear');

    Route::post('/cart/voucher', [CartController::class, 'applyVoucher'])->name('customer.cart.voucher.apply');
    Route::delete('/cart/voucher', [CartController::class, 'removeVoucher'])->name('customer.cart.voucher.remove');

    Route::put('/cart/{cartItem}', [CartController::class, 'update'])->name('customer.cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('customer.cart.remove');

    // Đặt bàn
    Route::get('/dat-ban', [BookingController::class, 'index'])->name('booking');
    Route::get('/api/tables', [BookingController::class, 'tables'])->name('api.tables');
    Route::get('/api/reservations', [BookingController::class, 'reservations'])->name('api.reservations');
    Route::post('/api/reservations', [BookingController::class, 'store']);



    // Đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Orders');
    })->name('orders');
});
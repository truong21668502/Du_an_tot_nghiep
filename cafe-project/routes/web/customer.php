<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\BookingController;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:CUSTOMER'])->group(function () {
    
    Route::get('/ho-so', [ProfileController::class, 'info'])->name('profile.info');
    Route::get('/ho-so/mat-khau', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/ho-so/don-hang', [ProfileController::class, 'orders'])->name('profile.orders');
    Route::get('/ho-so/don-hang/{order}', [ProfileController::class, 'orderDetail'])->name('profile.orders.detail');
    Route::get('/ho-so/dia-chi', [ProfileController::class, 'addresses'])->name('profile.addresses');

    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/profile/update-avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::put('/profile/orders/{order}/cancel', [ProfileController::class, 'cancelOrder'])->name('profile.orders.cancel');

    // RESTful addresses
    Route::post('/profile/addresses', [ProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::put('/profile/addresses/{address}', [ProfileController::class, 'updateAddress'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [ProfileController::class, 'deleteAddress'])->name('profile.addresses.delete');
    Route::put('/profile/addresses/{address}/set-default', [ProfileController::class, 'setDefaultAddress'])->name('profile.addresses.set-default');

    // Giỏ hàng
    Route::get('/gio-hang', [CartController::class, 'index'])->name('customer.cart.index');

    Route::post('/cart/add', [CartController::class, 'add'])->name('customer.cart.add');

    Route::delete('/cart', [CartController::class, 'clear'])->name('customer.cart.clear');

    Route::post('/cart/voucher', [CartController::class, 'applyVoucher'])->name('customer.cart.voucher.apply');
    Route::delete('/cart/voucher', [CartController::class, 'removeVoucher'])->name('customer.cart.voucher.remove');

    Route::put('/cart/{cartItem}', [CartController::class, 'update'])->name('customer.cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('customer.cart.remove');

    // Đặt bàn
<<<<<<< HEAD
    Route::get('/dat-ban', function () {
        return Inertia::render('Booking');
    })->name('booking');
=======
    Route::get('/dat-ban', [BookingController::class, 'index'])->name('booking');
    Route::get('/api/tables', [BookingController::class, 'tables'])->name('api.tables');
    Route::get('/api/reservations', [BookingController::class, 'reservations'])->name('api.reservations');
    Route::post('/api/reservations', [BookingController::class, 'store']);


>>>>>>> 3d5ce311f1019c3f496118181692beb2f084dc89

    // Đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Orders');
    })->name('orders');
});

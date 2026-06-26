<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\FavoriteProductController;
//use App\Http\Controllers\Customer\CheckoutController;

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

    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/profile/update-avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::put('/profile/orders/{order}/cancel', [ProfileController::class, 'cancelOrder'])->name('profile.orders.cancel');

    Route::post('/menu/{product}/review', [ReviewController::class, 'store'])->name('product.review.store');
    Route::post('/favorites/toggle/{product}', [FavoriteProductController::class, 'toggle'])->name('favorites.toggle');
    Route::delete('/favorites/{product}', [FavoriteProductController::class, 'remove'])->name('favorites.remove');
    Route::get('/favorites', [FavoriteProductController::class, 'index'])->name('favorites.index');

    // Giỏ hàng
    Route::get('/gio-hang', [CartController::class, 'index'])->name('customer.cart.index');

    Route::post('/cart/add', [CartController::class, 'add'])->name('customer.cart.add');

    Route::delete('/cart', [CartController::class, 'clear'])->name('customer.cart.clear');

    // Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    // Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    // Route::get('/thanh-toan/thanh-cong/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    // Route::get('/thanh-toan/payment/{order}', [CheckoutController::class, 'payment'])->name('checkout.payment');
    // Route::post('/checkout/confirm-payment/{order}', [CheckoutController::class, 'confirmPayment'])->name('checkout.confirm-payment');
    // Route::get('/checkout/confirming/{order}', [CheckoutController::class, 'confirming'])->name('checkout.confirming');

    Route::post('/cart/voucher', [CartController::class, 'applyVoucher'])->name('customer.cart.voucher.apply');
    Route::delete('/cart/voucher', [CartController::class, 'removeVoucher'])->name('customer.cart.voucher.remove');

    Route::put('/cart/{cartItem}', [CartController::class, 'update'])->name('customer.cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('customer.cart.remove');

    // Đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Orders');
    })->name('orders');
});

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\FavoriteProductController;
use App\Http\Controllers\Customer\CheckoutController;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:CUSTOMER,ADMIN'])->group(function () {

    Route::prefix('ho-so')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'info'])->name('info');
        Route::get('/mat-khau', [ProfileController::class, 'password'])->name('password');
        Route::get('/don-hang', [ProfileController::class, 'orders'])->name('orders');
        Route::get('/don-hang/{order}', [ProfileController::class, 'orderDetail'])->name('orders.detail');
        Route::get('/dia-chi', [ProfileController::class, 'addresses'])->name('addresses');
    });

    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/profile/update-avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::put('/profile/orders/{order}/cancel', [ProfileController::class, 'cancelOrder'])->name('profile.orders.cancel');
    Route::post('/profile/user-addresses', [ProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::put('/profile/user-addresses/{address}', [ProfileController::class, 'updateAddress'])->name('profile.addresses.update');
    Route::delete('/profile/user-addresses/{address}', [ProfileController::class, 'deleteAddress'])->name('profile.addresses.delete');
    Route::put('/profile/user-addresses/{address}/set-default', [ProfileController::class, 'setDefaultAddress'])->name('profile.addresses.set-default');

    Route::post('/menu/{product}/review', [ReviewController::class, 'store'])->name('product.review.store');
    Route::post('/favorites/toggle/{product}', [FavoriteProductController::class, 'toggle'])->name('favorites.toggle');
    Route::delete('/favorites/{product}', [FavoriteProductController::class, 'remove'])->name('favorites.remove');
    Route::get('/favorites', [FavoriteProductController::class, 'index'])->name('favorites.index');

    Route::prefix('gio-hang')->name('customer.cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/', [CartController::class, 'add'])->name('add');

        Route::delete('/', [CartController::class, 'clear'])->name('clear');
        Route::post('/voucher', [CartController::class, 'applyVoucher'])->name('voucher.apply');
        Route::delete('/voucher', [CartController::class, 'removeVoucher'])->name('voucher.remove');

            Route::patch('/{cartItem}', [CartController::class, 'update'])->name('update');
        Route::delete('/{cartItem}', [CartController::class, 'remove'])->name('remove');
    });

    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('customer.checkout.index');

    // Đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Orders');
    })->name('orders');
});

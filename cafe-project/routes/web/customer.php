<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    Route::get('/gio-hang', function () {
        return Inertia::render('Cart', [
            'cart' => [
                'id' => 1,
                'user_id' => 1,
                'items' => [
                    [
                        'id' => 101,
                        'product' => [
                            'id' => 1,
                            'name' => 'Classic Latte',
                            'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJ0vv5WLyfbJokI9s7WdH-IN_UQrBJuzAKaR3HQtGdhCJKL8yyjBCJXCGrfuP3hlaLAhxD9stXOhPkKwss2yxQeGB2eYnyuKJAFX_DCBLDui0_8ssJnFjRyeXg-pbwSDssT5YD8o9SLEzclAMSKgvqLdeunCDEuZhjWRIy_HOQBoubABuxCFxfrKe6R8zhJKFAIKYLZqaCU3z8-SVOrv_DERg4IAwOXuOZ7jdAl5x3un4x516UkEBraiNNo_3pvR3VpdCZyvXxuHQ'
                        ],
                        'variant' => [
                            'id' => 1,
                            'size' => 'Vừa',
                            'sugar' => 'Ít đường',
                            'ice' => 'Đá bình thường',
                            'price' => 65000,
                        ],
                        'quantity' => 2,
                        'note' => 'Nóng, thêm shot espresso'
                    ]
                ]
            ]
        ]);
    })->name('cart');

    // Đặt bàn
    Route::get('/dat-ban', function () {
        return Inertia::render('Booking', [
            'auth' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Nguyễn Văn A',
                    'email' => 'nguyenvana@example.com',
                    'phone' => '0912345678',
                    'avatar' => null,
                ]
            ]
        ]);
    })->name('booking');

    // Đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Orders');
    })->name('orders');
});
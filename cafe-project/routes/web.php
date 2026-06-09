<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Events\TestEvent;
use App\Models\TestRealTime;

// Trang chủ
Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Thực đơn
Route::get('/thuc-don', function(){
    return Inertia::render('Menu');
})->name('menu');

// Về chúng tôi
Route::get('/ve-chung-toi', function(){
    return Inertia::render('About');
})->name('about');

// Đặt bàn (có thể thêm sau)
Route::get('/dat-ban', function(){
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

// Đơn hàng (có thể thêm sau)
Route::get('/don-hang', function(){
    return Inertia::render('Orders');
})->name('orders');

Route::get('/gio-hang', function(){
    return Inertia::render('Cart',[
        'cart' => [
            'id' => 1,
            'user_id' => 1
        ],
        'cartItems' => [
            [
                'id' => 101,
                'cart_id' => 1,
                'product_id' => 1,
                'product' => [
                    'id' => 1,
                    'name' => 'Classic Latte',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJ0vv5WLyfbJokI9s7WdH-IN_UQrBJuzAKaR3HQtGdhCJKL8yyjBCJXCGrfuP3hlaLAhxD9stXOhPkKwss2yxQeGB2eYnyuKJAFX_DCBLDui0_8ssJnFjRyeXg-pbwSDssT5YD8o9SLEzclAMSKgvqLdeunCDEuZhjWRIy_HOQBoubABuxCFxfrKe6R8zhJKFAIKYLZqaCU3z8-SVOrv_DERg4IAwOXuOZ7jdAl5x3un4x516UkEBraiNNo_3pvR3VpdCZyvXxuHQ'
                ],
                'variant_id' => 1,
                'variant' => [
                    'id' => 1,
                    'size' => 'Vừa',
                    'sugar' => 'Ít đường',
                    'ice' => 'Đá bình thường',
                    'price' => 65000,
                    'image' => null
                ],
                'quantity' => 2,
                'note' => 'Nóng, thêm shot espresso'
            ],
            [
                'id' => 102,
                'cart_id' => 1,
                'product_id' => 2,
                'product' => [
                    'id' => 2,
                    'name' => 'Matcha Cold Foam',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_JPAxQlo8EQOj3lntcu18YnYMwmwpZ9Wt88yeBKt-F0m18r8m2UXWMmC44Slmx7s8LkDgsChh4NwMAV7AdQmqQ6vpsed4Y7e0lkzAn_nyOYtjvbJ2niuvJ0yj_28a7Aq69IrGcVA6M3PPAjPUzumGENd7zTVCcQTBWM5ysv1xQshCkwbgPjrk7vMxQt2-q-62LjXEwSChBBNKZ8vbrQDa7vYWAKN4VbZk9S0tZ20X5EIhDgV5VDurlW7RIt9uANaWIwIpmhHtoOM'
                ],
                'variant_id' => 3,
                'variant' => [
                    'id' => 3,
                    'size' => 'Lớn',
                    'sugar' => 'Không đường',
                    'ice' => 'Đá bình thường',
                    'price' => 75000,
                    'image' => null
                ],
                'quantity' => 1,
                'note' => null
            ],
            [
                'id' => 103,
                'cart_id' => 1,
                'product_id' => 4,
                'product' => [
                    'id' => 4,
                    'name' => 'Butter Croissant',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDioBp9YsnihFc9mZ1ekWpk6xtNrgipcVIIfZeONW3YpZ1Fb1sih8O28f-321mGr0gYQP1AlD5kjnNXt7RMw9FWbS7MrkKSKg2XJKPpFv1MAkiKUyNC2BxWbsEgaVfHSljwnoMLzH48HeAfvNHSujr78nbD_ikNOoUiaCUBgEFqac8yahsIH4R8USHbWOAunnHamRKK97iXjCNc2kxnSVr4NohFvHjZNR4abtv_0_nWmlHG2VrgrOLFNcQ4ifXwp-yq6WsjOzH-fgs'
                ],
                'variant_id' => 5,
                'variant' => [
                    'id' => 5,
                    'size' => null,
                    'sugar' => null,
                    'ice' => null,
                    'price' => 45000,
                    'image' => null
                ],
                'quantity' => 3,
                'note' => 'Hơi nóng giúp'
            ]
        ]
    ]);
})->name('cart');

Route::post('/lien-he/gui', function(){
    return response()->json([
    'success' => true
]);
})->name('contact.send');

Route::get('/lien-he', function(){
    return Inertia::render('Contact');
})->name('contact');

// Dashboard (cần đăng nhập)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile routes (cần đăng nhập)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('bai-viet', function(){
    return Inertia::render('Blog');
})->name('blog');

// Real-time testing
Route::get('/realtime', function () {
    $existingData = TestRealTime::latest()->limit(10)->get();
    return Inertia::render('RealTime', [
        'existingData' => $existingData
    ]);
});

Route::get('/fire', function () {
    $newData = TestRealTime::create([
        'content' => 'Tin nhắn tự động lúc ' . now()->toTimeString(),
    ]);

    return 'Đã lưu vào database bản ghi số: ' . $newData->id;
});


use App\Http\Controllers\TestRealTimeController;

Route::get(
    '/test-real-times',
    [TestRealTimeController::class, 'index']
);

Route::prefix('api')->group(function () {
    Route::get(
        '/test-real-times',
        [TestRealTimeController::class, 'list']
    );

    Route::post(
        '/test-real-times',
        [TestRealTimeController::class, 'store']
    );

    Route::put(
        '/test-real-times/{testRealTime}',
        [TestRealTimeController::class, 'update']
    );

    Route::delete(
        '/test-real-times/{testRealTime}',
        [TestRealTimeController::class, 'destroy']
    );
});

require __DIR__.'/auth.php';
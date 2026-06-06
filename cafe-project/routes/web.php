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

// Giỏ hàng (có thể thêm sau)
Route::get('/gio-hang', function(){
    return Inertia::render('Cart');
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
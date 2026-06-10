<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\TestRealTime;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\PostController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

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
Route::get('/thuc-don', [ProductController::class, 'index'])->name('customer.menu.index');

// Về chúng tôi
Route::get('/ve-chung-toi', function () {
    return Inertia::render('About');
})->name('about');

// Liên hệ
Route::get('/lien-he', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::post('/lien-he/gui', function () {
    return response()->json([
        'success' => true
    ]);
})->name('contact.send');

// Blog
Route::get('/bai-viet', [PostController::class, 'index'])->name('blog.index');
Route::get('/bai-viet/{slug}', [PostController::class, 'show'])->name('blog.show');

// Real-time testing (public)
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
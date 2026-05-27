<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Events\TestEvent;
use App\Models\TestRealTime;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/realtime', function () {
    $existingData = TestRealTime::latest()->limit(10)->get();

    return Inertia::render('RealTime', [
        'existingData' => $existingData
    ]);
});



Route::get('/fire', function () {
    // Chèn dữ liệu mới ngẫu nhiên vào database
    $newData = TestRealTime::create([
        'content' => 'Tin nhắn tự động lúc ' . now()->toTimeString()
    ]);

    return 'Đã lưu vào database bản ghi số: ' . $newData->id;
});


require __DIR__.'/auth.php';

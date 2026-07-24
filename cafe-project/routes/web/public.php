<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\TestRealTime;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\PostController;
use App\Http\Controllers\Customer\PostCommentController;
use App\Http\Controllers\Customer\TableOrderController;
use App\Http\Controllers\Customer\ContactController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\VnpayController;

use App\Http\Controllers\Customer\ChatController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Trang chủ
use App\Http\Controllers\Customer\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');



// Thực đơn
Route::get('/thuc-don', [ProductController::class, 'index'])->name('customer.menu.index');
Route::get('/thuc-don/{slug}', [ProductController::class, 'show'])->name('product.show');

//!!! quan trong không rờ lung tung!!!
Route::get('/ban/{qr_code}', [TableOrderController::class, 'index'])->name('table.order');


Route::post('/orders', [OrderController::class, 'store'])->name('customer.orders.store');
Route::get('/don-hang/{order}/pending', [OrderController::class, 'pending'])->name('customer.orders.pending');

Route::get('/vnpay-ket-qua', [VnpayController::class, 'return'])->name('vnpay.return');


// Về chúng tôi
Route::get('/ve-chung-toi', function () {
    return Inertia::render('About');
})->name('about');

// Liên hệ
Route::get('/lien-he', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');

route::get('/cc' , function () {
    return Inertia::render('cc');
});

// Blog
Route::get('/bai-viet', [PostController::class, 'index'])->name('blog.index');
Route::get('/bai-viet/danh-muc/{slug}', [PostController::class, 'category'])->name('blog.category');
Route::get('/bai-viet/{slug}', [PostController::class, 'dispatch'])->name('blog.dispatch');

Route::middleware('auth')->group(function () {
    Route::post('/bai-viet/{postId}/binh-luan', [PostCommentController::class, 'store'])
        ->name('comments.store');

    Route::put('/bai-viet/{postId}/binh-luan/{comment}', [PostCommentController::class, 'update'])
        ->name('comments.update');

    Route::delete('/bai-viet/{postId}/binh-luan/{comment}', [PostCommentController::class, 'destroy'])
        ->name('comments.destroy');
});


// !!!!!!!!!!!!!!! KHÔNG TỰ TIỆN RỜ VÀO QUAN TRỌNG !!!!!!!!!!!!!!!!!!!!!!!!!!
// Route::post('/chat', [ChatController::class, 'message'])->name('chat.message');
Route::get('/chat-test', function () {
    return Inertia::render('ChatTest');
});

Route::prefix('chat')->group(function () {
    Route::post('/', [ChatController::class, 'message'])->name('chat.message');
    
    // History management
    Route::get('/history', [ChatController::class, 'getHistory'])->name('chat.history');
    Route::get('/conversations', [ChatController::class, 'getConversations'])->name('chat.conversations');
    
    // Delete operations
    Route::delete('/messages', [ChatController::class, 'deleteMessages'])->name('chat.delete-messages');
    Route::delete('/messages/all', [ChatController::class, 'deleteAllMessages'])->name('chat.delete-all-messages');
    Route::delete('/conversation', [ChatController::class, 'deleteConversation'])->name('chat.delete-conversation');
    
    // Summary
    Route::post('/regenerate-summary', [ChatController::class, 'regenerateSummary'])->name('chat.regenerate-summary');
});







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
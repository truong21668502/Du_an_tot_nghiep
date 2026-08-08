<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\TableController;
use App\Http\Controllers\Staff\StaffAiController;
use App\Models\Order;
use App\Models\Table;
use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:STAFF,ADMIN'])->prefix('nhan-vien')->name('staff.')->group(function () {
    
    // AI Chatbot Route
    Route::get('/ai-conversations', [StaffAiController::class, 'getConversations'])->name('ai.conversations');
    Route::get('/ai-history', [StaffAiController::class, 'getHistory'])->name('ai.history');
    Route::post('/ai-chat', [StaffAiController::class, 'sendChatMessage'])->name('ai.chat');
    Route::delete('/ai-conversation', [StaffAiController::class, 'deleteConversation'])->name('ai.delete-conversation');

    // Route dashboard
    Route::get('/bang-dieu-khien', [DashboardController::class, 'index'])->name('dashboard');

    // Route quản lý đơn hàng
    Route::get('/don-hang', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/du-lieu-tao-don', [OrderController::class, 'createData'])->name('orders.create-data');
    Route::post('/don-hang', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('/don-hang/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::patch('/don-hang/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/don-hang/{order}/huy', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/don-hang/{order}/xac-nhan-thanh-toan', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::get('/don-hang/{order}/vnpay-url', [OrderController::class, 'getVnpayUrl'])->name('orders.vnpay-url');

    // Route quản lý bàn (Sơ đồ mặt bằng)
    Route::get('/so-do-ban', [TableController::class, 'index'])->name('tables.index');
    Route::patch('/so-do-ban/{table}/trang-thai', [TableController::class, 'updateStatus'])->name('tables.update-status');
    Route::post('/so-do-ban/{fromTable}/gop-vao/{toTable}', [TableController::class, 'mergeTable'])->name('tables.merge');
    Route::patch('/so-do-ban/{table}/tach-ban', [TableController::class, 'unmergeTable'])->name('tables.unmerge');

});
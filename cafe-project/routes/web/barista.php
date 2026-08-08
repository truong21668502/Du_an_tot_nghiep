<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Barista\BaristaController;
use App\Http\Controllers\Barista\BaristaAiController;

/*
|--------------------------------------------------------------------------
| Barista Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:BARISTA,ADMIN'])->prefix('pha-che')->name('barista.')->group(function () {

    // AI Chatbot Routes
    Route::get('/ai-conversations', [BaristaAiController::class, 'getConversations'])->name('ai.conversations');
    Route::get('/ai-history', [BaristaAiController::class, 'getHistory'])->name('ai.history');
    Route::post('/ai-chat', [BaristaAiController::class, 'sendChatMessage'])->name('ai.chat');
    Route::delete('/ai-conversation', [BaristaAiController::class, 'deleteConversation'])->name('ai.delete-conversation');

    // Hàng đợi pha chế (màn hình chính)
    Route::get('/hang-doi', [BaristaController::class, 'queue'])->name('queue');

    // Lịch sử pha chế hôm nay
    Route::get('/lich-su', [BaristaController::class, 'history'])->name('history');

    // Cập nhật trạng thái từng món (PENDING → PREPARING → COMPLETED)
    Route::patch('/mon/{detail}/trang-thai', [BaristaController::class, 'updateStatus'])->name('detail.update-status');

    // Dashboard tổng quan (redirect về queue)
    Route::get('/bang-dieu-khien', function () {
        return redirect()->route('barista.queue');
    })->name('dashboard');
});
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Barista Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:BARISTA,ADMIN'])->prefix('pha-che')->name('barista.')->group(function () {
    
    // Bảng điều khiển chung
    Route::get('/bang-dieu-khien', function () {
        return Inertia::render('Barista/Dashboard');
    })->name('dashboard');

    // Hàng đợi pha chế (Màn hình chính của Barista)
    Route::get('/hang-doi', function () {
        return Inertia::render('Barista/Queue');
    })->name('queue');

    // Xem lịch sử đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Barista/Orders/Index');
    })->name('orders.index');
});
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Barista Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:BARISTA'])->prefix('barista')->name('barista.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Barista/Dashboard');
    })->name('dashboard');

    // Xem đơn hàng cần pha chế
    Route::get('/orders', function () {
        return Inertia::render('Barista/Orders/Index');
    })->name('orders.index');

    // Queue pha chế
    Route::get('/queue', function () {
        return Inertia::render('Barista/Queue');
    })->name('queue');
});
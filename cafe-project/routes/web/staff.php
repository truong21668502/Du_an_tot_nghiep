<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:STAFF'])->prefix('staff')->name('staff.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Staff/Dashboard');
    })->name('dashboard');

    // Quản lý đơn hàng
    Route::get('/orders', function () {
        return Inertia::render('Staff/Orders/Index');
    })->name('orders.index');

    // Quản lý đặt bàn
    Route::get('/bookings', function () {
        return Inertia::render('Staff/Bookings/Index');
    })->name('bookings.index');
});
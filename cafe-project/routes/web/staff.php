<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/

    Route::middleware(['auth', 'role:STAFF'])->prefix('nhan-vien')->name('staff.')->group(function () {
    
    // Dashboard
    Route::get('/bang-dieu-khien', function () {
        return Inertia::render('Staff/Dashboard');
    })->name('dashboard');

    // Quản lý đơn hàng
    Route::get('/don-hang', function () {
        return Inertia::render('Staff/Orders');
    })->name('orders.index');

    // Quản lý đặt bàn
    Route::get('/dat-ban', function () {
        return Inertia::render('Staff/Bookings');
    })->name('bookings.index');
});
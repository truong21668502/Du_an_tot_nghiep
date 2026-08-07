<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shipper\OrderController;

Route::middleware(['auth', 'role:SHIPPER,ADMIN'])->prefix('giao-hang')->name('shipper.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/{order}/nhan', [OrderController::class, 'accept'])->name('orders.accept');
    Route::get('/{order}', [OrderController::class, 'show'])->name('delivery');
    Route::post('/{order}/hoan-thanh', [OrderController::class, 'complete'])->name('orders.complete');
}); 
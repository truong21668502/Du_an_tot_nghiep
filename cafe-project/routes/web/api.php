<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestRealTimeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {
    Route::get('/test-real-times', [TestRealTimeController::class, 'list']);
    Route::post('/test-real-times', [TestRealTimeController::class, 'store']);
    Route::put('/test-real-times/{testRealTime}', [TestRealTimeController::class, 'update']);
    Route::delete('/test-real-times/{testRealTime}', [TestRealTimeController::class, 'destroy']);
});

Route::get('/test-real-times', [TestRealTimeController::class, 'index']);
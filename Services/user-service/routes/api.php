<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('customer')->middleware('user.type:customer')->group(function () {
        Route::get('/profile', [AuthController::class, 'me']);
    });
    
    Route::prefix('restaurant')->middleware('user.type:restaurant')->group(function () {
        Route::get('/profile', [AuthController::class, 'me']);
    });
    
    Route::prefix('courier')->middleware('user.type:courier')->group(function () {
        Route::get('/profile', [AuthController::class, 'me']);
    });
});

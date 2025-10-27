<?php

use App\Http\Controllers\BasketController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function () {

    Route::prefix('restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);
    });
    
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductsController::class, 'index']);
        Route::get('/{product}', [ProductsController::class, 'show']);
    });
   
    Route::prefix('basket')->group(function () {
        Route::get('/', [BasketController::class, 'show']);
        Route::post('/{product}', [BasketController::class, 'create']);
        Route::delete('/{product}', [BasketController::class, 'delete']);
    });
});

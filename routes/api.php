<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RoleController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;

Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users', [UserController::class, 'index'])->middleware(AdminMiddleware::class);
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware(AdminMiddleware::class);
    Route::post('/users', [UserController::class, 'store'])->middleware(AdminMiddleware::class);
    Route::put('/users/update/{id}', [UserController::class, 'update'])->middleware(AdminMiddleware::class);
    Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])->middleware(AdminMiddleware::class);

    Route::controller(PaymentMethodController::class)->group(function () {
        Route::get('/paymentMethods', 'index');
        Route::get('/paymentMethods/{id}', 'show');
        Route::post('/paymentMethods/create', 'store');
        Route::put('/paymentMethods/update/{id}', 'update');
        Route::delete('/paymentMethods/delete/{id}', 'destroy');
    });    
});


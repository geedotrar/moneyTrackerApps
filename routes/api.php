<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RoleController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;

Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::middleware(['auth:api', AdminMiddleware::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/create', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
    
    Route::prefix('paymentMethods')->controller(PaymentMethodController::class)->group(function () {
        Route::get('/', 'index'); 
        Route::get('/{id}', 'show'); 
        Route::post('/create', 'store'); 
        Route::put('/update/{id}', 'update'); 
        Route::delete('/delete/{id}', 'destroy'); 
    });
    
    Route::prefix('incomes')->controller(IncomeController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/create', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
});


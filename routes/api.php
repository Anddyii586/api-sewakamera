<?php

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RentalController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::get('refresh', [AuthController::class, 'refresh']);
    Route::get('logout', [AuthController::class, 'logout']);

    Route::apiResource('categories', CategoryController::class);

    Route::get('items/by-category/{category}', [ItemController::class, 'byCategory']);
    Route::apiResource('items', ItemController::class);

    Route::get('rentals/my-rentals', [RentalController::class, 'myRentals']);
    Route::get('rentals', [RentalController::class, 'index']);
    Route::post('rentals', [RentalController::class, 'store']);
    Route::get('rentals/{rental}', [RentalController::class, 'show']);
    Route::patch('rentals/{rental}/approve', [RentalController::class, 'approve']);
    Route::patch('rentals/{rental}/rented', [RentalController::class, 'rented']);
    Route::patch('rentals/{rental}/return', [RentalController::class, 'markReturned']);
    Route::patch('rentals/{rental}/cancel', [RentalController::class, 'cancel']);
    Route::delete('rentals/{rental}', [RentalController::class, 'destroy']);

    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
    Route::patch('payments/{payment}/paid', [PaymentController::class, 'paid']);
    Route::patch('payments/{payment}/failed', [PaymentController::class, 'failed']);
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);

    Route::get('logs', [LogController::class, 'index']);
    Route::get('logs/{log}', [LogController::class, 'show']);
});

Route::fallback(fn () => ApiFormatter::error('Route not found.', 404));

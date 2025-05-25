<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserBalaceController;
use App\Http\Controllers\TransactionController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API',
    ], 200);
})->name('login');

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:api', 'transactions_entries'])->group(function () {
    Route::prefix('balance')->group(function () {
        Route::get('/', [UserBalaceController::class, 'get']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'get']);
        Route::post('/transfer', [TransactionController::class, 'transfer']);
        Route::post('/revert', [TransactionController::class, 'revert']);
    });
});

Route::post('transactions/deposit', [TransactionController::class, 'depositSimulation']);
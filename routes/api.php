<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\TransactionController;

// ─── Rotas Públicas ──────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/checkout', [TransactionController::class, 'store']); // Compra pública

// ─── Rotas Privadas (Bearer Token via Sanctum) ──────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('gateways', GatewayController::class);

    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund']);
});

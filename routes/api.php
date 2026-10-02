<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // --- Public Endpoints ---
    Route::prefix('auth')->group(function () {
        Route::post('/register-tenant', [AuthController::class, 'registerTenant']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::get('/plans', [SubscriptionController::class, 'plans']);

    // --- Protected Multi-Tenant Endpoints ---
    Route::middleware(['auth:sanctum', 'tenant', 'throttle:api'])->group(function () {

        // Authenticated Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Tenant Settings
        Route::prefix('tenant')->group(function () {
            Route::get('/', [TenantController::class, 'show']);
            Route::put('/', [TenantController::class, 'update']);
        });

        // Subscriptions
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [SubscriptionController::class, 'current']);
            Route::post('/change-plan', [SubscriptionController::class, 'changePlan']);
            Route::post('/cancel', [SubscriptionController::class, 'cancel']);
        });

        // Customers CRUD
        Route::apiResource('customers', CustomerController::class);

        // Team Users CRUD
        Route::apiResource('users', UserController::class);

        // Dashboard & Analytics
        Route::prefix('dashboard')->group(function () {
            Route::get('/analytics', [DashboardController::class, 'analytics']);
            Route::get('/usage', [DashboardController::class, 'usage']);
        });
    });
});

<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\Type1Controller;
use App\Http\Controllers\Type2Controller;
use App\Http\Controllers\Type3Controller;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
// 無限制 公開路由
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/backend-login', [AuthController::class, 'backendLogin']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
});

// Protected Auth Routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Protected Admin/Backend Routes
// 後台功能
/*中介層 (Middleware) BackendAccess，其核心邏輯為：檢查當前登入的使用者是否具備「至少一個」Spatie
    的後台角色 ($user->roles()->count() > 0)。
- 將所有 /api/admin/* 開頭的路由都掛上了這個 backend 中介層。現在，即使前台會員取得了
    Token，只要他們試圖呼叫管理員專用的 API，就會被直接阻擋並收到 403 權限不足，無法登入後台。 的錯誤。*/
Route::middleware(['auth:sanctum', 'backend'])->prefix('admin')->group(function () {
    // User Management
    Route::get('/users/frontend', [UserManagementController::class, 'getFrontendUsers']);
    Route::get('/users/backend', [UserManagementController::class, 'getBackendUsers']);

    // Permission and Identity Management
    Route::put('/users/{user}/venues', [UserManagementController::class, 'syncUserVenues']);
    Route::post('/users/{user}/upgrade-coach', [UserManagementController::class, 'upgradeToCoach']);

    // Create/Edit/Delete Backend Admins and Staff
    Route::middleware('permission:backend_user.manage')->group(function () {
        Route::post('/admins', [UserManagementController::class, 'createBackendUser']);
        Route::put('/admins/{user}', [UserManagementController::class, 'updateBackendUser']);
        Route::delete('/admins/{user}', [UserManagementController::class, 'deleteBackendUser']);
    });

    // Create Venues (System Admin Only)
    // 系統管理員
    Route::middleware('role:System Admin')->group(function () {
        Route::post('/venues', [\App\Http\Controllers\Api\VenueController::class, 'store']);
    });
});




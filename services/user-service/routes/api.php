<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\VenueController;

// Existing legacy/mock routes
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);

// Public Auth Routes
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
    Route::get('/verify', [AuthController::class, 'verify']);
});

// Protected Admin/Backend Routes
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
    Route::middleware('role:System Admin')->group(function () {
        Route::post('/venues', [VenueController::class, 'store']);
    });
});

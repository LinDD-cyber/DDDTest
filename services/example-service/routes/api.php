<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExampleController;

// Example routes protected by API Gateway auth
Route::middleware('gateway.auth')->group(function () {
    Route::get('/examples', [ExampleController::class, 'index']);
    Route::get('/examples/{id}', [ExampleController::class, 'show']);
    Route::post('/examples', [ExampleController::class, 'store']);
});

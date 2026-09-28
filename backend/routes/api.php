<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\SourceController;

Route::get('/offers', [CatalogController::class, 'index']);
Route::get('/offers/{offer}', [CatalogController::class, 'show']);
Route::get('/filters', [CatalogController::class, 'filters']);
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/preferences', [AuthController::class, 'preferences']);
    Route::apiResource('watches', WatchController::class)->except(['show']);
    Route::get('/notifications', [AlertController::class, 'index']);
    Route::post('/notifications/{id}/read', [AlertController::class, 'read']);
    Route::middleware('owner')->prefix('admin')->group(function () {
        Route::apiResource('sources', SourceController::class)->except(['show']);
        Route::post('/sources/{source}/run', [SourceController::class, 'run']);
        Route::post('/imports', [SourceController::class, 'import'])->middleware('throttle:20,1');
    });
});

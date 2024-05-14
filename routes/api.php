<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Public product routes (read-only)
Route::get('product', [ProductController::class, 'show']);
Route::get('products', [ProductController::class, 'index']);

// Public category routes (read-only)
Route::get('category', [CategoryController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);

// Routes protected by sanctum auth middleware
Route::middleware('auth:sanctum')->group(function () {
    // User routes (read-only)
    Route::get('user/{id}', [UserController::class, 'show']);
    Route::get('users', [UserController::class, 'index']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        // Product CRUD operations
        Route::post('product', [ProductController::class, 'store']);
        Route::put('product/{id}', [ProductController::class, 'update']);
        Route::delete('product/{id}', [ProductController::class, 'destroy']);

        // Category CRUD operations
        Route::post('category', [CategoryController::class, 'store']);
        Route::put('category/{id}', [CategoryController::class, 'update']);
        Route::delete('category/{id}', [CategoryController::class, 'destroy']);
    });
});

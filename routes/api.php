<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RoundsController;
use App\Http\Controllers\ScoringMethodController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [UserController::class, 'register']);
Route::post('/register/invite', [UserController::class, 'registerWithInvite']);
Route::post('/login', [UserController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (require authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Players
    Route::get('/players', [PlayerController::class, 'index']);
    Route::post('/players', [PlayerController::class, 'store']);
    Route::put('/players/{id}', [PlayerController::class, 'update']);
    Route::delete('/players/{id}', [PlayerController::class, 'destroy']);

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/tees', [CourseController::class, 'getTees']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::post('/courses/{courseId}/tees', [CourseController::class, 'addTee']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

    // Scoring Methods
    Route::get('/scoring-methods', [ScoringMethodController::class, 'index']);

    // Rounds
    Route::get('/rounds', [RoundsController::class, 'index']);
    Route::post('/rounds', [RoundsController::class, 'store']);
    Route::get('/rounds/{id}', [RoundsController::class, 'show']);
    Route::put('/rounds/{id}', [RoundsController::class, 'update']);
    Route::delete('/rounds/{id}', [RoundsController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

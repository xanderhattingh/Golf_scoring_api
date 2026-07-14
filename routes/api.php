<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TournamentsController;
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
Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);

/*
|--------------------------------------------------------------------------
| Protected Routes (require authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [UserController::class, 'me']);
    Route::put('/user', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    // Friends
    Route::get('/friends', [FriendController::class, 'index']);
    Route::post('/friends', [FriendController::class, 'store']);
    Route::delete('/friends/{friendId}', [FriendController::class, 'destroy']);

    // Players
    Route::get('/players', [PlayerController::class, 'index']);
    Route::get('/players/search', [PlayerController::class, 'search']);
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

    // Tees
    Route::post('/tees', [CourseController::class, 'createTee']);

    // Scoring Methods
    Route::get('/scoring-methods', [ScoringMethodController::class, 'index']);

    // Rounds
    Route::get('/rounds', [RoundsController::class, 'index']);
    Route::post('/rounds', [RoundsController::class, 'store']);
    Route::get('/rounds/{id}', [RoundsController::class, 'show']);
    Route::put('/rounds/{id}', [RoundsController::class, 'update']);
    Route::delete('/rounds/{id}', [RoundsController::class, 'destroy']);

    //Tournaments
    Route::get('/tournaments', [TournamentsController::class, 'index']);
    Route::get('/tournaments/lookup/{code}', [TournamentsController::class, 'lookup']);
    Route::post('/tournaments', [TournamentsController::class, 'store']);
    Route::delete('/tournaments/{id}', [TournamentsController::class, 'destroy']);

});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

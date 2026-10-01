<?php

use App\Http\Controllers\Api\Admin\AdminArticleController;
use App\Http\Controllers\Api\Admin\AdminExerciseController;
use App\Http\Controllers\Api\Admin\AdminRoutineController;
use App\Http\Controllers\Api\Admin\AdminStatsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BodyMeasurementController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoutineController;
use App\Http\Controllers\Api\TaxonomyController;
use App\Http\Controllers\Api\WorkoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Public Content & Meta Routes
|--------------------------------------------------------------------------
*/
Route::get('/exercises', [ExerciseController::class, 'index']);
Route::get('/exercises/{key}', [ExerciseController::class, 'show']);

Route::get('/routines', [RoutineController::class, 'index']);
Route::get('/routines/{key}', [RoutineController::class, 'show']);

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{key}', [ArticleController::class, 'show']);

Route::get('/taxonomies', [TaxonomyController::class, 'index']);

Route::post('/chat', [ChatController::class, 'ask']) ->middleware('throttle:chat');
/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Sanctum Protected)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // User Profile & Settings
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::get('/me/favorites', [ProfileController::class, 'favorites']);
    Route::get('/me/saved', [ProfileController::class, 'favorites']);

    // Favorites / Saving Toggles
    Route::post('/routines/{routine}/save', [FavoriteController::class, 'saveRoutine']);
    Route::delete('/routines/{routine}/save', [FavoriteController::class, 'unsaveRoutine']);
    Route::post('/exercises/{exercise}/save', [FavoriteController::class, 'saveExercise']);
    Route::delete('/exercises/{exercise}/save', [FavoriteController::class, 'unsaveExercise']);

    // Dashboard & Metrics
    Route::get('/me/dashboard', [DashboardController::class, 'index']);
    Route::get('/me/exercises/{exercise}/progression', [DashboardController::class, 'exerciseProgression']);

    // User Workouts & Measurements
    Route::prefix('me')->group(function () {
        Route::get('/workouts', [WorkoutController::class, 'index']);
        Route::post('/workouts', [WorkoutController::class, 'store']);
        Route::get('/workouts/{id}', [WorkoutController::class, 'show']);

        Route::get('/measurements', [BodyMeasurementController::class, 'index']);
        Route::post('/measurements', [BodyMeasurementController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Sanctum + Admin Middleware)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('exercises', AdminExerciseController::class)->except(['show']);
        // `show` is included here (unlike the other two) because the day-by-day
        // builder has to load a routine's full programme before editing it.
        Route::apiResource('routines', AdminRoutineController::class);
        Route::apiResource('articles', AdminArticleController::class)->except(['show']);

        // Overview cards + activity feed. The frontend called both of these
        // before they existed, so the admin dashboard 404'd on load.
        Route::get('/stats', [AdminStatsController::class, 'stats']);
        Route::get('/activity', [AdminStatsController::class, 'activity']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
    });
});

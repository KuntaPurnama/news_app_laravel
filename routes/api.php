<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/is-logged-in/{token}', [AuthController::class, 'isLoggedIn']);
    Route::post('/activate-account/{token}', [AuthController::class, 'activateAccount']);
    Route::post('/forgot-password', [AuthController::class, 'forgetPasswordToken']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/reset-forgot-password', [AuthController::class, 'resetForgotPassword']);
    Route::post('/validate-reset-password', [AuthController::class, 'validateResetPasswordToken']);
});

Route::prefix('/news')->group(function () {
    Route::post('/get-news', [NewsController::class, 'getNewsAndRelatedData']);
    Route::get('/news-summary/{index}/{size}', [NewsController::class, 'getNewsSummary']);
    Route::get('/this-week-news/{size}', [NewsController::class, 'getThisWeekNews']);
    Route::get('/get-more-news/{size}', [NewsController::class, 'getMoreNews']);
    Route::get('/get-all-topics', [NewsController::class, 'getAllTopics']);
    Route::get('/get-all-authors', [NewsController::class, 'getAllAuthors']);
    Route::get('/get-all-sources', [NewsController::class, 'getAllSources']);
    Route::post('/search', [NewsController::class, 'searchNews']);
});

Route::prefix('/user')->group(function () {
    Route::post('/update', [UserController::class, 'updateUser']);
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsAPIController;
use App\Http\Controllers\NewsController;

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

Route::get('/test', [NewsAPIController::class, 'getTopHeadlinesNewsAPI']);
Route::post('/get-news', [NewsController::class, 'getNewsAndRelatedData']);
Route::get('/news-summary/{index}/{size}', [NewsController::class, 'getNewsSummary']);
Route::get('/this-week-news/{size}', [NewsController::class, 'getThisWeekNews']);
Route::get('/get-more-news/{size}', [NewsController::class, 'getMoreNews']);
Route::get('/get-all-topics', [NewsController::class, 'getAllTopics']);
Route::get('/get-all-authors', [NewsController::class, 'getAllAuthors']);
Route::get('/get-all-sources', [NewsController::class, 'getAllSources']);
Route::post('/search', [NewsController::class, 'searchNews']);

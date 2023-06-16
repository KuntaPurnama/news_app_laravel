<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsAPIController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewYorkTimesAPIController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return 'welcome';
});

Route::get('/test', [NewsAPIController::class, 'getTopHeadlinesNewsAPI']);
Route::get('/nycTest', [NewYorkTimesAPIController::class, 'getTopStories']);
Route::get('/testR', [NewYorkTimesAPIController::class, 'test']);
Route::get('/news-summary/{index}/{size}', [NewsController::class, 'getNewsSummary']);

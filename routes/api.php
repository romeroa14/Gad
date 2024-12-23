<?php

use App\Http\Controllers\FacebookAdsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('facebook')->group(function () {
    Route::get('/test', [FacebookAdsController::class, 'testConnection']);
    Route::get('/insights', [FacebookAdsController::class, 'getInsights']);
    Route::get('/friends', [FacebookAdsController::class, 'getFriends']);
    Route::get('/test-token', [FacebookAdsController::class, 'testToken']);
});

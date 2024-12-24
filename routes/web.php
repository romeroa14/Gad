<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookAuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
    Route::get('auth/facebook', [FacebookAuthController::class, 'redirect'])
        ->name('facebook.login')
        ->middleware('guest');
        
    Route::get('auth/facebook/callback', [FacebookAuthController::class, 'callback'])
        ->name('facebook.callback')
        ->middleware('guest');
});

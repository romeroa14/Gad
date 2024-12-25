<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
// use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación básica
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::get('logout', [LoginController::class, 'logout'])->name('logout');

// Rutas de Facebook
Route::middleware(['web'])->group(function () {
    Route::get('auth/facebook', [FacebookAuthController::class, 'redirect'])
        ->name('facebook.login');
        
    Route::get('auth/facebook/callback', [FacebookAuthController::class, 'callback'])
        ->name('facebook.callback');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

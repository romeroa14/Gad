<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
// use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect('/admin');
});

// Rutas de autenticación básica
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de Facebook
Route::middleware(['web'])->group(function () {
    Route::get('/auth/facebook/login', [\App\Http\Controllers\FacebookAuthController::class, 'redirectToFacebook'])
        ->name('facebook.login');
        
    Route::get('/auth/facebook/callback', [\App\Http\Controllers\FacebookAuthController::class, 'handleFacebookCallback']);
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Rutas protegidas del panel de administración
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

Route::get('/auth/facebook/disconnect', [\App\Http\Controllers\FacebookAuthController::class, 'disconnect'])
    ->name('facebook.disconnect')
    ->middleware('auth');

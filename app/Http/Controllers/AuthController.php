<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function logout(Request $request)
    {
        Log::info('Usuario cerró sesión', ['user_id' => Auth::id()]);
        
        // Cerrar la sesión
        Auth::logout();
        
        // Invalidar la sesión y regenerar el token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redireccionar al inicio
        return redirect('/');
    }
} 
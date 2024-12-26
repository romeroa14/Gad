<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    public function redirect()
    {
        Log::info('Iniciando redirección a Facebook');
        return Socialite::with('facebook')
            ->scopes([
                'ads_management',
                'ads_read',
                'business_management',
                'public_profile',
                'email'
            ])
            ->redirect();
    }

    public function callback()
    {
        Log::info('Iniciando callback de Facebook');
        try {
            Log::info('Intentando obtener usuario de Facebook');
            
            $facebookUser = Socialite::driver('facebook')->user();
            
            Log::info('Usuario de Facebook obtenido exitosamente');
            
            // Log para debug
            Log::info('Facebook callback data:', [
                'id' => $facebookUser->getId(),
                'name' => $facebookUser->getName(),
                'email' => $facebookUser->getEmail(),
                'token' => $facebookUser->token,
                'expiresIn' => $facebookUser->expiresIn,
            ]);

            $user = User::updateOrCreate(
                ['facebook_id' => $facebookUser->getId()],
                [
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'facebook_access_token' => $facebookUser->token,
                ]
            );

            Auth::login($user);

            // Redirigir siempre al dashboard
            // return redirect()->route('filament.pages.dashboard');

            return redirect('/admin');


            // } catch (Exception $e) {
            //     return redirect()
            //         ->route('filament.pages.dashboard')
            //         ->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
            // }

        } catch (\Exception $e) {
            Log::error('Facebook callback error detallado:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/admin')->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
        }
    }
}

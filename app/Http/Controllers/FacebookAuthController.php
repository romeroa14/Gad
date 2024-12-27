<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            $facebookUser = Socialite::driver('facebook')->user();
            
            // Agregar más logging para debug
            Log::info('Respuesta completa de Facebook:', [
                'user' => $facebookUser,
                'token_exists' => !empty($facebookUser->token),
            ]);

            // Primero buscamos por email
            $user = User::where('email', $facebookUser->getEmail())->first();
            
            if ($user) {
                Log::info('Actualizando usuario existente:', ['user_id' => $user->id]);
                $user->update([
                    'facebook_id' => $facebookUser->getId(),
                    'facebook_access_token' => $facebookUser->token,
                    'facebook_token_expires_at' => now()->addSeconds($facebookUser->expiresIn)
                ]);
            } else {
                // Si no existe, creamos uno nuevo
                $user = User::create([
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'facebook_id' => $facebookUser->getId(),
                    'facebook_access_token' => $facebookUser->token,
                    'facebook_token_expires_at' => now()->addSeconds($facebookUser->expiresIn)
                ]);
            }

            // Verificar token antes de hacer la petición
            if (empty($facebookUser->token)) {
                throw new Exception('No se recibió token de acceso de Facebook');
            }

            // Mejorar la petición de cuentas publicitarias
            $response = Http::get('https://graph.facebook.com/v19.0/me/adaccounts', [
                'access_token' => $facebookUser->token,
                'fields' => 'name,account_status,currency,timezone_name,id'
            ]);

            Log::info('Respuesta de cuentas publicitarias:', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $adAccounts = $response->json('data', []);
                Log::info('Cuentas publicitarias encontradas:', ['count' => count($adAccounts)]);
                
                foreach ($adAccounts as $account) {
                    $user->advertisingAccounts()->updateOrCreate(
                        ['account_id' => $account['id']],
                        [
                            'name' => $account['name'] ?? 'Sin nombre',
                            'status' => $account['account_status'] ?? 0,
                            'currency' => $account['currency'] ?? 'USD',
                            'timezone' => $account['timezone_name'] ?? 'UTC'
                        ]
                    );
                }
            } else {
                Log::error('Error al obtener cuentas publicitarias:', [
                    'status' => $response->status(),
                    'error' => $response->json()
                ]);
            }

            Auth::login($user); // Importante: asegurarse que el usuario esté autenticado

            return redirect('/admin')->with('success', 'Cuenta de Facebook conectada exitosamente');

        } catch (\Exception $e) {
            Log::error('Facebook callback error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin')->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
        }
    }
}

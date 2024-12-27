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

            Log::info('Usuario de Facebook obtenido exitosamente');

            // Log para debug
            Log::info('Facebook callback data:', [
                'id' => $facebookUser->getId(),
                'name' => $facebookUser->getName(),
                'email' => $facebookUser->getEmail(),
                'token' => $facebookUser->token,
                'expiresIn' => $facebookUser->expiresIn,
            ]);
            
            // Primero buscamos por email
            $user = User::where('email', $facebookUser->getEmail())->first();
            
            if ($user) {
                // Si el usuario existe, solo actualizamos los datos de Facebook
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

            // Obtener cuentas publicitarias
            $response = Http::get('https://graph.facebook.com/v19.0/me/adaccounts', [
                'access_token' => $facebookUser->token,
                'fields' => 'name,account_status,currency,timezone_name'
            ]);

            if ($response->successful()) {
                $adAccounts = $response->json('data', []);
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
            }

            return redirect('/admin')->with('success', 'Cuenta de Facebook conectada exitosamente');

        } catch (\Exception $e) {
            Log::error('Facebook callback error: ' . $e->getMessage());
            return redirect('/admin')->with('error', 'Error al conectar con Facebook');
        }
    }
}

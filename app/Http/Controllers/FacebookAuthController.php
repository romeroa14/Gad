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
        return Socialite::driver('facebook')
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
            
            // Buscar o crear usuario
            $user = User::updateOrCreate(
                ['facebook_id' => $facebookUser->getId()],
                [
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'facebook_access_token' => $facebookUser->token,
                    'facebook_token_expires_at' => now()->addSeconds($facebookUser->expiresIn)
                ]
            );

            // Obtener cuentas publicitarias usando el token del usuario
            $response = Http::get('https://graph.facebook.com/v19.0/me/adaccounts', [
                'access_token' => $facebookUser->token,
                'fields' => 'name,account_status,currency,timezone_name'
            ]);

            if ($response->successful()) {
                $adAccounts = $response->json('data', []);
                
                Log::info('Cuentas publicitarias obtenidas:', ['accounts' => $adAccounts]);

                foreach ($adAccounts as $account) {
                    $accountData = [
                        'account_id' => $account['id'] ?? '',
                        'name' => $account['name'] ?? 'Sin nombre',
                        'status' => $account['account_status'] ?? 0,
                        'currency' => $account['currency'] ?? 'USD',
                        'timezone' => $account['timezone_name'] ?? 'UTC'
                    ];

                    Log::info('Procesando cuenta publicitaria:', $accountData);

                    $user->advertisingAccounts()->updateOrCreate(
                        ['account_id' => $accountData['account_id']],
                        [
                            'name' => $accountData['name'],
                            'status' => $accountData['status'],
                            'currency' => $accountData['currency'],
                            'timezone' => $accountData['timezone']
                        ]
                    );
                }
            } else {
                Log::error('Error al obtener cuentas publicitarias:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            Auth::login($user);

            return redirect('/admin');

        } catch (\Exception $e) {
            Log::error('Facebook callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin')->with('error', 'Error al conectar con Facebook');
        }
    }
}

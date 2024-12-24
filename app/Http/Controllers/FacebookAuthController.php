<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdvertisingAccount;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class FacebookAuthController extends Controller
{
    public function redirect()
    {
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
        try {
            $facebookUser = Socialite::driver('facebook')->user();
            
            $user = User::updateOrCreate(
                [
                    'facebook_id' => $facebookUser->id,
                ],
                [
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'facebook_access_token' => $facebookUser->token,
                ]
            );

            Auth::login($user);

            // Sincronizar cuentas publicitarias
            $this->syncAdvertisingAccounts($user);

            return redirect()->route('filament.pages.dashboard')
                ->with('success', 'Conexión con Facebook exitosa');
                
        } catch (Exception $e) {
            return redirect()->route('filament.pages.dashboard')
                ->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
        }
    }

    protected function syncAdvertisingAccounts($user)
    {
        try {
            $response = Socialite::driver('facebook')
                ->stateless()
                ->userFromToken($user->facebook_access_token);

            $accounts = $response->user['adaccounts']['data'] ?? [];

            foreach ($accounts as $account) {
                AdvertisingAccount::updateOrCreate(
                    [
                        'account_id' => $account['id'],
                        'user_id' => $user->id,
                    ],
                    [
                        'name' => $account['name'],
                        'status' => 'active',
                    ]
                );
            }
        } catch (Exception $e) {
            report($e);
        }
    }
} 
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class FacebookAuthController extends Controller
{
    public function redirect()
    {
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
        try {
            $facebookUser = Socialite::driver('facebook')->user();
            
            $user = User::updateOrCreate(
                ['facebook_id' => $facebookUser->id],
                [
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'facebook_access_token' => $facebookUser->token,
                ]
            );

            Auth::login($user);

            // Redirigir siempre al dashboard
            return redirect()->route('filament.pages.dashboard');

        } catch (Exception $e) {
            return redirect()
                ->route('filament.pages.dashboard')
                ->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
        }
    }
} 
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdvertisingAccount;
use App\Models\FacebookAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthController extends Controller
{
    /**
     * Redirigir al usuario a la página de autenticación de Facebook.
     */
    public function redirectToFacebook()
    {
        try {
            Log::info('Iniciando redirección a Facebook');
            return Socialite::driver('facebook')
                ->scopes(['email', 'ads_management', 'ads_read', 'public_profile'])
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Error en redirección a Facebook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin')->with('error', 'Error al conectar con Facebook');
        }
    }

    /**
     * Obtener la información del usuario de Facebook después de la autenticación.
     */
    public function handleFacebookCallback(Request $request)
    {
        try {
            Log::info('Iniciando callback de Facebook');

            // Verificar si hay errores en la respuesta de Facebook
            if ($request->has('error') || $request->has('error_reason')) {
                Log::error('Error en callback de Facebook', [
                    'error' => $request->error,
                    'error_reason' => $request->error_reason,
                    'error_description' => $request->error_description
                ]);
                return redirect('/admin')->with('error', 'Error durante la conexión con Facebook: ' . ($request->error_description ?? 'Acceso denegado'));
            }

            // Obtener datos del usuario desde Facebook
            $fbUser = Socialite::driver('facebook')->stateless()->user();
            
            Log::info('Datos de usuario obtenidos de Facebook', [
                'id' => $fbUser->getId(),
                'name' => $fbUser->getName(),
                'email' => $fbUser->getEmail(),
                'token_length' => strlen($fbUser->token),
                'expires_in' => $fbUser->expiresIn ?? 0,
                'refresh_token' => isset($fbUser->refreshToken) ? 'presente' : 'ausente'
            ]);

            // Calcular fecha de expiración correctamente
            // Por defecto, Facebook generalmente da tokens válidos por 60 días (5184000 segundos)
            $expiresIn = !empty($fbUser->expiresIn) && $fbUser->expiresIn > 3600 
                ? $fbUser->expiresIn 
                : 5184000; // 60 días en segundos como fallback
            
            $expirationDate = now()->addSeconds($expiresIn);
            
            Log::info('Calculando fecha de expiración del token', [
                'expires_in_seconds' => $expiresIn,
                'expiration_date' => $expirationDate,
                'is_future' => $expirationDate->isFuture()
            ]);

            // Crear o actualizar la cuenta de Facebook independientemente del usuario
            $facebookAccount = FacebookAccount::updateOrCreate(
                ['facebook_id' => $fbUser->getId()],
                [
                    'facebook_user_name' => $fbUser->getName(),
                    'facebook_email' => $fbUser->getEmail(),
                    'facebook_access_token' => $fbUser->token,
                    'facebook_token_expires_at' => $expirationDate,
                    'user_id' => null, // No asociamos a ningún usuario del sistema
                ]
            );

            // Guardar la cuenta en la sesión para uso futuro
            session(['facebook_account_id' => $facebookAccount->id]);
            Log::info('ID de cuenta de Facebook guardada en sesión', [
                'facebook_account_id' => $facebookAccount->id,
                'user_name' => $facebookAccount->facebook_user_name,
                'token_expires_at' => $facebookAccount->facebook_token_expires_at
            ]);

            // Obtener las cuentas publicitarias
            $this->fetchAndProcessAdAccounts($facebookAccount);

            return redirect('/admin')->with('success', 'Cuenta de Facebook conectada exitosamente. Ahora puedes acceder a tus cuentas publicitarias.');

        } catch (\Exception $e) {
            Log::error('Error durante conexión con Facebook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin')->with('error', 'Error durante la conexión con Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta la cuenta de Facebook del usuario y elimina el usuario de la base de datos
     */
    public function disconnect()
    {
        try {
            $facebookAccountId = session('facebook_account_id');
            
            if ($facebookAccountId) {
                $facebookAccount = FacebookAccount::find($facebookAccountId);
                
                if ($facebookAccount) {
                    // Registrar la acción
                    Log::info('Desconectando cuenta de Facebook', [
                        'facebook_id' => $facebookAccount->facebook_id,
                        'facebook_email' => $facebookAccount->facebook_email
                    ]);
                    
                    // Eliminar cuentas publicitarias y cuenta de Facebook
                    $facebookAccount->advertisingAccounts()->delete();
                    $facebookAccount->delete();
                    
                    // Limpiar la sesión
                    session()->forget('facebook_account_id');
                    
                    return redirect('/admin')->with('success', 'Cuenta de Facebook desconectada correctamente');
                }
            }
            
            return redirect('/admin')->with('error', 'No se encontró una cuenta de Facebook para desconectar');
        } catch (\Exception $e) {
            Log::error('Error al desconectar Facebook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/admin')->with('error', 'Error al desconectar la cuenta de Facebook: ' . $e->getMessage());
        }
    }

    private function fetchAndProcessAdAccounts(FacebookAccount $facebookAccount)
    {
        try {
            $response = Http::get('https://graph.facebook.com/v17.0/me/adaccounts', [
                'access_token' => $facebookAccount->facebook_access_token,
                'fields' => 'id,name,account_status,currency,timezone_name,balance,amount_spent',
            ]);

            if (!$response->successful()) {
                Log::error('Error al obtener cuentas publicitarias', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $adAccounts = $response->json('data', []);
            
            foreach ($adAccounts as $account) {
                AdvertisingAccount::updateOrCreate(
                    [
                        'facebook_account_id' => $facebookAccount->id,
                        'account_id' => $account['id'],
                    ],
                    [
                        'name' => $account['name'],
                        'status' => $account['account_status'] ?? 0,
                        'currency' => $account['currency'] ?? 'USD',
                        'timezone' => $account['timezone_name'] ?? 'America/Caracas',
                    ]
                );
            }
            
            Log::info('Cuentas publicitarias actualizadas', [
                'count' => count($adAccounts),
                'facebook_account_id' => $facebookAccount->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error al procesar cuentas publicitarias', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Método de depuración para verificar el estado de la conexión
     */
    public function checkConnection()
    {
        $facebookAccountId = session('facebook_account_id');
        $account = $facebookAccountId ? FacebookAccount::find($facebookAccountId) : null;
        
        if (!$account) {
            $account = FacebookAccount::latest()->first();
            if ($account) {
                session(['facebook_account_id' => $account->id]);
            }
        }
        
        $data = [
            'session_has_id' => session()->has('facebook_account_id'),
            'session_id' => session('facebook_account_id'),
            'account_found' => $account ? true : false,
            'account_details' => $account ? [
                'id' => $account->id,
                'facebook_id' => $account->facebook_id,
                'facebook_user_name' => $account->facebook_user_name,
                'token_exists' => !empty($account->facebook_access_token),
                'token_expires' => $account->facebook_token_expires_at,
                'token_is_valid' => $account->hasValidToken(),
            ] : null
        ];
        
        Log::info('Estado de conexión Facebook', $data);
        
        return response()->json($data);
    }
}

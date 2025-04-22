<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdvertisingAccount;
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
            Log::info('Iniciando callback de Facebook', [
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error')
            ]);

            // Verificar si hay errores en la respuesta de Facebook
            if ($request->has('error') || $request->has('error_reason')) {
                Log::error('Error en callback de Facebook (desde parámetros)', [
                    'error' => $request->error,
                    'error_reason' => $request->error_reason,
                    'error_description' => $request->error_description
                ]);
                return redirect('/admin')->with('error', 'Error durante la autenticación con Facebook: ' . ($request->error_description ?? 'Acceso denegado'));
            }

            // Obtener datos del usuario desde Facebook
            $fbUser = Socialite::driver('facebook')->stateless()->user();
            
            Log::info('Datos de usuario obtenidos correctamente', [
                'id' => $fbUser->getId(),
                'name' => $fbUser->getName(),
                'email' => $fbUser->getEmail()
            ]);

            // Verifica que tenemos un email
            if (empty($fbUser->getEmail())) {
                Log::error('Facebook no proporcionó email');
                return redirect('/admin')->with('error', 'Facebook no proporcionó un email válido');
            }

            // Buscar o crear el usuario
            $user = User::updateOrCreate(
                ['email' => $fbUser->getEmail()],
                [
                    'name' => $fbUser->getName(),
                    'facebook_id' => $fbUser->getId(),
                    'facebook_access_token' => $fbUser->token,
                    'facebook_token_expires_at' => now()->addSeconds($fbUser->expiresIn),
                    'email_verified_at' => now(),
                ]
            );

            // Actualizar la sesión con el usuario autenticado
            Auth::login($user);

            // Intentar obtener las cuentas publicitarias si hay token
            if ($user->facebook_access_token) {
                $this->fetchAndProcessAdAccounts($user->facebook_access_token, $user);
            }

            return redirect('/admin')->with('success', 'Conectado con Facebook exitosamente');

        } catch (\Exception $e) {
            Log::error('Facebook callback error: ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin')->with('error', 'Error durante la autenticación con Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta la cuenta de Facebook del usuario y elimina el usuario de la base de datos
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                // Registrar la acción
                Log::info('Eliminando usuario y desconectando cuenta de Facebook', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                // Guardar información para el log antes de eliminar
                $userId = $user->id;
                $userEmail = $user->email;
                
                // Eliminar las cuentas publicitarias asociadas primero (relaciones)
                $user->advertisingAccounts()->delete();
                
                // Cerrar la sesión antes de eliminar el usuario
                Auth::logout();
                
                // Eliminar el usuario completamente
                $user->delete();
                
                Log::info('Usuario eliminado correctamente', [
                    'user_id' => $userId,
                    'email' => $userEmail
                ]);
                
                // Invalidar la sesión y regenerar el token CSRF
                request()->session()->invalidate();
                request()->session()->regenerateToken();
                
                return redirect('/admin')->with('success', 'Tu cuenta ha sido eliminada correctamente');
            }
            
            return redirect('/admin')->with('error', 'No se pudo desconectar la cuenta');
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/admin')->with('error', 'Error al eliminar la cuenta: ' . $e->getMessage());
        }
    }

    private function fetchAndProcessAdAccounts($accessToken, $user)
    {
        try {
            $response = Http::get('https://graph.facebook.com/v19.0/me/adaccounts', [
                'access_token' => $accessToken,
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
        } catch (\Exception $e) {
            Log::error('Error al obtener cuentas publicitarias: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

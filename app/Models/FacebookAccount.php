<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FacebookAccount extends Model
{
    protected $fillable = [
        'facebook_id',
        'facebook_user_name',
        'facebook_email',
        'facebook_access_token',
        'facebook_token_expires_at'
    ];

    protected $casts = [
        'facebook_token_expires_at' => 'datetime',
    ];

    

    /**
     * Obtener las cuentas publicitarias asociadas a esta cuenta de Facebook
     */
    public function advertisingAccounts(): HasMany
    {
        return $this->hasMany(AdvertisingAccount::class);
    }

    /**
     * Verificar si el token de acceso sigue siendo válido
     */
    public function hasValidToken(): bool
    {
        // Si no hay token o no hay fecha de expiración, no es válido
        if (empty($this->facebook_access_token) || empty($this->facebook_token_expires_at)) {
            Log::info('Token de Facebook inválido - Token vacío o sin fecha de expiración', [
                'account_id' => $this->id,
                'has_token' => !empty($this->facebook_access_token) ? 'true' : 'false',
                'has_expiry_date' => !empty($this->facebook_token_expires_at) ? 'true' : 'false'
            ]);
            return false;
        }
        
        // Verificar si el token expira en el futuro
        $isValid = $this->facebook_token_expires_at->isFuture();
        
        // Si el token está por expirar (menos de 1 día), loguear un aviso
        $expiresInDays = $isValid ? now()->diffInDays($this->facebook_token_expires_at) : 0;
        if ($isValid && $expiresInDays < 1) {
            Log::warning('Token de Facebook válido pero próximo a expirar', [
                'account_id' => $this->id,
                'expires_at' => $this->facebook_token_expires_at->format('Y-m-d H:i:s'),
                'expires_in_hours' => now()->diffInHours($this->facebook_token_expires_at)
            ]);
        }
        
        // Registrar el resultado
        Log::info('Validando token Facebook', [
            'account_id' => $this->id,
            'has_token' => !empty($this->facebook_access_token) ? 'true' : 'false',
            'expires_at' => $this->facebook_token_expires_at->format('Y-m-d H:i:s'),
            'expires_in_days' => $expiresInDays,
            'is_future' => $isValid ? 'true' : 'false',
            'is_valid' => $isValid ? 'true' : 'false'
        ]);
        
        return $isValid;
    }

    /**
     * Verificar el token directamente con la API de Facebook
     */
    public function verifyTokenWithFacebook(): bool
    {
        if (empty($this->facebook_access_token)) {
            return false;
        }

        try {
            $response = Http::get('https://graph.facebook.com/debug_token', [
                'input_token' => $this->facebook_access_token,
                'access_token' => config('services.facebook.client_id') . '|' . config('services.facebook.client_secret')
            ]);

            if ($response->successful()) {
                $tokenData = $response->json('data');
                $isValid = $tokenData['is_valid'] ?? false;
                $expiresAt = isset($tokenData['expires_at']) ? Carbon::createFromTimestamp($tokenData['expires_at']) : null;

                Log::info('Verificación de token con Facebook', [
                    'account_id' => $this->id,
                    'is_valid' => $isValid ? 'true' : 'false',
                    'expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : 'null',
                    'app_id' => $tokenData['app_id'] ?? 'null',
                    'scopes' => $tokenData['scopes'] ?? []
                ]);

                // Actualizar la fecha de expiración si es diferente
                if ($isValid && $expiresAt && (!$this->facebook_token_expires_at || $this->facebook_token_expires_at->ne($expiresAt))) {
                    $this->facebook_token_expires_at = $expiresAt;
                    $this->save();
                    Log::info('Fecha de expiración del token actualizada', [
                        'account_id' => $this->id,
                        'old_date' => $this->getOriginal('facebook_token_expires_at'),
                        'new_date' => $expiresAt->format('Y-m-d H:i:s')
                    ]);
                }

                return $isValid;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error al verificar token con Facebook', [
                'account_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 
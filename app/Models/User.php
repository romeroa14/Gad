<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // O tu lógica de autorización
    }

   

    /**
     * Obtener las cuentas publicitarias
     * Asegúrate de que siempre devuelva un Builder para mantener consistencia
     */
    public function advertisingAccounts()
    {
        // Si existe una relación con una cuenta de Facebook
        if ($this->facebookAccount) {
            // Retorna un builder, no una colección
            return $this->facebookAccount->advertisingAccounts();
        }
        
        // Retorna un builder vacío, no una colección
        return AdvertisingAccount::query()->whereRaw('1 = 0');
    }

    /**
     * Verificar si el usuario tiene una cuenta de Facebook conectada
     */
    public function hasConnectedFacebookAccount(): bool
    {
        return $this->facebookAccount && $this->facebookAccount->hasValidToken();
    }
}


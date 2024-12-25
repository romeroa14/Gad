<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'facebook_id',
        'facebook_access_token',
        'facebook_token_expires_at',
    ];

    protected $casts = [
        'facebook_token_expires_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // O tu lógica de autorización
    }

    public function hasConnectedFacebookAccount(): bool
    {
        return !empty($this->facebook_access_token);
    }

    public function advertisingAccounts()
    {
        return $this->hasMany(AdvertisingAccount::class);
    }
}


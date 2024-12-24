<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'facebook_id',
        'facebook_access_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'facebook_access_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Verifica si el usuario tiene una cuenta de Facebook conectada
     */
    public function hasConnectedFacebookAccount(): bool
    {
        return !empty($this->facebook_access_token) && !empty($this->facebook_id);
    }

    /**
     * Relación con las cuentas publicitarias
     */
    public function advertisingAccounts()
    {
        return $this->hasMany(AdvertisingAccount::class);
    }
}


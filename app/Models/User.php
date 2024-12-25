<?php

namespace App\Models;

use App\Models\Traits\HasFacebookAccount;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFacebookAccount, Notifiable;

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
}


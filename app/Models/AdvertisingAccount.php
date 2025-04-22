<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvertisingAccount extends Model
{
    protected $fillable = [
        'facebook_account_id',
        'account_id',
        'name',
        'status',
        'currency',
        'timezone'
    ];

    /**
     * Obtener la cuenta de Facebook a la que pertenece esta cuenta publicitaria
     */
    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    /**
     * Obtener el usuario a través de la cuenta de Facebook
     */
    public function user(): BelongsTo
    {
        return $this->facebookAccount->user();
    }
} 
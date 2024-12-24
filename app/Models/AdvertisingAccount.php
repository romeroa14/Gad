<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvertisingAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'account_id',
        'access_token'
    ];

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }
} 
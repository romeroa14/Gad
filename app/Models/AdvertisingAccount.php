<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisingAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'status',
        'currency',
        'timezone'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 
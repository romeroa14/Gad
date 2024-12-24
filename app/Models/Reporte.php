<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    protected $fillable = [
        'advertising_account_id',
        'date_range',
        'metrics'
    ];

    protected $casts = [
        'metrics' => 'array'
    ];

    public function advertisingAccount(): BelongsTo
    {
        return $this->belongsTo(AdvertisingAccount::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsCampaign extends Model
{
    // Constantes para estados
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    // Constantes para planes
    const PLAN_BASIC = 'basic';
    const PLAN_PREMIUM = 'premium';
    const PLAN_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'name',
        'client_id',
        'plan',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'status',
        'meta_campaign_id',
        'ad_account_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2'
    ];

    // Relaciones
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class, 'ad_account_id', 'account_id');
    }
}
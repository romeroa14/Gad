<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\FacebookAds\FacebookAdsService;
use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AdsCampaign extends Model
{
    protected $fillable = [
        'name',
        'client_id',
        'plan_id',
        'advertising_account_id',
        'meta_campaign_id',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'cost_per_conversion',
        'status',
        'last_synced_at',
        'meta_insights'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
        'meta_insights' => 'array',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class);
    }

    public function adsSets(): HasMany
    {
        return $this->hasMany(AdsSet::class, 'ads_campaign_id');
    }

    public function ads()
    {
        return $this->hasManyThrough(Ad::class, AdsSet::class, 'ads_campaign_id', 'ads_set_id');
    }

    
}
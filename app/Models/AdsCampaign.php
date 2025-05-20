<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\FacebookAds\FacebookAdsService;
use App\Models\Client;

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

    public function syncWithFacebook()
    {
        if (!$this->meta_campaign_id || !$this->advertising_account_id) {
            return false;
        }
        
        try {
            $service = new FacebookAdsService();
            $insights = $service->getCampaignInsights($this->advertising_account_id, $this->meta_campaign_id);
            
            if (empty($insights)) {
                return false;
            }
            
            $this->update([
                'actual_cost' => $insights['spend'] ?? $this->actual_cost,
                'cost_per_conversion' => $insights['cost_per_conversion'] ?? $this->cost_per_conversion,
                'last_synced_at' => now(),
                'meta_insights' => $insights,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error syncing campaign {$this->id}: " . $e->getMessage());
            return false;
        }
    }
}
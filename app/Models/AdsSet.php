<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdsSet extends Model
{
    protected $fillable = [
        'meta_adset_id',
        'ads_campaign_id',
        'name',
        'status',
        'daily_budget',
        'lifetime_budget',
        'target_spec',
        'optimization_goal',
        'billing_event',
        'meta_insights',
    ];

    protected $casts = [
        'daily_budget' => 'float',
        'lifetime_budget' => 'float',
        'target_spec' => 'array',
        'meta_insights' => 'array',
    ];

    public function adsCampaign(): BelongsTo
    {
        return $this->belongsTo(AdsCampaign::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
} 
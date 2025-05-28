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
        'page_id',
        'page_name',
        'instagram_account_id',
        'instagram_username',
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

    /**
     * Obtener información de la página de Facebook
     */
    public function getPageInfoAttribute()
    {
        return [
            'id' => $this->page_id,
            'name' => $this->page_name,
            'url' => $this->page_id ? "https://facebook.com/{$this->page_id}" : null
        ];
    }

    /**
     * Obtener información de Instagram
     */
    public function getInstagramInfoAttribute()
    {
        return [
            'id' => $this->instagram_account_id,
            'username' => $this->instagram_username,
            'url' => $this->instagram_username ? "https://instagram.com/{$this->instagram_username}" : null
        ];
    }

    /**
     * Verificar si tiene página de Facebook
     */
    public function hasPage()
    {
        return !empty($this->page_id);
    }

    /**
     * Verificar si tiene cuenta de Instagram
     */
    public function hasInstagram()
    {
        return !empty($this->instagram_account_id);
    }
} 
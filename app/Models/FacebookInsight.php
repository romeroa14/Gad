<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookInsight extends Model
{
    protected $fillable = [
        'facebook_campaign_id',
        'date',
        'impressions',
        'clicks',
        'reach',
        'spend',
        'cpc',
        'cpm',
        'ctr',
        'frequency',
        'unique_clicks',
        'unique_ctr',
        'cost_per_unique_click',
        'conversions',
        'cost_per_conversion',
        'conversion_rate',
        'social_reach',
        'social_impressions',
        'actions',  // Puede almacenarse como JSON
        'website_purchases',
        'return_on_ad_spend',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'reach' => 'integer',
        'spend' => 'decimal:2',
        'cpc' => 'decimal:2',
        'cpm' => 'decimal:2',
        'ctr' => 'decimal:4',
        'frequency' => 'decimal:2',
        'unique_clicks' => 'integer',
        'unique_ctr' => 'decimal:4',
        'cost_per_unique_click' => 'decimal:2',
        'conversions' => 'integer',
        'cost_per_conversion' => 'decimal:2',
        'conversion_rate' => 'decimal:4',
        'social_reach' => 'integer',
        'social_impressions' => 'integer',
        'actions' => 'json',
        'website_purchases' => 'integer',
        'return_on_ad_spend' => 'decimal:2',
    ];

    /**
     * Obtiene la campaña asociada a este insight
     */
    public function campaign()
    {
        return $this->belongsTo(FacebookCampaign::class, 'facebook_campaign_id');
    }

    /**
     * Scope para filtrar insights por rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope para obtener insights de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope para obtener insights de los últimos X días
     */
    public function scopeLastDays($query, $days)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }
} 
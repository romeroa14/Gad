<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $fillable = [
        'meta_ad_id',
        'ads_set_id',
        'name',
        'status',
        'creative_id',
        'creative_url',
        'thumbnail_url',
        'preview_url',
        'meta_insights',
    ];

    protected $casts = [
        'meta_insights' => 'array',
    ];

    public function adsSet(): BelongsTo
    {
        return $this->belongsTo(AdsSet::class);
    }
} 
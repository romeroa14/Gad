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
        'page_id',
        'page_name',
        'instagram_account_id',
        'instagram_username',
    ];

    protected $casts = [
        'meta_insights' => 'array',
    ];

    public function adsSet(): BelongsTo
    {
        return $this->belongsTo(AdsSet::class);
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'status',
        'objective',
        'spend',
        'start_time',
        'stop_time',
        'facebook_ad_account_id'
    ];

    public function adAccount()
    {
        return $this->belongsTo(FacebookAdAccount::class);
    }

    public function insights()
    {
        return $this->hasMany(FacebookInsight::class);
    }
}

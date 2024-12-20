<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookAdAccount extends Model
{
    protected $fillable = [
        'account_id',
        'name',
        'currency',
        'timezone',
        'status',
        'last_sync_at'
    ];

    public function campaigns()
    {
        return $this->hasMany(FacebookCampaign::class);
    }
}

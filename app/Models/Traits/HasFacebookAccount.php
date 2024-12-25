<?php

namespace App\Models\Traits;

trait HasFacebookAccount
{
    public function hasConnectedFacebookAccount(): bool
    {
        return !empty($this->facebook_access_token) && !empty($this->facebook_id);
    }

    public function advertisingAccounts()
    {
        return $this->hasMany(\App\Models\AdvertisingAccount::class);
    }
} 
<?php

namespace App\Services\FacebookAds;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        Api::init(
            config('facebook-ads.app_id'),
            config('facebook-ads.app_secret'),
            config('facebook-ads.access_token')
        );

        if (config('app.debug')) {
            Api::instance()->setLogger(new CurlLogger());
        }

        $this->adAccount = new AdAccount('act_' . config('facebook-ads.account_id'));
    }

    public function getAdAccount()
    {
        return $this->adAccount;
    }
}
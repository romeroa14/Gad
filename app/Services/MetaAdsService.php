<?php

namespace App\Services;

use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use App\Models\ServiceIntegration;

class MetaAdsService
{
    protected $api;
    protected $adAccount;
    protected $credentials;

    public function __construct()
    {
        $this->loadCredentials();
        $this->initializeApi();
    }

    protected function loadCredentials()
    {
        $service = ServiceIntegration::where('name', 'meta')->first();
        if (!$service) {
            throw new \Exception('Servicio de Meta no encontrado');
        }
        $this->credentials = $service->getMetaCredentials();
        if (!$this->credentials) {
            throw new \Exception('Credenciales de Meta no encontradas');
        }
    }

    protected function initializeApi()
    {
        Api::init(
            $this->credentials['app_id'],
            $this->credentials['app_secret'],
            $this->credentials['access_token']
        );
    }

    public function getAdAccount()
    {
        if (!$this->adAccount) {
            $this->adAccount = new AdAccount($this->credentials['ad_account_id']);
        }
        return $this->adAccount;
    }
} 
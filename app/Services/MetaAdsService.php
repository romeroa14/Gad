<?php

namespace App\Services;

use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Logger\CurlLogger;

class MetaAdsService
{
    protected $api;
    protected $adAccount;

    public function __construct()
    {
        // Inicializar la API
        Api::init(
            config('meta-ads.app_id'),
            config('meta-ads.app_secret'),
            config('meta-ads.access_token')
        );

        // Habilitar logging en desarrollo
        if (config('app.debug')) {
            Api::instance()->setLogger(new CurlLogger());
        }

        $this->adAccount = new AdAccount('act_' . config('meta-ads.account_id'));
    }

    public function validateCampaignExists($campaignId)
    {
        try {
            $campaign = new Campaign($campaignId);
            $campaign->read(['name']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCampaignData($campaignId)
    {
        try {
            $campaign = new Campaign($campaignId);
            $campaign->read([
                'name',
                'objective',
                'status',
                'spend',
                'start_time',
                'stop_time'
            ]);

            $insights = $campaign->getInsights([
                'fields' => [
                    'impressions',
                    'clicks',
                    'ctr',
                    'spend',
                    'reach'
                ],
                'time_range' => [
                    'since' => date('Y-m-d', strtotime('-30 days')),
                    'until' => date('Y-m-d'),
                ],
            ]);

            return [
                'campaign' => $campaign->getData(),
                'insights' => $insights->getResponse()->getContent(),
            ];
        } catch (\Exception $e) {
            throw new \Exception("Error obteniendo datos de la campaña: " . $e->getMessage());
        }
    }
} 
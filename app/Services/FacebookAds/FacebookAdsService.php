<?php

namespace App\Services;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use Illuminate\Support\Facades\Log;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;

    public function __construct()
    {
        $this->api = app('facebook-ads-api');
        $this->adAccount = new AdAccount('act_' . config('263840399179369'));
    }

    // Método de prueba para obtener campañas
    public function getCampaigns()
    {
        try {
            $campaigns = $this->adAccount->getCampaigns();
            return $campaigns->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'objective' => $campaign->objective,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error al obtener campañas: ' . $e->getMessage());
            throw $e;
        }
    }

    // Método para obtener insights básicos
    public function getAccountInsights()
    {
        try {
            $insights = $this->adAccount->getInsights([
                'fields' => [
                    'spend',
                    'impressions',
                    'clicks',
                    'reach'
                ],
                'time_range' => [
                    'since' => date('Y-m-d', strtotime('-30 days')),
                    'until' => date('Y-m-d'),
                ],
            ]);
            return $insights->getResponse()->getContent();
        } catch (\Exception $e) {
            Log::error('Error al obtener insights: ' . $e->getMessage());
            throw $e;
        }
    }
}
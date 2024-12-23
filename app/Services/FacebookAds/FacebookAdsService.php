<?php

namespace App\Services\FacebookAds;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;

    public function __construct()
    {
        try {
            // Inicializar la API con los valores correctos
            $this->api = Api::init(
                '603275022244128',                // Usar el valor directamente si no quieres usar env()
                'f54aa934d5b30c295299bb76390e3806',
                'EAAIkrOlnGSABOZCzwgdzOdTXUkOYcm1snfCJ2nzIrnZC8vaCLe2PmdnCfl1GYfN30QgZA6aImNKrhbJqTZBAbtnLqbneaE4y8caKhfn887yylfAzEVv1VJB1fhNrDOjnNoZA2uNR6JYo0mAVZA7kfcl4VRDZAQhXJRmI6VgFv5OZB8CjjLBpztzDR3tJ6WtXrhz6lfG6gSFAqTWlSVwLqHH5JVQC2ZCiTwvNzx3wEhdVT'
            );
            
            // Obtener el ID de la cuenta de anuncios
            $adAccountId = '933248667753162';     // Usar el valor directamente
            
            if (empty($adAccountId)) {
                throw new \Exception('Facebook Ad Account ID no está configurado en el archivo .env');
            }
            
            // Asegurarse de que el ID tenga el formato correcto
            $accountId = strpos($adAccountId, 'act_') === 0 ? $adAccountId : 'act_' . $adAccountId;
            $this->adAccount = new AdAccount($accountId);
            
        } catch (\Exception $e) {
            Log::error('Error al inicializar FacebookAdsService: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCampaigns()
    {
        Log::info('adAccount: ' . $this->adAccount->getCampaigns());
        try {
            $fields = [
                'id',
                'name',
                'status',
                'objective',
                'created_time',
                'start_time',
                'stop_time'
            ];
            
            $params = [
                'limit' => 1000,
                'status' => ['ACTIVE', 'PAUSED', 'ARCHIVED']
            ];

            $campaigns = $this->adAccount->getCampaigns($fields, $params);
            Log::info('campaigns: ' . $campaigns);
            return $campaigns->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'objective' => $campaign->objective,
                    'created_time' => $campaign->created_time,
                    'start_time' => $campaign->start_time,
                    'stop_time' => $campaign->stop_time,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error al obtener campañas: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountInsights()
    {
        try {
            $fields = 'impressions,clicks,spend,reach,frequency,cost_per_action_type,actions,ctr';
            $timeRange = [
                'since' => '2024-01-01',
                'until' => '2024-12-20'
            ];

            $adAccountId = str_replace('act_', '', config('services.facebook.ad_account_id'));
            
            $response = Http::withToken(config('services.facebook.access_token'))
                ->get("https://graph.facebook.com/v21.0/act_{$adAccountId}/insights", [
                    'fields' => $fields,
                    'time_range' => json_encode($timeRange)
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error al obtener insights: ' . $e->getMessage());
            throw $e;
        }
    }

    public function makeRequest($endpoint, $id)
    {
        $response = Http::withToken(config('services.facebook.EAAIkrOlnGSABOZCzwgdzOdTXUkOYcm1snfCJ2nzIrnZC8vaCLe2PmdnCfl1GYfN30QgZA6aImNKrhbJqTZBAbtnLqbneaE4y8caKhfn887yylfAzEVv1VJB1fhNrDOjnNoZA2uNR6JYo0mAVZA7kfcl4VRDZAQhXJRmI6VgFv5OZB8CjjLBpztzDR3tJ6WtXrhz6lfG6gSFAqTWlSVwLqHH5JVQC2ZCiTwvNzx3wEhdVT'))
            ->get("https://graph.facebook.com/v18.0/{$id}/{$endpoint}");

        return $response->json();
    }

    public function getFriends()
    {
        $response = $this->makeRequest('friends', '10232575857351584');
        return response()->json($response);
    }

    public function testToken()
    {
        try {
            $response = Http::withToken(config('services.facebook.EAAIkrOlnGSABOZCzwgdzOdTXUkOYcm1snfCJ2nzIrnZC8vaCLe2PmdnCfl1GYfN30QgZA6aImNKrhbJqTZBAbtnLqbneaE4y8caKhfn887yylfAzEVv1VJB1fhNrDOjnNoZA2uNR6JYo0mAVZA7kfcl4VRDZAQhXJRmI6VgFv5OZB8CjjLBpztzDR3tJ6WtXrhz6lfG6gSFAqTWlSVwLqHH5JVQC2ZCiTwvNzx3wEhdVT'))
                ->get('https://graph.facebook.com/v21.0/me');
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error validando token: ' . $e->getMessage());
            throw $e;
        }
    }
}


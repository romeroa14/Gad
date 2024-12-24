<?php

namespace App\Services\FacebookAds;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;
    protected $accessToken;
    protected $adAccountId;

    public function __construct()
    {
        try {
            $this->accessToken = 'EAAIkrOlnGSABO4jrpCxyeH8WtHZAwKfCxheh6BSbOOxXWZAB2l3eUY2Tye0gpJfjCsuxiEr8MkWXenwLVSKixHZC8HraT46kl3QGKr2pBLwscZAc8qbdZCiuoi6APZBNCvGmDTxpgBNFuPuuccmqO7oTGBfg9HaZBkOsaIZCTL6GPZCd9jxEHAxo87FtuDZCgVj1NUGlq7UT03Mvk4D7U0JJab32Pne5HiWjoZD';
            
            // Añadir logs para debug
            $rawAccountId = '933248667753162';
            Log::info('Raw Account ID:', ['id' => $rawAccountId]);
            
            // Validación más estricta
            if (empty($rawAccountId)) {
                throw new \Exception('Facebook Ad Account ID está vacío');
            }
            
            // Asegurarse de que el ID tenga el prefijo 'act_'
            $this->adAccountId = strpos($rawAccountId, 'act_') === 0 
                ? $rawAccountId 
                : 'act_' . $rawAccountId;
                
            
            Log::info('Processed Account ID:', ['id' => $this->adAccountId]);
            
            $this->api = Api::init(
                '603275022244128',
                'f54aa934d5b30c295299bb76390e3806',
                $this->accessToken
            );
            
            // Validación final
            if ($this->adAccountId === 'act_') {
                throw new \Exception('Facebook Ad Account ID no está configurado correctamente');
            }
            
            $this->adAccount = new AdAccount($this->adAccountId);
            
        } catch (\Exception $e) {
            Log::error('Error al inicializar FacebookAdsService: ' . $e->getMessage(), [
                'raw_id' => $rawAccountId ?? null,
                'processed_id' => $this->adAccountId ?? null
            ]);
            throw $e;
        }
    }

    public function getCampaigns()
    {
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
            
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/" . 'v21.0' . "/{$this->adAccountId}/insights", [
                    'fields' => $fields,
                    'time_range' => json_encode($timeRange)
                ]);

            if (!$response->successful()) {
                throw new \Exception('Error en la respuesta de la API: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error al obtener insights: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function validateToken()
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get('https://graph.facebook.com/' . config('services.facebook.v21.0') . '/me');
            
            if (!$response->successful()) {
                throw new \Exception('Token inválido o expirado');
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error validando token: ' . $e->getMessage());
            throw $e;
        }
    }
}


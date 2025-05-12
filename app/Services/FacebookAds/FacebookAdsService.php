<?php

namespace App\Services\FacebookAds;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Models\AdvertisingAccount;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;
    protected $accessToken;
    protected $adAccountId;

    public function __construct($accountId = null)
    {
        try {
            // Obtener token de acceso desde configuración
            $this->accessToken = config('services.facebook.access_token');
            
            // Si no se proporciona un account_id, intentar obtenerlo de la sesión
            if (empty($accountId)) {
                $accountId = session('selected_advertising_account_id');
                
                // Si hay un ID en sesión, obtener el account_id del modelo
                if ($accountId) {
                    $account = AdvertisingAccount::find($accountId);
                    if ($account) {
                        $rawAccountId = $account->account_id;
                    }
                }
            } else {
                // Si se proporcionó directamente un ID, usarlo
                $rawAccountId = $accountId;
            }
            
            // Si aún no tenemos ID, usar valor por defecto o lanzar error
            if (empty($rawAccountId)) {
                Log::warning('No se proporcionó Account ID, usando cuenta por defecto');
                $rawAccountId = env('FACEBOOK_AD_ACCOUNT_ID', '933248667753162');
            }
            
            Log::info('Raw Account ID:', ['id' => $rawAccountId]);
            
            // Asegurarse de que el ID tenga el prefijo 'act_'
            $this->adAccountId = strpos($rawAccountId, 'act_') === 0 
                ? $rawAccountId 
                : 'act_' . $rawAccountId;
                
            Log::info('Processed Account ID:', ['id' => $this->adAccountId]);
            
            // Inicializar la API de Facebook
            $this->api = Api::init(
                config('services.facebook.app_id', '603275022244128'),
                config('services.facebook.app_secret', 'f54aa934d5b30c295299bb76390e3806'),
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

    /**
     * Obtiene las cuentas publicitarias disponibles
     */
    public function getAdvertisingAccounts()
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get('https://graph.facebook.com/v18.0/me/adaccounts', [
                    'fields' => 'id,name,account_status,currency,timezone_name,balance,amount_spent'
                ]);
                
            if (!$response->successful()) {
                Log::error('Error al obtener cuentas publicitarias', [
                    'response' => $response->body()
                ]);
                return [];
            }
            
            $accounts = $response->json('data', []);
            $formattedAccounts = [];
            
            foreach ($accounts as $account) {
                // Buscar si esta cuenta ya existe en la base de datos
                $dbAccount = AdvertisingAccount::where('account_id', $account['id'])->first();
                
                $formattedAccounts[] = [
                    // Si la cuenta existe en DB, usar su ID de la base de datos
                    'id' => $dbAccount ? $dbAccount->id : null,
                    'name' => $account['name'],
                    'account_id' => $account['id'],
                    'status' => $account['account_status'] ?? 0,
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone_name'] ?? 'America/Los_Angeles',
                    'balance' => $account['balance'] ?? 0,
                    'amount_spent' => $account['amount_spent'] ?? 0,
                    'updated_at' => now()->toIso8601String()
                ];
            }
            
            return $formattedAccounts;
        } catch (\Exception $e) {
            Log::error('Error al obtener cuentas publicitarias: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las campañas para la cuenta publicitaria seleccionada
     */
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
                'stop_time',
                'daily_budget',
                'lifetime_budget'
            ];
            
            $params = [
                'limit' => 1000,
                'status' => ['ACTIVE', 'PAUSED', 'ARCHIVED']
            ];

            $campaigns = $this->adAccount->getCampaigns($fields, $params);
            
            $result = [];
            foreach ($campaigns as $campaign) {
                $result[] = [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'objective' => $campaign->objective,
                    'created_time' => $campaign->created_time,
                    'start_time' => $campaign->start_time ?? null,
                    'stop_time' => $campaign->stop_time ?? null,
                    'daily_budget' => $campaign->daily_budget ?? null,
                    'lifetime_budget' => $campaign->lifetime_budget ?? null,
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error al obtener campañas: ' . $e->getMessage(), [
                'account_id' => $this->adAccountId
            ]);
            return [];
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


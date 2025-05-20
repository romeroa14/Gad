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
     * Obtiene las campañas con sus conjuntos de anuncios y anuncios
     * para poder acceder a información de page_id e instagram_account_id
     */
    public function getCampaignsWithAdsHierarchy()
    {
        try {
            Log::info("Solicitando jerarquía completa de campañas para la cuenta: {$this->adAccountId}");
            
            // 1. Obtener campañas
            $campaignFields = [
                'id',
                'name',
                'status',
                'objective',
                'created_time',
                'start_time',
                'stop_time',
                'daily_budget',
                'lifetime_budget',
                'promoted_object'
            ];
            
            $campaignParams = [
                'limit' => 100,
                'status' => ['ACTIVE', 'PAUSED', 'ARCHIVED']
            ];

            // Obtener todas las campañas
            $campaignsResponse = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v18.0/{$this->adAccountId}/campaigns", [
                    'fields' => implode(',', $campaignFields),
                    'limit' => 100
                ]);
                
            if (!$campaignsResponse->successful()) {
                Log::error("Error al obtener campañas", [
                    'response' => $campaignsResponse->body(),
                    'status' => $campaignsResponse->status()
                ]);
                return [];
            }
            
            $campaigns = $campaignsResponse->json('data', []);
            $result = [];
            
            // 2. MÉTODO ALTERNATIVO: Obtener todos los anuncios directamente de la cuenta
            // Esto nos permitirá encontrar más page_ids e instagram_ids
            $allAdsResponse = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v18.0/{$this->adAccountId}/ads", [
                    'fields' => 'id,name,campaign_id,creative{object_story_spec,effective_object_story_id,object_story_id}',
                    'limit' => 500  // Aumentamos el límite para obtener más anuncios
                ]);
                
            $allAdsMap = [];
            $adToCampaignMap = [];
            
            if ($allAdsResponse->successful()) {
                $allAds = $allAdsResponse->json('data', []);
                Log::info("Obtenidos " . count($allAds) . " anuncios para la cuenta", [
                    'account_id' => $this->adAccountId
                ]);
                
                // Organizar anuncios por campaña
                foreach ($allAds as $ad) {
                    if (!empty($ad['campaign_id'])) {
                        $adToCampaignMap[$ad['id']] = $ad['campaign_id'];
                        
                        if (!isset($allAdsMap[$ad['campaign_id']])) {
                            $allAdsMap[$ad['campaign_id']] = [];
                        }
                        
                        $allAdsMap[$ad['campaign_id']][] = $ad;
                    }
                }
            } else {
                Log::warning("No se pudieron obtener todos los anuncios de la cuenta", [
                    'response' => $allAdsResponse->body(),
                    'status' => $allAdsResponse->status()
                ]);
            }
            
            // 3. Procesar cada campaña
            foreach ($campaigns as $campaign) {
                $campaignData = [
                    'id' => $campaign['id'],
                    'name' => $campaign['name'],
                    'status' => $campaign['status'],
                    'objective' => $campaign['objective'] ?? null,
                    'created_time' => $campaign['created_time'] ?? null,
                    'start_time' => $campaign['start_time'] ?? null,
                    'stop_time' => $campaign['stop_time'] ?? null,
                    'daily_budget' => $campaign['daily_budget'] ?? null,
                    'lifetime_budget' => $campaign['lifetime_budget'] ?? null,
                    'page_id' => null,
                    'page_name' => null,
                    'instagram_account_id' => null,
                    'instagram_username' => null
                ];
                
                // Extraer información directamente de promoted_object si está disponible
                if (!empty($campaign['promoted_object'])) {
                    if (!empty($campaign['promoted_object']['page_id'])) {
                        $campaignData['page_id'] = $campaign['promoted_object']['page_id'];
                        Log::info("Encontrado page_id en promoted_object", [
                            'campaign_id' => $campaign['id'],
                            'page_id' => $campaign['promoted_object']['page_id']
                        ]);
                    }
                    
                    if (!empty($campaign['promoted_object']['instagram_account_id'])) {
                        $campaignData['instagram_account_id'] = $campaign['promoted_object']['instagram_account_id'];
                        Log::info("Encontrado instagram_account_id en promoted_object", [
                            'campaign_id' => $campaign['id'],
                            'instagram_id' => $campaign['promoted_object']['instagram_account_id']
                        ]);
                    }
                }
                
                // Si no tenemos page_id o instagram_id, buscar en los anuncios previamente obtenidos
                if (empty($campaignData['page_id']) || empty($campaignData['instagram_account_id'])) {
                    if (isset($allAdsMap[$campaign['id']])) {
                        foreach ($allAdsMap[$campaign['id']] as $ad) {
                            // Buscar en object_story_spec
                            if (!empty($ad['creative']['object_story_spec'])) {
                                $storySpec = $ad['creative']['object_story_spec'];
                                
                                if (empty($campaignData['page_id']) && !empty($storySpec['page_id'])) {
                                    $campaignData['page_id'] = $storySpec['page_id'];
                                    Log::info("Encontrado page_id en object_story_spec", [
                                        'campaign_id' => $campaign['id'],
                                        'ad_id' => $ad['id'],
                                        'page_id' => $storySpec['page_id']
                                    ]);
                                }
                                
                                if (empty($campaignData['instagram_account_id']) && !empty($storySpec['instagram_actor_id'])) {
                                    $campaignData['instagram_account_id'] = $storySpec['instagram_actor_id'];
                                    Log::info("Encontrado instagram_account_id en object_story_spec", [
                                        'campaign_id' => $campaign['id'],
                                        'ad_id' => $ad['id'],
                                        'instagram_id' => $storySpec['instagram_actor_id']
                                    ]);
                                }
                            }
                            
                            // Buscar en effective_object_story_id o object_story_id
                            if (empty($campaignData['page_id'])) {
                                $storyId = isset($ad['creative']['effective_object_story_id']) ? $ad['creative']['effective_object_story_id'] : 
                                          (isset($ad['creative']['object_story_id']) ? $ad['creative']['object_story_id'] : null);
                                
                                if ($storyId && is_string($storyId) && strpos($storyId, '_') !== false) {
                                    $parts = explode('_', $storyId);
                                    if (count($parts) >= 2 && is_numeric($parts[0])) {
                                        $campaignData['page_id'] = $parts[0];
                                        Log::info("Encontrado page_id en story_id", [
                                            'campaign_id' => $campaign['id'],
                                            'ad_id' => $ad['id'],
                                            'story_id' => $storyId,
                                            'extracted_page_id' => $parts[0]
                                        ]);
                                    }
                                }
                            }
                            
                            // Si ya encontramos ambos, salir del bucle
                            if (!empty($campaignData['page_id']) && !empty($campaignData['instagram_account_id'])) {
                                break;
                            }
                        }
                    }
                }
                
                // 4. Si aún no tenemos page_id o instagram_id, obtener todos los anuncios específicamente para esta campaña
                if (empty($campaignData['page_id']) && empty($campaignData['instagram_account_id'])) {
                    try {
                        $campaignAdsResponse = Http::withToken($this->accessToken)
                            ->get("https://graph.facebook.com/v18.0/{$campaign['id']}/ads", [
                                'fields' => 'creative{object_story_spec,effective_object_story_id,object_story_id}',
                                'limit' => 50
                            ]);
                        
                        if ($campaignAdsResponse->successful()) {
                            $campaignAds = $campaignAdsResponse->json('data', []);
                            
                            foreach ($campaignAds as $ad) {
                                // Similar a la lógica anterior, buscar en object_story_spec
                                if (!empty($ad['creative']['object_story_spec'])) {
                                    $storySpec = $ad['creative']['object_story_spec'];
                                    
                                    if (empty($campaignData['page_id']) && !empty($storySpec['page_id'])) {
                                        $campaignData['page_id'] = $storySpec['page_id'];
                                    }
                                    
                                    if (empty($campaignData['instagram_account_id']) && !empty($storySpec['instagram_actor_id'])) {
                                        $campaignData['instagram_account_id'] = $storySpec['instagram_actor_id'];
                                    }
                                }
                                
                                // Buscar en effective_object_story_id o object_story_id
                                if (empty($campaignData['page_id'])) {
                                    $storyId = isset($ad['creative']['effective_object_story_id']) ? $ad['creative']['effective_object_story_id'] : 
                                              (isset($ad['creative']['object_story_id']) ? $ad['creative']['object_story_id'] : null);
                                    
                                    if ($storyId && is_string($storyId) && strpos($storyId, '_') !== false) {
                                        $parts = explode('_', $storyId);
                                        if (count($parts) >= 2 && is_numeric($parts[0])) {
                                            $campaignData['page_id'] = $parts[0];
                                        }
                                    }
                                }
                                
                                if (!empty($campaignData['page_id']) && !empty($campaignData['instagram_account_id'])) {
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error al obtener anuncios para la campaña " . $campaign['id'] . ": " . $e->getMessage());
                    }
                }
                
                // 5. Obtener detalles de página y/o cuenta de Instagram si encontramos IDs
                if (!empty($campaignData['page_id'])) {
                    try {
                        $pageResponse = Http::withToken($this->accessToken)
                            ->get("https://graph.facebook.com/v18.0/{$campaignData['page_id']}", [
                                'fields' => 'name,link'
                            ]);
                        
                        if ($pageResponse->successful()) {
                            $pageData = $pageResponse->json();
                            $campaignData['page_name'] = $pageData['name'] ?? null;
                            $campaignData['page_link'] = $pageData['link'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::error("Error al obtener información de la página " . $campaignData['page_id'] . ": " . $e->getMessage());
                    }
                }
                
                if (!empty($campaignData['instagram_account_id'])) {
                    try {
                        $igResponse = Http::withToken($this->accessToken)
                            ->get("https://graph.facebook.com/v18.0/{$campaignData['instagram_account_id']}", [
                                'fields' => 'username'
                            ]);
                        
                        if ($igResponse->successful()) {
                            $igData = $igResponse->json();
                            $campaignData['instagram_username'] = $igData['username'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::error("Error al obtener información de Instagram " . $campaignData['instagram_account_id'] . ": " . $e->getMessage());
                    }
                }
                
                $result[] = $campaignData;
            }
            
            // Log resumen
            $withPageId = count(array_filter($result, function($c) { return !empty($c['page_id']); }));
            $withInstagram = count(array_filter($result, function($c) { return !empty($c['instagram_account_id']); }));
            
            Log::info("Jerarquía de campañas obtenida", [
                'total_campaigns' => count($result),
                'with_page_id' => $withPageId,
                'with_instagram' => $withInstagram
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error al obtener jerarquía de campañas: ' . $e->getMessage(), [
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


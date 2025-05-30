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
use App\Models\AdsSet;
use App\Models\Ad;
use App\Models\AdsCampaign;

class FacebookAdsService
{
    protected $api;
    protected $adAccount;
    protected $accessToken;
    protected $adAccountId;

    /**
     * Rate limiting configuración
     */
    private const MAX_CALLS_PER_MINUTE = 25;
    private const DELAY_BETWEEN_CALLS = 2.5; // segundos
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 10; // segundos

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
     * Extraer información de página e Instagram de un ad creative
     */
    private function extractSocialInfo($creative)
    {
        $socialInfo = [
            'page_id' => null,
            'page_name' => null,
            'instagram_account_id' => null,
            'instagram_username' => null
        ];

        try {
            // Buscar page_id en object_story_spec
            if (!empty($creative['object_story_spec']['page_id'])) {
                $socialInfo['page_id'] = $creative['object_story_spec']['page_id'];
            }

            // Buscar instagram_account_id
            if (!empty($creative['object_story_spec']['instagram_actor_id'])) {
                $socialInfo['instagram_account_id'] = $creative['object_story_spec']['instagram_actor_id'];
            }

            // Buscar en effective_object_story_id como fallback
            if (!$socialInfo['page_id'] && !empty($creative['effective_object_story_id'])) {
                $storyId = $creative['effective_object_story_id'];
                if (strpos($storyId, '_') !== false) {
                    $parts = explode('_', $storyId);
                    if (is_numeric($parts[0])) {
                        $socialInfo['page_id'] = $parts[0];
                    }
                }
            }

            // Obtener nombres si tenemos IDs
            if ($socialInfo['page_id']) {
                $socialInfo['page_name'] = $this->getPageName($socialInfo['page_id']);
            }

            if ($socialInfo['instagram_account_id']) {
                $socialInfo['instagram_username'] = $this->getInstagramUsername($socialInfo['instagram_account_id']);
            }

            Log::info("Información social extraída:", $socialInfo);

        } catch (\Exception $e) {
            Log::warning("Error extrayendo información social: " . $e->getMessage());
        }

        return $socialInfo;
    }

    /**
     * Obtener nombre de página (con cache)
     */
    private function getPageName($pageId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v18.0/{$pageId}", [
                    'fields' => 'name'
                ]);
            
            if ($response->successful()) {
                return $response->json('name');
            }
        } catch (\Exception $e) {
            Log::warning("Error obteniendo nombre de página {$pageId}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Obtener username de Instagram (con cache)
     */
    private function getInstagramUsername($instagramId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v18.0/{$instagramId}", [
                    'fields' => 'username'
                ]);
            
            if ($response->successful()) {
                return $response->json('username');
            }
        } catch (\Exception $e) {
            Log::warning("Error obteniendo username de Instagram {$instagramId}: " . $e->getMessage());
        }
        
        return null;
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

    /**
     * Sincronización completa con procesamiento por lotes
     */
    public function syncCompleteHierarchy($batchSize = 10)
    {
        try {
            Log::info("=== INICIO SINCRONIZACIÓN POR LOTES ===", [
                'account_id' => $this->adAccountId,
                'batch_size' => $batchSize
            ]);
            
            $result = [
                'campaigns_synced' => 0,
                'adsets_synced' => 0,
                'ads_synced' => 0,
                'errors' => [],
                'batches_processed' => 0
            ];

            // 1. Obtener todas las campañas
            $campaigns = $this->getCampaigns();
            $totalCampaigns = count($campaigns);
            
            // 2. Procesar campañas en lotes
            $campaignBatches = array_chunk($campaigns, $batchSize);
            
            foreach ($campaignBatches as $batchIndex => $campaignBatch) {
                Log::info("=== PROCESANDO LOTE " . ($batchIndex + 1) . "/" . count($campaignBatches) . " ===", [
                    'campaigns_in_batch' => count($campaignBatch),
                    'total_campaigns' => $totalCampaigns
                ]);
                
                foreach ($campaignBatch as $campaignData) {
                    try {
                        // Sincronizar campaña
                        $campaign = $this->syncCampaign($campaignData);
                        $result['campaigns_synced']++;
                        
                        // Sincronizar AdSets de la campaña
                        $adSets = $this->getAdSetsForCampaign($campaignData['id']);
                        
                        foreach ($adSets as $adSetData) {
                            try {
                                $adSet = $this->syncAdSet($campaign->id, $adSetData);
                                $result['adsets_synced']++;
                                
                                // Sincronizar Ads del AdSet
                                $ads = $this->getAdsForAdSet($adSetData['id']);
                                
                                foreach ($ads as $adData) {
                                    try {
                                        $this->syncAd($adSet->id, $adData);
                                        $result['ads_synced']++;
                                    } catch (\Exception $e) {
                                        Log::error("Error sincronizando Ad: " . $e->getMessage());
                                        $result['errors'][] = "Ad {$adData['id']}: " . $e->getMessage();
                                    }
                                }
                                
                            } catch (\Exception $e) {
                                Log::error("Error sincronizando AdSet: " . $e->getMessage());
                                $result['errors'][] = "AdSet {$adSetData['id']}: " . $e->getMessage();
                            }
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Error sincronizando campaña: " . $e->getMessage());
                        $result['errors'][] = "Campaña {$campaignData['id']}: " . $e->getMessage();
                    }
                }
                
                $result['batches_processed']++;
                
                // Pausa más larga entre lotes
                if ($batchIndex < count($campaignBatches) - 1) {
                    Log::info("Pausa entre lotes de 30 segundos...");
                    sleep(30);
                }
            }

            Log::info("=== SINCRONIZACIÓN COMPLETADA ===", $result);
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error en sincronización completa: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Hacer llamada con rate limiting y retry
     */
    private function makeApiCallWithRetry($apiCall, $description = 'API Call')
    {
        $attempts = 0;
        $maxAttempts = self::MAX_RETRIES;
        
        while ($attempts < $maxAttempts) {
            try {
                // Delay entre llamadas para respetar rate limits
                if ($attempts > 0) {
                    $delay = self::RETRY_DELAY * $attempts;
                    Log::info("Reintentando en {$delay} segundos...", ['description' => $description, 'attempt' => $attempts + 1]);
                    sleep($delay);
                } else {
                    // Delay normal entre llamadas
                    usleep(self::DELAY_BETWEEN_CALLS * 1000000); // convertir a microsegundos
                }
                
                Log::info("Ejecutando: {$description}", ['attempt' => $attempts + 1]);
                return $apiCall();
                
            } catch (\Exception $e) {
                $attempts++;
                
                // Verificar si es rate limit error
                if ($this->isRateLimitError($e)) {
                    Log::warning("Rate limit alcanzado. Reintentando...", [
                        'description' => $description,
                        'attempt' => $attempts,
                        'max_attempts' => $maxAttempts,
                        'error' => $e->getMessage()
                    ]);
                    
                    if ($attempts >= $maxAttempts) {
                        Log::error("Máximo de reintentos alcanzado para: {$description}");
                        throw $e;
                    }
                    continue;
                }
                
                // Si no es rate limit, fallar inmediatamente
                Log::error("Error no relacionado con rate limit: {$description}", ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    }

    /**
     * Verificar si el error es de rate limiting
     */
    private function isRateLimitError($exception)
    {
        $message = $exception->getMessage();
        return strpos($message, 'User request limit reached') !== false ||
               strpos($message, 'rate limit') !== false ||
               strpos($message, 'too many calls') !== false;
    }

    /**
     * Obtener campañas con campos completos usando SDK
     */
    public function getCampaigns()
    {
        return $this->makeApiCallWithRetry(function() {
            // Usar el SDK de Facebook en lugar de HTTP directo
            $adAccount = new AdAccount($this->adAccountId);
            
            $campaigns = $adAccount->getCampaigns(
                [
                    'effective_status' => ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED']
                ],
                [
                    'id',
                    'name', 
                    'status',
                    'effective_status',
                    'objective',
                    'created_time',
                    'start_time',
                    'stop_time',
                    'daily_budget',
                    'lifetime_budget',
                    'updated_time'
                ]
            );

            $result = $campaigns->getResponse()->getContent();
            $campaignsData = $result['data'] ?? [];
            
            Log::info("Campañas obtenidas con SDK de Facebook", [
                'total_campaigns' => count($campaignsData),
                'sample_campaign' => !empty($campaignsData) ? $campaignsData[0] : null
            ]);

            return $campaignsData;
        }, "Campañas con SDK");
    }

    /**
     * Sincronizar una campaña con validación de datos
     */
    public function syncCampaign($campaignData)
    {
        try {
            // Validar que los datos mínimos estén presentes
            if (empty($campaignData['id'])) {
                throw new \Exception("ID de campaña faltante en los datos");
            }

            // Debuggear los datos que llegam
            Log::info("Datos de campaña recibidos:", $campaignData);

            // Extraer información adicional del nombre (si existe)
            $campaignInfo = $this->extractCampaignInfo($campaignData['name'] ?? 'Campaña sin nombre');
            
            // Preparar datos con valores por defecto
            $syncData = [
                'name' => $campaignData['name'] ?? "Campaña {$campaignData['id']}",
                'status' => $this->mapFacebookStatus($campaignData['status'] ?? null),
                'budget' => $this->parseFloatValue($campaignData['daily_budget'] ?? $campaignData['lifetime_budget'] ?? null),
                'client_id' => $campaignInfo['client_id'],
                'plan_id' => $campaignInfo['plan_id'],
                'advertising_account_id' => session('selected_advertising_account_id'),
                'start_date' => !empty($campaignData['start_time']) ? new \DateTime($campaignData['start_time']) : null,
                'end_date' => !empty($campaignData['stop_time']) ? new \DateTime($campaignData['stop_time']) : null,
                'meta_insights' => $campaignData,
                'last_synced_at' => now()
            ];

            $campaign = AdsCampaign::updateOrCreate(
                ['meta_campaign_id' => $campaignData['id']],
                $syncData
            );

            Log::info("Campaña sincronizada exitosamente", [
                'id' => $campaign->id,
                'meta_id' => $campaign->meta_campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status
            ]);

            return $campaign;
            
        } catch (\Exception $e) {
            Log::error("Error sincronizando campaña: " . $e->getMessage(), [
                'campaign_data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Extraer client_id y plan_id del nombre de la campaña
     * Ejemplo: "Cliente ABC | Campaña XYZ" -> buscar cliente "Cliente ABC"
     */
    private function extractCampaignInfo($campaignName)
    {
        $client_id = null;
        $plan_id = null;
        
        try {
            // Ejemplo de lógica para extraer cliente del nombre
            if (preg_match('/^([^|]+)/', $campaignName, $matches)) {
                $clientName = trim($matches[1]);
                
                // Buscar cliente por nombre (descomentar cuando tengas tabla clients)
                $client = \App\Models\Client::where('name', 'LIKE', "%{$clientName}%")->first();
                if ($client) {
                    $client_id = $client->id;
                    
                    // Buscar plan activo del cliente
                    $plan = $client->plans()->where('status', 'active')->first();
                    if ($plan) {
                        $plan_id = $plan->id;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error extrayendo info de campaña: " . $e->getMessage());
        }
        
        return [
            'client_id' => $client_id,
            'plan_id' => $plan_id
        ];
    }

    /**
     * Obtener AdSets con rate limiting
     */
    public function getAdSetsForCampaign($campaignId)
    {
        return $this->makeApiCallWithRetry(function() use ($campaignId) {
            $adAccount = new AdAccount($this->adAccountId);
            $adSets = $adAccount->getAdSets([
                'campaign_id' => $campaignId,
                'status' => ['ACTIVE', 'PAUSED'],
                'limit' => 50
            ], [
                'id', 'name', 'status', 'daily_budget', 'lifetime_budget',
                'optimization_goal', 'billing_event', 'targeting'
            ]);
            
            $result = $adSets->getResponse()->getContent();
            Log::info("Obtenidos " . count($result['data']) . " AdSets para campaña {$campaignId}");
            
            return $result['data'] ?? [];
        }, "AdSets para campaña {$campaignId}");
    }

    /**
     * Sincronizar un AdSet individual
     */
    public function syncAdSet($campaignId, $adSetData)
    {
        try {
            // Convertir budgets de centavos a dólares
            $dailyBudget = isset($adSetData['daily_budget']) ? $adSetData['daily_budget'] / 100 : null;
            $lifetimeBudget = isset($adSetData['lifetime_budget']) ? $adSetData['lifetime_budget'] / 100 : null;

            $adsSet = \App\Models\AdsSet::updateOrCreate(
                ['meta_adset_id' => $adSetData['id']],
                [
                    'ads_campaign_id' => $campaignId,
                    'name' => $adSetData['name'],
                    'status' => $this->mapFacebookStatus($adSetData['status']),
                    'daily_budget' => $dailyBudget,
                    'lifetime_budget' => $lifetimeBudget,
                    'target_spec' => $adSetData['targeting'] ?? null,
                    'optimization_goal' => $adSetData['optimization_goal'] ?? null,
                    'billing_event' => $adSetData['billing_event'] ?? null,
                    'meta_insights' => $adSetData,
                    // Campos sociales se llenarán cuando sincronicemos los ads
                    'page_id' => null,
                    'page_name' => null,
                    'instagram_account_id' => null,
                    'instagram_username' => null,
                ]
            );

            return $adsSet;

        } catch (\Exception $e) {
            Log::error("Error sincronizando AdSet {$adSetData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener Ads con rate limiting
     */
    public function getAdsForAdSet($adSetId)
    {
        return $this->makeApiCallWithRetry(function() use ($adSetId) {
            $adAccount = new AdAccount($this->adAccountId);
            $ads = $adAccount->getAds([
                'adset_id' => $adSetId,
                'status' => ['ACTIVE', 'PAUSED'],
                'limit' => 50
            ], [
                'id', 'name', 'status', 'creative'
            ]);
            
            $result = $ads->getResponse()->getContent();
            Log::info("Obtenidos " . count($result['data']) . " Ads para AdSet {$adSetId}");
            
            return $result['data'] ?? [];
        }, "Ads para AdSet {$adSetId}");
    }

    /**
     * Sincronizar un Ad individual
     */
    public function syncAd($adsSetId, $adData)
    {
        try {
            // Extraer información social del creative
            $socialInfo = $this->extractSocialInfo($adData['creative'] ?? []);

            $ad = \App\Models\Ad::updateOrCreate(
                ['meta_ad_id' => $adData['id']],
                [
                    'ads_set_id' => $adsSetId,
                    'name' => $adData['name'],
                    'status' => $this->mapFacebookStatus($adData['status']),
                    'creative_id' => $adData['creative']['id'] ?? null,
                    'creative_url' => $adData['creative']['image_url'] ?? null,
                    'thumbnail_url' => $adData['creative']['thumbnail_url'] ?? null,
                    'preview_url' => null, // Se puede agregar después
                    'meta_insights' => $adData,
                    'page_id' => $socialInfo['page_id'],
                    'page_name' => $socialInfo['page_name'],
                    'instagram_account_id' => $socialInfo['instagram_account_id'],
                    'instagram_username' => $socialInfo['instagram_username'],
                ]
            );

            // Actualizar el AdSet con la información social si no la tiene
            $adSet = \App\Models\AdsSet::find($adsSetId);
            if ($adSet && !$adSet->page_id && $socialInfo['page_id']) {
                $adSet->update([
                    'page_id' => $socialInfo['page_id'],
                    'page_name' => $socialInfo['page_name'],
                    'instagram_account_id' => $socialInfo['instagram_account_id'],
                    'instagram_username' => $socialInfo['instagram_username'],
                ]);
            }

            return $ad;

        } catch (\Exception $e) {
            Log::error("Error sincronizando Ad {$adData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper para parsear valores float
     */
    private function parseFloatValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Facebook devuelve budgets en centavos
        if (is_numeric($value)) {
            return (float) $value / 100;
        }
        
        return null;
    }

    /**
     * Mapear estados de Facebook a estados locales (arreglado para NULL)
     */
    private function mapFacebookStatus($facebookStatus)
    {
        // Manejar valores null, vacíos o no string
        if ($facebookStatus === null || $facebookStatus === '') {
            return 'inactive';
        }
        
        // Convertir a string de forma segura
        $status = strtoupper((string) $facebookStatus);
        
        switch ($status) {
            case 'ACTIVE':
                return 'active';
            case 'PAUSED':
                return 'paused';
            case 'ARCHIVED':
            case 'COMPLETED':
                return 'completed';
            case 'DELETED':
                return 'deleted';
            case 'DISAPPROVED':
                return 'rejected';
            case 'WITH_ISSUES':
                return 'issue';
            default:
                return 'inactive';
        }
    }
}


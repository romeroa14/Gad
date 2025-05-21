<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FacebookAds\FacebookAdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookAdsController extends Controller
{
    /**
     * Obtiene los anuncios para un conjunto específico
     */
    public function getAdsForAdSet(string $adSetId)
    {
        Log::info("API: Solicitando anuncios para AdSet ID: {$adSetId}");
        
        try {
            $accountId = session('selected_advertising_account_id');
            Log::info("Usando cuenta publicitaria: {$accountId}");
            
            $service = new FacebookAdsService($accountId);
            $ads = $service->getAdsForAdSet($adSetId);
            
            return response()->json($ads);
        } catch (\Exception $e) {
            Log::error("Error obteniendo anuncios: " . $e->getMessage(), [
                'adset_id' => $adSetId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'adset_id' => $adSetId
            ], 500);
        }
    }
} 
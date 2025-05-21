<?php

use App\Http\Controllers\FacebookAdsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('facebook')->group(function () {
    Route::get('/test', [FacebookAdsController::class, 'testConnection']);
    Route::get('/insights', [FacebookAdsController::class, 'getInsights']);
    Route::get('/friends', [FacebookAdsController::class, 'getFriends']);
    Route::get('/adset/{adSetId}/ads', function (string $adSetId) {
        // Añadir logging detallado
        Log::info("Solicitando anuncios para AdSet ID: {$adSetId}");
        
        try {
            // No tomar la cuenta del usuario autenticado, usar la de sesión
            $accountId = session('selected_advertising_account_id');
            Log::info("Usando cuenta publicitaria: {$accountId}");
            
            $service = new \App\Services\FacebookAds\FacebookAdsService($accountId);
            
            // Intentar obtener anuncios con manejo de errores detallado
            $ads = $service->getAdsForAdSet($adSetId);
            
            Log::info("Anuncios recuperados: " . count($ads));
            
            // Si no hay anuncios, retornar array vacío explícitamente
            if (empty($ads)) {
                Log::info("No se encontraron anuncios para este conjunto");
                return response()->json([]);
            }
            
            return response()->json($ads);
        } catch (\Exception $e) {
            Log::error("Error obteniendo anuncios: " . $e->getMessage(), [
                'adset_id' => $adSetId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'No se pudieron cargar los anuncios',
                'message' => $e->getMessage(),
                'adset_id' => $adSetId
            ], 500);
        }
    });

    Route::get('/adset/{adSetId}/ads', [App\Http\Controllers\Api\FacebookAdsController::class, 'getAdsForAdSet']);
});

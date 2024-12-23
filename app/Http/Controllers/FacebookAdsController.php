<?php

namespace App\Http\Controllers;

use App\Services\FacebookAds\FacebookAdsService;
use Illuminate\Http\Request;

class FacebookAdsController extends Controller
{
    protected $facebookAdsService;

    public function __construct(FacebookAdsService $facebookAdsService)
    {
        $this->facebookAdsService = $facebookAdsService;
    }

    public function getFriends()
    {
        $facebookService = new FacebookAdsService();
        $response = $facebookService->makeRequest('friends', '10232575857351584');
        return response()->json($response);
    }

    public function testConnection()
    {
        try {
            $facebookService = new FacebookAdsService();
            $campaigns = $facebookService->getCampaigns();
            return response()->json([
                'success' => true,
                'campaigns' => $campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getInsights()
    {
        try {
            $insights = $this->facebookAdsService->getAccountInsights();
            
            // Verificar si hay error en la respuesta
            if (isset($insights['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $insights['error']['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'insights' => $insights
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testToken()
    {
        try {
            $result = $this->facebookAdsService->testToken();
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 
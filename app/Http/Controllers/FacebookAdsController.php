<?php

namespace App\Http\Controllers;

use App\Services\FacebookAds\FacebookAdsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacebookAdsController extends Controller
{
    protected $facebookAdsService;

    public function __construct(FacebookAdsService $facebookAdsService)
    {
        $this->facebookAdsService = $facebookAdsService;
    }

    public function testConnection(): JsonResponse
    {
        try {
            $campaigns = $this->facebookAdsService->getCampaigns();
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

    public function getInsights(): JsonResponse
    {
        try {
            $insights = $this->facebookAdsService->getAccountInsights();
            
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

    public function debug(): JsonResponse
    {
        return response()->json([
            'from_env' => env('FACEBOOK_AD_ACCOUNT_ID'),
            'from_config' => config('services.facebook.ad_account_id'),
            'all_config' => config('services.facebook')
        ]);
    }
} 
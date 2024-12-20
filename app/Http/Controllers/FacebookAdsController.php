<?php

namespace App\Http\Controllers;

use App\Services\FacebookAdsService;
use Illuminate\Http\Request;

class FacebookAdsController extends Controller
{
    protected $facebookAdsService;

    public function __construct(FacebookAdsService $facebookAdsService)
    {
        $this->facebookAdsService = $facebookAdsService;
    }

    public function testConnection()
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

    public function getInsights()
    {
        try {
            $insights = $this->facebookAdsService->getAccountInsights();
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
} 
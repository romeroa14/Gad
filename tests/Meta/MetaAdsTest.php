<?php

namespace Tests\Meta;

use Tests\TestCase;
use App\Services\MetaAdsService;

class MetaAdsTest extends TestCase
{
    public function test_meta_ads_connection()
    {
        $metaAds = new MetaAdsService();
        
        try {
            $account = $metaAds->getAdAccount();
            $this->assertNotNull($account);
            
            // Intenta obtener campañas
            $campaigns = $account->getCampaigns();
            $this->assertNotNull($campaigns);
            
            echo "Conexión exitosa. Campañas encontradas: " . count($campaigns);
        } catch (\Exception $e) {
            $this->fail("Error de conexión: " . $e->getMessage());
        }
    }
} 
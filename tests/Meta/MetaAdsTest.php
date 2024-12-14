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
            // Desencriptar el token si es necesario
            $token = decrypt(config('services.meta.access_token'));
            
            $account = $metaAds->getAdAccount();
            $this->assertNotNull($account);
            
            $campaigns = $account->getCampaigns();
            $this->assertNotNull($campaigns);
            
            $this->assertTrue(count($campaigns) >= 0, "Conexión exitosa. Campañas encontradas: " . count($campaigns));
        } catch (\Exception $e) {
            $this->fail("Error de conexión: " . $e->getMessage());
        }
    }
} 
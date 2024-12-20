<?php

namespace App\Services\FacebookAds;

use App\Models\FacebookCampaign;
use FacebookAds\Object\Campaign;

class CampaignService extends FacebookAdsService
{
    public function syncCampaigns()
    {
        $campaigns = $this->getAdAccount()->getCampaigns();

        foreach ($campaigns as $campaign) {
            FacebookCampaign::updateOrCreate(
                ['campaign_id' => $campaign->id],
                [
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'objective' => $campaign->objective,
                    // ... otros campos
                ]
            );
        }
    }

    public function createCampaign(array $data)
    {
        // Crear campaña localmente
        $localCampaign = FacebookCampaign::create($data);

        // Crear campaña en Facebook
        $campaign = new Campaign(null, $this->getAdAccount()->id);
        $campaign->setData([
            'name' => $data['name'],
            'objective' => $data['objective'],
            'status' => $data['status'],
        ]);
        
        $campaign->create();

        // Actualizar ID de campaña local
        $localCampaign->update(['campaign_id' => $campaign->id]);

        return $localCampaign;
    }
}

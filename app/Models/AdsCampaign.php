<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsCampaign extends Model
{
    protected $fillable = [
        'name',
        'client_id',
        'plan',
        'start_date',
        'end_date',
        'budget',
        'real_cost',
        'status',
        'meta_campaign_id',
        'last_synced_at'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_synced_at' => 'datetime',
        'meta_insights' => 'array'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function metrics()
    {
        return $this->hasMany(CampaignMetric::class);
    }

    public function syncWithMetaAds()
    {
        if (!$this->meta_campaign_id) {
            return;
        }

        try {
            $metaAdsService = new MetaAdsService();
            $data = $metaAdsService->getCampaignData($this->meta_campaign_id);

            // Actualizar datos de la campaña
            $this->update([
                'real_cost' => $data['campaign']['spend'] ?? 0,
                'status' => $this->mapMetaStatus($data['campaign']['status']),
                'last_synced_at' => now(),
            ]);

            // Guardar métricas
            if (isset($data['insights'][0])) {
                $this->metrics()->create([
                    'date' => now()->toDateString(),
                    'impressions' => $data['insights'][0]['impressions'],
                    'clicks' => $data['insights'][0]['clicks'],
                    'ctr' => $data['insights'][0]['ctr'],
                    'spend' => $data['insights'][0]['spend'],
                    'reach' => $data['insights'][0]['reach'],
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Error syncing campaign {$this->id}: " . $e->getMessage());
        }
    }

    private function mapMetaStatus($metaStatus)
    {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'COMPLETED' => 'completed',
            // ... otros estados
        ];

        return $statusMap[$metaStatus] ?? 'unknown';
    }
}
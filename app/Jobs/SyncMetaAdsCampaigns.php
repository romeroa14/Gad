<?php

namespace App\Jobs;

use App\Models\AdsCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMetaAdsCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        AdsCampaign::whereNotNull('meta_campaign_id')
            ->where(function ($query) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subHours(1));
            })
            ->each(function ($campaign) {
                $campaign->syncWithMetaAds();
            });
    }
} 
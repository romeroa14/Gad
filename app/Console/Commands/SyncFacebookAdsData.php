<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncFacebookCampaigns;

class SyncFacebookAdsData extends Command
{
    protected $signature = 'facebook:sync';
    protected $description = 'Sincroniza datos de Facebook Ads';

    public function handle()
    {
        SyncFacebookCampaigns::dispatch();
        $this->info('Sincronización iniciada');
    }
}

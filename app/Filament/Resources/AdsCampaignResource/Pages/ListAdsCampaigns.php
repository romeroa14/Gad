<?php

namespace App\Filament\Resources\AdsCampaignResource\Pages;

use App\Filament\Resources\AdsCampaignResource;
use App\Models\AdvertisingAccount;
use App\Services\FacebookAds\FacebookAdsService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

class ListAdsCampaigns extends ListRecords
{
    protected static string $resource = AdsCampaignResource::class;

    
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            // Acción para cambiar de cuenta publicitaria
            
            
            
        ];
    }


   
    
    
}

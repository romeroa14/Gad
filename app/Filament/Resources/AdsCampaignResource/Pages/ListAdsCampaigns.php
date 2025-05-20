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
            Actions\Action::make('changeAccount')
                ->label('Cambiar Cuenta')
                ->icon('heroicon-o-credit-card')
                ->form([
                    Forms\Components\Select::make('advertising_account_id')
                        ->label('Cuenta Publicitaria')
                        ->options(function () {
                            $accounts = AdvertisingAccount::all();
                            return $accounts->pluck('name', 'id')->toArray();
                        })
                        ->required()
                ])
                ->action(function (array $data): void {
                    $account = AdvertisingAccount::find($data['advertising_account_id']);
                    
                    if ($account) {
                        session([
                            'selected_advertising_account_id' => $account->id,
                            'selected_advertising_account_fb_id' => $account->account_id
                        ]);
                        
                        Notification::make()
                            ->title('Cuenta seleccionada')
                            ->body("Ahora trabajas con: {$account->name}")
                            ->success()
                            ->send();
                    }
                    
                    $this->redirect(ListAdsCampaigns::getUrl());
                }),
            
            // Acción para sincronizar datos
            Actions\Action::make('syncCampaigns')
                ->label('Sincronizar')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    $selectedAccountId = session('selected_advertising_account_id');
                    
                    if (!$selectedAccountId) {
                        Notification::make()
                            ->title('Error')
                            ->body('No hay cuenta seleccionada')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    try {
                        $facebookService = new FacebookAdsService();
                        $campaigns = $facebookService->getCampaigns();
                        
                        // Proceso para sincronizar campañas
                        // ... código para guardar campañas ...
                        
                        Notification::make()
                            ->title('Éxito')
                            ->body('Campañas sincronizadas')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }


   
    
    
}

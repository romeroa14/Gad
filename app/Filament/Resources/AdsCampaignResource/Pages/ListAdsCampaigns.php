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

    // Método para mostrar un banner con la cuenta seleccionada
    protected function getHeader(): ?View
    {
        $selectedAccountId = session('selected_advertising_account_id');
        $account = null;
        
        if ($selectedAccountId) {
            if (is_string($selectedAccountId) && str_starts_with($selectedAccountId, 'act_')) {
                $account = AdvertisingAccount::where('account_id', $selectedAccountId)->first();
            } else {
                $account = AdvertisingAccount::find($selectedAccountId);
            }
        }
        
        return view('filament.headers.selected-advertising-account', [
            'account' => $account,
        ]);
    }
    
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

    protected function getHeading(): string
    {
        $selectedAccountId = session('selected_advertising_account_id');
        $accountName = '';
        
        if ($selectedAccountId) {
            $account = is_string($selectedAccountId) && str_starts_with($selectedAccountId, 'act_')
                ? \App\Models\AdvertisingAccount::where('account_id', $selectedAccountId)->first()
                : \App\Models\AdvertisingAccount::find($selectedAccountId);
                
            if ($account) {
                $accountName = " - {$account->name}";
            }
        }
        
        return parent::getHeading() . $accountName;
    }

    // Filtrar tabla según la cuenta seleccionada
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        $selectedAccountId = session('selected_advertising_account_id');
        if ($selectedAccountId) {
            $query->where('advertising_account_id', $selectedAccountId);
        }
        
        return $query;
    }
    
    // Mostrar indicador de estado
    protected function getFooter(): ?string
    {
        $selectedAccountId = session('selected_advertising_account_id');
        
        if (!$selectedAccountId) {
            return '<div class="text-center py-2 text-sm text-gray-500">No hay cuenta publicitaria seleccionada. Por favor selecciona una cuenta para ver campañas.</div>';
        }
        
        $account = is_string($selectedAccountId) && str_starts_with($selectedAccountId, 'act_')
            ? AdvertisingAccount::where('account_id', $selectedAccountId)->first()
            : AdvertisingAccount::find($selectedAccountId);
        
        if (!$account) {
            return '<div class="text-center py-2 text-sm text-gray-500">La cuenta seleccionada ya no existe.</div>';
        }
        
        return null;
    }
}

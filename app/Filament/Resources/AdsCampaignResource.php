<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdsCampaignResource\Pages;
use App\Models\AdsCampaign;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use App\Services\FacebookAds\FacebookAdsService;
use Illuminate\Support\Facades\Log;
class AdsCampaignResource extends Resource
{
    protected static ?string $model = AdsCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Campañas de Facebook';
    protected static ?string $pluralModelLabel = 'Campañas de Facebook';
    
    public static function getAdvertisingAccounts()
    {
        $metaAdsService = new FacebookAdsService();
        return $metaAdsService->getAdvertisingAccounts() ?? [];
    }
    
    public static function getSelectedAdvertisingAccount()
    {
        $selectedAccountId = session('selected_advertising_account_id');
        if (!$selectedAccountId) {
            return null;
        }
        
        return AdvertisingAccount::find($selectedAccountId) ?? 
               AdvertisingAccount::where('account_id', $selectedAccountId)->first();
    }
    
    public static function table(Table $table): Table
    {
        $selectedAccount = self::getSelectedAdvertisingAccount();
        $selectedClientId = session('selected_client_id');
        
        if (!$selectedAccount) {
            // Si no hay cuenta seleccionada, mostrar mensaje informativo
            return $table
                ->query(AdsCampaign::query()->where('id', 0))
                ->emptyStateHeading('No hay cuenta publicitaria seleccionada');
        }
        
        // Construir la consulta base
        $query = AdsCampaign::query()->where('advertising_account_id', $selectedAccount->id);
        
        // Añadir filtro por cliente si está seleccionado
        if ($selectedClientId) {
            $query->where('client_id', $selectedClientId);
        }
        
        return $table
            ->query($query)
            ->headerActions([
                Tables\Actions\Action::make('syncCampaigns')
                    ->label('Sincronizar Campañas')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function() use ($selectedAccount) {
                        try {
                            $service = new FacebookAdsService();
                            $campaigns = $service->getCampaigns($selectedAccount->account_id);
                            
                            // Obtener un cliente y plan por defecto
                            $defaultClient = \App\Models\Client::first();
                            $defaultPlan = \App\Models\Plan::first();
                            
                            if (!$defaultClient) {
                                throw new \Exception('Necesitas crear al menos un cliente antes de importar campañas');
                            }
                            
                            if (!$defaultPlan) {
                                throw new \Exception('Necesitas crear al menos un plan antes de importar campañas');
                            }
                            
                            $importedCount = 0;
                            
                            // Log para debug
                            Log::info('Iniciando importación de campañas', [
                                'cuenta' => $selectedAccount->name, 
                                'total_campañas' => count($campaigns)
                            ]);
                            
                            foreach ($campaigns as $campaignData) {
                                // Mostrar datos para debug
                                Log::info('Procesando campaña', ['data' => $campaignData]);
                                
                                // Procesar fechas
                                $startDate = !empty($campaignData['start_time']) 
                                    ? date('Y-m-d', strtotime($campaignData['start_time'])) 
                                    : now()->format('Y-m-d');
                                    
                                $endDate = !empty($campaignData['stop_time']) 
                                    ? date('Y-m-d', strtotime($campaignData['stop_time'])) 
                                    : now()->addDays(30)->format('Y-m-d');
                                
                                // Asegurar que todos los campos obligatorios estén incluidos
                                AdsCampaign::updateOrCreate(
                                    ['meta_campaign_id' => $campaignData['id']],
                                    [
                                        'name' => $campaignData['name'],
                                        'client_id' => $defaultClient->id,
                                        'plan_id' => $defaultPlan->id,
                                        'advertising_account_id' => $selectedAccount->id,
                                        'status' => $campaignData['status'] ?? 'ACTIVE',
                                        'budget' => 0,                          // Valor predeterminado
                                        'actual_cost' => 0,                     // Valor predeterminado
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                    ]
                                );
                                
                                $importedCount++;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("$importedCount campañas sincronizadas")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Error en sincronización: ' . $e->getMessage());
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meta_campaign_id')
                    ->label('ID de Meta')
                    ->sortable(),
               
                    
                    
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => 
                        match ($state) {
                            'active' => 'success',
                            'paused' => 'warning',
                            'completed' => 'info',
                            default => 'gray',
                        }
                    ),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('budget')
                    ->label('Presupuesto')
                    ->money('usd')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('actual_cost')
                    ->label('Gasto')
                    ->money('usd')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'completed' => 'Completada'
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('verEnFacebook')
                    ->label('Ver en Facebook')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (AdsCampaign $record) {
                        // Primero intentamos usar la cuenta asociada directamente a la campaña
                        $accountId = null;
                        
                        if ($record->advertisingAccount) {
                            // Usamos el account_id (ID de Facebook) no el ID interno de la base de datos
                            $accountId = $record->advertisingAccount->account_id;
                        } else {
                            // Si no tiene cuenta asociada, usamos la de la sesión
                            $selectedAccount = self::getSelectedAdvertisingAccount();
                            if ($selectedAccount) {
                                $accountId = $selectedAccount->account_id;
                            }
                        }
                        
                        // Asegurarnos de eliminar cualquier prefijo "act_" si existe
                        $accountId = str_replace('act_', '', $accountId);
                        
                        return "https://adsmanager.facebook.com/adsmanager/manage/ads?act={$accountId}&selected_campaign_ids={$record->meta_campaign_id}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (AdsCampaign $record) => !empty($record->meta_campaign_id)),
            ])
            ->bulkActions([
                // Normalmente no necesitamos acciones masivas para campañas de Facebook
            ])
            ->emptyStateHeading('No hay campañas')
            ->emptyStateDescription('Las campañas se sincronizarán desde Facebook Ads')
            ->emptyStateIcon('heroicon-o-document-text');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdsCampaigns::route('/'),
            'view' => Pages\ViewAdsCampaign::route('/{record}'),
        ];
    }
}

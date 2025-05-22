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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

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

                Tables\Actions\Action::make('changeAccount')
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
                    
                    // $this->redirect(ListAdsCampaigns::getUrl());
                }),

                Tables\Actions\Action::make('syncCampaigns')
                    ->label('Sincronizar Campañas')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function() use ($selectedAccount) {
                        try {
                            $service = new FacebookAdsService($selectedAccount->account_id);
                            
                            // Usar el método mejorado para obtener la jerarquía completa con page_id e instagram_id
                            $campaigns = $service->getCampaignsWithAdsHierarchy();
                            
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
                            $updatedWithInsightsCount = 0;
                            $stateMap = [];
                            
                            // Log para debug
                            Log::info('Iniciando importación de campañas', [
                                'cuenta' => $selectedAccount->name, 
                                'total_campañas' => count($campaigns)
                            ]);
                            
                            // Función auxiliar para mapear estados - definida dentro del closure
                            $mapFacebookStatus = function($facebookStatus) {
                                $status = strtoupper(trim($facebookStatus));
                                
                                switch ($status) {
                                    case 'ACTIVE':
                                        return 'activa';
                                    case 'PAUSED':
                                        return 'pausada';
                                    case 'ARCHIVED':
                                    case 'COMPLETED':
                                        return 'completada';
                                    case 'DELETED':
                                        return 'eliminada';
                                    case 'DISAPPROVED':
                                        return 'rechazada';
                                    case 'WITH_ISSUES':
                                        return 'con problemas';
                                    default:
                                        Log::warning("Estado de Facebook desconocido: $facebookStatus", [
                                            'mapped_to' => 'inactiva'
                                        ]);
                                        return 'inactiva';
                                }
                            };
                            
                            foreach ($campaigns as $campaignData) {
                                // Usar la función auxiliar definida arriba
                                $status = $mapFacebookStatus($campaignData['status'] ?? 'ACTIVE');
                                
                                // Procesar fechas
                                $startDate = !empty($campaignData['start_time']) 
                                    ? date('Y-m-d', strtotime($campaignData['start_time'])) 
                                    : now()->format('Y-m-d');
                                    
                                $endDate = !empty($campaignData['stop_time']) 
                                    ? date('Y-m-d', strtotime($campaignData['stop_time'])) 
                                    : now()->addDays(30)->format('Y-m-d');
                                
                                // Compilar toda la información para meta_insights
                                $metaInsights = [
                                    'page_id' => $campaignData['page_id'] ?? null,
                                    'page_name' => $campaignData['page_name'] ?? null,
                                    'page_link' => $campaignData['page_link'] ?? null,
                                    'instagram_account_id' => $campaignData['instagram_account_id'] ?? null,
                                    'instagram_username' => $campaignData['instagram_username'] ?? null,
                                    'raw_status' => $campaignData['status'] ?? null, // Guardar el estado original
                                    'delivery_info' => $campaignData['effective_status'] ?? $campaignData['status'] ?? null, // Guardar información de entrega
                                ];
                                
                                // Crear o actualizar la campaña
                                $campaign = AdsCampaign::updateOrCreate(
                                    ['meta_campaign_id' => $campaignData['id']],
                                    [
                                        'name' => $campaignData['name'],
                                        'client_id' => $defaultClient->id,
                                        'plan_id' => $defaultPlan->id,
                                        'advertising_account_id' => $selectedAccount->id,
                                        'status' => $status,
                                        'budget' => 0,                   // Valor predeterminado
                                        'actual_cost' => 0,              // Valor predeterminado
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                        'meta_insights' => $metaInsights, // Guardar toda la información recolectada
                                    ]
                                );
                                
                                // Limpiar caché para esta campaña
                                Cache::forget("campaign_fanpage_{$campaign->id}");
                                
                                // Registrar los estados para información
                                if (!isset($stateMap[$status])) {
                                    $stateMap[$status] = 0;
                                }
                                $stateMap[$status]++;
                                
                                $importedCount++;
                                
                                if (!empty($metaInsights['page_id']) || !empty($metaInsights['instagram_account_id'])) {
                                    $updatedWithInsightsCount++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Campañas sincronizadas")
                                ->body("{$importedCount} campañas en total, {$updatedWithInsightsCount} con información de página/Instagram. Estados: " . json_encode($stateMap))
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
               
                Tables\Columns\TextColumn::make('fanpage')
                    ->label('Fanpage/Instagram')
                    ->getStateUsing(function (AdsCampaign $record) {
                        // Cache key para evitar consultas repetidas
                        $cacheKey = "campaign_fanpage_{$record->id}";
                        
                        if (Cache::has($cacheKey)) {
                            return Cache::get($cacheKey);
                        }
                        
                        // Usar meta_insights si está disponible
                        if (!empty($record->meta_insights)) {
                            $hasFacebook = !empty($record->meta_insights['page_id']);
                            $hasInstagram = !empty($record->meta_insights['instagram_account_id']);
                            
                            // Caso 1: Tenemos tanto Facebook como Instagram
                            if ($hasFacebook && $hasInstagram) {
                                $pageId = $record->meta_insights['page_id'] ?? 'Sin ID';
                                $pageName = $record->meta_insights['page_name'] ?? 'Sin nombre';
                                
                                $igId = $record->meta_insights['instagram_account_id'] ?? 'Sin ID';
                                $igUsername = $record->meta_insights['instagram_username'] ?? 'Sin nombre';
                                
                                $result = new HtmlString(
                                    "<strong>[FB]</strong> {$pageName}<br>".
                                    "<span class='text-xs text-gray-500'>ID: {$pageId}</span><br>".
                                    "<strong>[IG]</strong> {$igUsername}<br>".
                                    "<span class='text-xs text-gray-500'>ID: {$igId}</span>"
                                );
                                
                                Cache::put($cacheKey, $result, now()->addHours(24));
                                return $result;
                            }
                            
                            // Caso 2: Solo Facebook
                            if ($hasFacebook) {
                                $pageId = $record->meta_insights['page_id'];
                                $pageName = $record->meta_insights['page_name'] ?? 'Sin nombre';
                                
                                $result = new HtmlString(
                                    "<strong>[FB]</strong> {$pageName}<br>".
                                    "<span class='text-xs text-gray-500'>ID: {$pageId}</span>"
                                );
                                
                                Cache::put($cacheKey, $result, now()->addHours(24));
                                return $result;
                            }
                            
                            // Caso 3: Solo Instagram
                            if ($hasInstagram) {
                                $igId = $record->meta_insights['instagram_account_id'];
                                $igUsername = $record->meta_insights['instagram_username'] ?? 'Sin nombre';
                                
                                $result = new HtmlString(
                                    "<strong>[IG]</strong> {$igUsername}<br>".
                                    "<span class='text-xs text-gray-500'>ID: {$igId}</span>"
                                );
                                
                                Cache::put($cacheKey, $result, now()->addHours(24));
                                return $result;
                            }
                        }
                        
                        // Caso 4: No hay información de redes sociales
                        if ($record->client) {
                            $result = new HtmlString(
                                "Cliente: {$record->client->name}<br>".
                                "<span class='text-xs text-gray-500'>Sin cuentas asociadas</span>"
                            );
                            
                            Cache::put($cacheKey, $result, now()->addHours(1));
                            return $result;
                        }
                        
                        // Último recurso
                        $result = new HtmlString(
                            "Sin asignar<br>".
                            "<span class='text-xs text-gray-500'>Sin cuentas asociadas</span>"
                        );
                        
                        Cache::put($cacheKey, $result, now()->addHours(1));
                        return $result;
                    })
                    ->html()
                    ->searchable(false)
                    ->sortable(false)
                    ->wrap()
                    ->icon(function (AdsCampaign $record) {
                        if (!empty($record->meta_insights)) {
                            $hasFacebook = !empty($record->meta_insights['page_id']);
                            $hasInstagram = !empty($record->meta_insights['instagram_account_id']);
                            
                            // Si tenemos ambos, mostrar un icono diferente
                            if ($hasFacebook && $hasInstagram) {
                                return 'heroicon-o-rectangle-stack';
                            }
                            
                            if ($hasFacebook) {
                                return 'heroicon-o-globe-alt';
                            }
                            
                            if ($hasInstagram) {
                                return 'heroicon-o-camera';
                            }
                        }
                        
                        return 'heroicon-o-question-mark-circle';
                    })
                    ->iconPosition('before')
                    ->tooltip(function (AdsCampaign $record) {
                        $tooltipParts = [];
                        
                        if (!empty($record->meta_insights['page_id'])) {
                            $tooltipParts[] = "Facebook ID: " . $record->meta_insights['page_id'];
                            
                            if (!empty($record->meta_insights['page_link'])) {
                                $tooltipParts[] = "Link: " . $record->meta_insights['page_link'];
                            }
                        }
                        
                        if (!empty($record->meta_insights['instagram_account_id'])) {
                            $tooltipParts[] = "Instagram ID: " . $record->meta_insights['instagram_account_id'];
                        }
                        
                        return !empty($tooltipParts) ? implode("\n", $tooltipParts) : null;
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(function (string $state, AdsCampaign $record): string {
                        // Si tenemos información de entrega en meta_insights, mostrarla junto con el estado
                        if (!empty($record->meta_insights['delivery_info'])) {
                            $deliveryInfo = $record->meta_insights['delivery_info'];
                            return ucfirst($state) . ' (' . $deliveryInfo . ')';
                        }
                        
                        return ucfirst($state);
                    })
                    ->color(fn (string $state): string => 
                        match ($state) {
                            'active' => 'success',
                            'paused' => 'warning',
                            'completed' => 'info',
                            'deleted' => 'gray',
                            'rejected' => 'danger',
                            'issue' => 'danger',
                            default => 'gray',
                        }
                    )
                    ->tooltip(function (AdsCampaign $record): ?string {
                        if (!empty($record->meta_insights['raw_status'])) {
                            return "Estado original en Facebook: " . $record->meta_insights['raw_status'];
                        }
                        return null;
                    }),
                    
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
                
                Tables\Columns\TextColumn::make('adsets_count')
                    ->label('Conjuntos')
                    ->getStateUsing(function (AdsCampaign $record) {
                        // Usar caché para mejorar rendimiento
                        $cacheKey = "adsets_count_" . $record->meta_campaign_id;
                        return Cache::remember($cacheKey, now()->addMinutes(60), function() use ($record) {
                            if (empty($record->meta_campaign_id)) return 0;
                            
                            try {
                                // Buscar cuenta publicitaria para esta campaña
                                $accountId = $record->advertisingAccount?->account_id;
                                $service = new FacebookAdsService($accountId);
                                $adSets = $service->getAdSetsForCampaign($record->meta_campaign_id);
                                
                                return count($adSets);
                            } catch (\Exception $e) {
                                Log::error("Error obteniendo conteo de adsets: " . $e->getMessage());
                                return 0;
                            }
                        });
                    })
                    ->tooltip('Número de conjuntos de anuncios'),
                
                Tables\Columns\TextColumn::make('ads_count')
                    ->label('Anuncios')
                    ->getStateUsing(function (AdsCampaign $record) {
                        $cacheKey = "ads_count_" . $record->meta_campaign_id;
                        return Cache::remember($cacheKey, now()->addMinutes(60), function() use ($record) {
                            if (empty($record->meta_campaign_id)) return 0;
                            
                            try {
                                // Buscar cuenta publicitaria para esta campaña
                                $accountId = $record->advertisingAccount?->account_id;
                                $service = new FacebookAdsService($accountId);
                                
                                // Obtener todos los adsets de la campaña
                                $adSets = $service->getAdSetsForCampaign($record->meta_campaign_id);
                                
                                // Contar todos los anuncios en todos los adsets
                                $totalAds = 0;
                                foreach ($adSets as $adSet) {
                                    // Si el adset tiene un contador precalculado, usarlo
                                    if (isset($adSet['ads_count'])) {
                                        $totalAds += $adSet['ads_count'];
                                    } else {
                                        // Si no, hacer consulta para obtener los anuncios del adset
                                        $ads = $service->getAdsForAdSet($adSet['id']);
                                        $totalAds += count($ads);
                                    }
                                }
                                
                                return $totalAds;
                            } catch (\Exception $e) {
                                Log::error("Error obteniendo conteo de anuncios: " . $e->getMessage());
                                return 0;
                            }
                        });
                    })
                    ->tooltip('Número total de anuncios'),
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
                        
                        return "https://adsmanager.facebook.com/adsmanager/manage/campaigns?act={$accountId}&selected_campaign_ids={$record->meta_campaign_id}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (AdsCampaign $record) => !empty($record->meta_campaign_id)),
                
                Tables\Actions\Action::make('viewAdSets')
                    ->label('Ver Conjuntos')
                    ->icon('heroicon-o-squares-2x2')
                    ->modalHeading(fn ($record) => "{$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false )
                    ->modalContent(function ($record) {
                        // Cargar AdSets directamente de la API al abrir el modal
                        $accountId = $record->advertisingAccount?->account_id;
                        $service = new FacebookAdsService($accountId);
                        $adSets = $service->getAdSetsForCampaign($record->meta_campaign_id);
                        
                        return view('filament.resources.ads-campaign-resource.components.adsets-list', [
                            'campaign' => $record,
                            'adSets' => $adSets,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkSyncAdSets')
                    ->label('Sincronizar Conjuntos')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        // Implementar sincronización masiva
                    })
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

    /**
     * Mapear estados de Facebook a estados de la aplicación
     */
    private function mapFacebookStatus($facebookStatus)
    {
        $status = strtoupper(trim($facebookStatus));
        
        switch ($status) {
            case 'ACTIVE':
                return 'active';
            case 'PAUSED':
                return 'paused';
            case 'ARCHIVED':
            case 'COMPLETED':
                return 'completed';
            case 'DELETED':
                return 'deleted';
            case 'DISAPPROVED':
                return 'rejected';
            case 'WITH_ISSUES':
                return 'issue';
            default:
                Log::warning("Estado de Facebook desconocido: $facebookStatus", [
                    'mapped_to' => 'inactive'
                ]);
                return 'inactive';
        }
    }
}

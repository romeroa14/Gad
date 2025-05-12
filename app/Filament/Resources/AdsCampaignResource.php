<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdsCampaignResource\Pages;
use App\Filament\Resources\AdsCampaignResource\RelationManagers;
use App\Models\AdsCampaign;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
// use App\Services\MetaAdsService;
use App\Services\FacebookAds\FacebookAdsService;
use App\Filament\Resources\ClientResource\RelationManagers\FanpageRelationManager;
use FFI;

class AdsCampaignResource extends Resource
{
    protected static ?string $model = AdsCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Campañas Publicitarias';
    protected static ?string $pluralModelLabel = 'Campañas Publicitarias';
    protected static ?int $navigationSort = 2;

    public static function getAdvertisingAccounts()
    {
        $metaAdsService = new FacebookAdsService();
        $accounts = $metaAdsService->getAdvertisingAccounts() ?? [];
        
        return $accounts;
    }
    
    public static function getSelectedAdvertisingAccount()
    {
        $selectedAccountId = session('selected_advertising_account_id');
        if (!$selectedAccountId) {
            return null;
        }
        
        // Verifica si el ID comienza con 'act_' (formato de Facebook)
        if (is_string($selectedAccountId) && str_starts_with($selectedAccountId, 'act_')) {
            // Buscar por account_id en lugar de id
            return AdvertisingAccount::where('account_id', $selectedAccountId)->first();
        }
        
        // Si no comienza con 'act_', asumimos que es un ID de la base de datos
        return AdvertisingAccount::find($selectedAccountId);
    }

    public static function form(Form $form): Form
    {
        $selectedAccount = self::getSelectedAdvertisingAccount();
        
        return $form
            ->schema([
                Forms\Components\Section::make('Cuenta Publicitaria')
                    ->schema([
                        Forms\Components\Select::make('advertising_account_id')
                            ->label('Seleccionar cuenta publicitaria')
                            ->options(function () {
                                $accounts = self::getAdvertisingAccounts();
                                
                                $options = [];
                                foreach ($accounts as $account) {
                                    $options[$account['id']] = "{$account['name']} ({$account['account_id']})";
                                }
                                
                                return $options;
                            })
                            ->default(session('selected_advertising_account_id'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                session(['selected_advertising_account_id' => $state]);
                            })
                            ->helperText('Esta selección afectará globalmente a toda la aplicación'),
                            
                        Forms\Components\Placeholder::make('account_info')
                            ->label('Información de la cuenta')
                            ->content(function () use ($selectedAccount) {
                                if (!$selectedAccount) {
                                    return 'No hay una cuenta publicitaria seleccionada';
                                }
                                
                                $statusLabels = [
                                    1 => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>',
                                    2 => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Deshabilitado</span>',
                                    0 => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Inactivo</span>',
                                ];
                                
                                $statusHtml = $statusLabels[$selectedAccount->status] ?? 'Desconocido';
                                
                                return new HtmlString(
                                    "<div class='space-y-1'>
                                        <div class='font-medium'>{$selectedAccount->name}</div>
                                        <div class='text-sm text-gray-600'>ID: {$selectedAccount->account_id}</div>
                                        <div class='text-sm text-gray-600'>Moneda: {$selectedAccount->currency}</div>
                                        <div class='text-sm text-gray-600'>Estado: {$statusHtml}</div>
                                        <div class='text-sm text-gray-600'>Zona horaria: {$selectedAccount->timezone}</div>
                                        <div class='mt-2'>
                                            <a href='https://www.facebook.com/adsmanager/manage/campaigns?act={$selectedAccount->account_id}' 
                                               target='_blank'
                                               class='text-sm text-primary-600 hover:text-primary-800 inline-flex items-center'>
                                                <svg class='w-3.5 h-3.5 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'></path>
                                                </svg>
                                                Ver en Ads Manager
                                            </a>
                                        </div>
                                    </div>"
                                );
                            }),
                    ])
                    ->collapsible(),
                
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Campaña')
                    ->required(),
                
                Forms\Components\Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('plan')
                    ->label('Plan')
                    ->options([
                        'basic' => 'Básico',
                        'premium' => 'Premium',
                        'enterprise' => 'Empresarial'
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->required(),

                Forms\Components\TextInput::make('budget')
                    ->label('Presupuesto')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('meta_campaign_id')
                    ->label('ID de Campaña en Meta')
                    ->helperText('Opcional - Para sincronización con Meta Ads')
                    ->rules(['nullable', function($attribute, $value, $fail) use ($selectedAccount) {
                        if ($value && !(new MetaAdsService())->validateCampaignExists($value, $selectedAccount?->account_id)) {
                            $fail('La campaña no existe en Meta Ads o no pertenece a la cuenta seleccionada');
                        }
                    }]),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'completed' => 'Completada'
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $selectedAccount = self::getSelectedAdvertisingAccount();
        $accountInfoHtml = '';
        
        if ($selectedAccount) {
            $accountInfoHtml = "
                <div class='mb-4 p-4 bg-white rounded-lg shadow'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <h3 class='text-lg font-medium'>Cuenta: {$selectedAccount->name}</h3>
                            <p class='text-sm text-gray-600'>ID: {$selectedAccount->account_id}</p>
                        </div>
                        <a href='https://www.facebook.com/adsmanager/manage/campaigns?act={$selectedAccount->account_id}' 
                           target='_blank'
                           class='text-sm text-primary-600 hover:text-primary-800 inline-flex items-center'>
                            Ver en Ads Manager
                        </a>
                    </div>
                </div>
            ";
        }
        
        return $table
            ->headerActions([
                Tables\Actions\Action::make('cambiarCuenta')
                    ->label('Cambiar Cuenta Publicitaria')
                    ->icon('heroicon-o-credit-card')
                    ->modalHeading('Seleccionar Cuenta Publicitaria')
                    ->modalSubmitActionLabel('Seleccionar Cuenta')
                    ->form([
                        Forms\Components\Select::make('advertising_account_id')
                            ->label('Cuenta Publicitaria')
                            ->options(function () {
                                $accounts = self::getAdvertisingAccounts();
                                $options = [];
                                foreach ($accounts as $account) {
                                    $options[$account['id']] = "{$account['name']} ({$account['account_id']})";
                                }
                                return $options;
                            })
                            ->default(session('selected_advertising_account_id'))
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $accountData = collect(self::getAdvertisingAccounts())
                            ->firstWhere('id', $data['advertising_account_id']);
                            
                        if ($accountData) {
                            // Si la cuenta no existe en la DB, crearla primero
                            if (!$accountData['id']) {
                                $dbAccount = AdvertisingAccount::updateOrCreate(
                                    ['account_id' => $accountData['account_id']],
                                    [
                                        'name' => $accountData['name'],
                                        'status' => $accountData['status'],
                                        'currency' => $accountData['currency'],
                                        'timezone' => $accountData['timezone'],
                                    ]
                                );
                                session(['selected_advertising_account_id' => $dbAccount->id]);
                            } else {
                                session(['selected_advertising_account_id' => $accountData['id']]);
                            }
                            
                            session(['selected_advertising_account_fb_id' => $accountData['account_id']]);
                        }
                        
                        redirect(request()->header('Referer'));
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre'),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente'),
                Tables\Columns\TextColumn::make('plan.daily_investment')->label('Plan'),
                Tables\Columns\TextColumn::make('start_date')->label('Inicio'),
                Tables\Columns\TextColumn::make('end_date')->label('Fin'),
                Tables\Columns\TextColumn::make('budget')->label('Presupuesto')->money('usd', true),
                Tables\Columns\TextColumn::make('actual_cost')->label('Costo Real')->money('usd', true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Activa',
                        'Paused' => 'Pausada',
                        'Finished' => 'Finalizada',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // FanpageRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdsCampaigns::route('/'),
            'create' => Pages\CreateAdsCampaign::route('/create'),
            'edit' => Pages\EditAdsCampaign::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\FacebookPage;
use App\Models\AdvertisingAccount;
use App\Models\InstagramAccount;
use App\Models\FacebookAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        // Obtenemos la cuenta publicitaria seleccionada de la sesión
        $selectedAccountId = session('selected_advertising_account_id');
        $advertisingAccount = null;

        if ($selectedAccountId) {
            $advertisingAccount = AdvertisingAccount::find($selectedAccountId);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->required()
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('business')
                            ->label('Negocio')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Ubicación')
                    ->schema([
                        Forms\Components\Select::make('country_id')
                            ->label('País')
                            ->required()
                            ->searchable()
                            ->options(Country::pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('state_id', null)),

                        Forms\Components\Select::make('state_id')
                            ->label('Estado/Provincia')
                            ->required()
                            ->searchable()
                            ->options(function (callable $get) {
                                $country = $get('country_id');
                                if (!$country) {
                                    return [];
                                }
                                return State::where('country_id', $country)->pluck('name', 'id');
                            })
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('city_id', null)),

                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                // Nueva sección para Facebook
                Forms\Components\Section::make('Facebook')
                    ->schema([
                        Forms\Components\Placeholder::make('active_account')
                            ->label('Cuenta Publicitaria Activa')
                            ->content(function () use ($advertisingAccount) {
                                if ($advertisingAccount) {
                                    return $advertisingAccount->name . ' (' . $advertisingAccount->account_id . ')';
                                }
                                return 'Ninguna cuenta seleccionada';
                            })
                            ->visible(fn() => $advertisingAccount !== null),

                        Forms\Components\Select::make('facebook_page_id')
                            ->label('Fanpage de Facebook')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                // Añadimos logs extensivos para debugging
                                \Illuminate\Support\Facades\Log::info('Iniciando carga de fanpages desde API');

                                // Obtener la cuenta de Facebook activa y su token
                                $facebookToken = config('services.facebook.access_token');

                                // Usar directamente el token del .env (o configuración)
                                \Illuminate\Support\Facades\Log::info('Token disponible: ' . ($facebookToken ? 'Sí' : 'No'));

                                if (empty($facebookToken)) {
                                    \Illuminate\Support\Facades\Log::error('Token de Facebook no disponible');
                                    return [];
                                }

                                try {
                                    \Illuminate\Support\Facades\Log::info('Realizando petición a API de Facebook');

                                    // Petición directa a la API utilizando el token del .env
                                    $response = \Illuminate\Support\Facades\Http::get('https://graph.facebook.com/v18.0/me/accounts', [
                                        'access_token' => $facebookToken,
                                        'fields' => 'id,name,category,picture'
                                    ]);

                                    // Log completo de la respuesta para debugging
                                    \Illuminate\Support\Facades\Log::info('Respuesta de API', [
                                        'status' => $response->status(),
                                        'body' => $response->body()
                                    ]);

                                    if ($response->successful()) {
                                        $pages = $response->json('data', []);

                                        \Illuminate\Support\Facades\Log::info('Páginas recuperadas: ' . count($pages));

                                        // Convertir a formato clave-valor para el select
                                        $pageOptions = [];
                                        foreach ($pages as $page) {
                                            // Log detallado de cada página
                                            \Illuminate\Support\Facades\Log::info('Página encontrada', [
                                                'id' => $page['id'] ?? 'N/A',
                                                'name' => $page['name'] ?? 'N/A'
                                            ]);

                                            $pageOptions[$page['id']] = $page['name'];
                                        }

                                        return $pageOptions;
                                    }

                                    \Illuminate\Support\Facades\Log::error('Error al extraer páginas de Facebook', [
                                        'status' => $response->status(),
                                        'response' => $response->json()
                                    ]);

                                    return [];
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Excepción al extraer páginas de Facebook', [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                    return [];
                                }
                            })
                            ->helperText('Selecciona una página de Facebook para asociarla con este cliente')
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                // Reinicia la selección de Instagram si cambia la página
                                $set('instagram_account_id', null);

                                // Podríamos cargar cuentas de Instagram, pero lo simplificamos por ahora
                            }),

                        Forms\Components\Select::make('instagram_account_id')
                            ->label('Cuenta de Instagram')
                            ->searchable()
                            ->preload()
                            ->options(function ($get) {
                                $pageId = $get('facebook_page_id');

                                \Illuminate\Support\Facades\Log::info('Cargando cuentas de Instagram para page_id: ' . $pageId);

                                if (empty($pageId)) {
                                    return [];
                                }

                                // Obtener token de acceso directamente de la configuración
                                $facebookToken = config('services.facebook.access_token');

                                if (empty($facebookToken)) {
                                    \Illuminate\Support\Facades\Log::error('Token de Facebook no disponible para Instagram');
                                    return [];
                                }

                                try {
                                    \Illuminate\Support\Facades\Log::info('Obteniendo página de Facebook');

                                    // Primero obtenemos el token específico de la página
                                    $pageResponse = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v18.0/{$pageId}", [
                                        'fields' => 'access_token,instagram_business_account',
                                        'access_token' => $facebookToken
                                    ]);

                                    \Illuminate\Support\Facades\Log::info('Respuesta token de página', [
                                        'status' => $pageResponse->status(),
                                        'body' => $pageResponse->body()
                                    ]);

                                    if (!$pageResponse->successful()) {
                                        \Illuminate\Support\Facades\Log::error('Error al obtener token de página');
                                        return [];
                                    }

                                    // Extraer token de página
                                    $pageToken = $pageResponse->json('access_token');
                                    $instagramBusinessAccountId = $pageResponse->json('instagram_business_account.id');

                                    \Illuminate\Support\Facades\Log::info('Datos obtenidos', [
                                        'page_token' => $pageToken ? 'Disponible' : 'No disponible',
                                        'instagram_business_account' => $instagramBusinessAccountId
                                    ]);

                                    // Si tenemos el ID de Instagram Business directamente de la página
                                    if ($instagramBusinessAccountId) {
                                        // Obtener detalles de esta cuenta
                                        $igResponse = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v18.0/{$instagramBusinessAccountId}", [
                                            'fields' => 'username,name,profile_picture_url',
                                            'access_token' => $pageToken ?? $facebookToken
                                        ]);
                                        
                                        \Illuminate\Support\Facades\Log::info('Respuesta de cuenta Instagram Business', [
                                            'status' => $igResponse->status(),
                                            'body' => $igResponse->body()
                                        ]);
                                        
                                        if ($igResponse->successful()) {
                                            $igDetails = $igResponse->json();
                                            $instagramOptions = [
                                                $instagramBusinessAccountId => $igDetails['username'] ?? $igDetails['name'] ?? 'Cuenta de Instagram'
                                            ];
                                            return $instagramOptions;
                                        }
                                    }

                                    // Enfoque alternativo: obtener cuentas vinculadas
                                    $response = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v18.0/{$pageId}/instagram_accounts", [
                                        'access_token' => $pageToken ?? $facebookToken
                                    ]);

                                    \Illuminate\Support\Facades\Log::info('Respuesta de Instagram', [
                                        'status' => $response->status(),
                                        'body' => $response->body()
                                    ]);

                                    if ($response->successful()) {
                                        $accounts = $response->json('data', []);

                                        \Illuminate\Support\Facades\Log::info('Cuentas de Instagram encontradas: ' . count($accounts));

                                        // Convertir a formato para el select
                                        $instagramOptions = [];
                                        foreach ($accounts as $account) {
                                            \Illuminate\Support\Facades\Log::info('Cuenta IG encontrada', [
                                                'id' => $account['id'] ?? 'N/A',
                                                'username' => $account['username'] ?? 'N/A'
                                            ]);

                                            $instagramOptions[$account['id']] = $account['username'] ?? 'Cuenta sin nombre';
                                        }

                                        return $instagramOptions;
                                    }

                                    \Illuminate\Support\Facades\Log::error('Error al obtener cuentas de Instagram', [
                                        'status' => $response->status(),
                                        'response' => $response->json()
                                    ]);

                                    return [];
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Excepción al obtener cuentas de Instagram', [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                    return [];
                                }
                            })
                            ->helperText('Selecciona la cuenta de Instagram asociada a la página de Facebook'),

                    ])
                    ->visible(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('facebook_page_id')
                    ->label('Fanpage de Facebook')
                    ->formatStateUsing(function ($state, $record) {
                        // Si no hay page_id, mostrar placeholder
                        if (empty($state)) {
                            return '-';
                        }
                        
                        // Intentar obtener del caché primero
                        $cacheKey = "facebook_page_{$state}";
                        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                            return \Illuminate\Support\Facades\Cache::get($cacheKey);
                        }
                        
                        try {
                            // Obtener token de Facebook desde la configuración
                            $facebookToken = config('services.facebook.access_token');
                            
                            if (empty($facebookToken)) {
                                return "ID: {$state}";
                            }
                            
                            // Hacer llamada a la API de Facebook para obtener detalles de la página
                            $response = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v18.0/{$state}", [
                                'fields' => 'name,category',
                                'access_token' => $facebookToken
                            ]);
                            
                            if ($response->successful()) {
                                $pageData = $response->json();
                                $pageName = $pageData['name'] ?? "ID: {$state}";
                                
                                // Guardar en caché por 24 horas
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $pageName, now()->addHours(24));
                                
                                return $pageName;
                            }
                            
                            return "ID: {$state}";
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error al obtener datos de página Facebook', [
                                'page_id' => $state,
                                'error' => $e->getMessage()
                            ]);
                            
                            return "ID: {$state}";
                        }
                    }),

                Tables\Columns\TextColumn::make('instagram_account_id')
                    ->label('Cuenta de Instagram')
                    ->formatStateUsing(function ($state, $record) {
                        // Si no hay account_id, mostrar placeholder
                        if (empty($state)) {
                            return '-';
                        }
                        
                        // Intentar obtener del caché primero
                        $cacheKey = "instagram_account_{$state}";
                        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                            return \Illuminate\Support\Facades\Cache::get($cacheKey);
                        }
                        
                        try {
                            // Obtener token de Facebook desde la configuración
                            $facebookToken = config('services.facebook.access_token');
                            
                            if (empty($facebookToken)) {
                                return "ID: {$state}";
                            }
                            
                            // Hacer llamada a la API de Facebook para obtener detalles de la cuenta IG
                            $response = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v18.0/{$state}", [
                                'fields' => 'username,name',
                                'access_token' => $facebookToken
                            ]);
                            
                            if ($response->successful()) {
                                $accountData = $response->json();
                                $accountName = $accountData['username'] ?? $accountData['name'] ?? "ID: {$state}";
                                
                                // Guardar en caché por 24 horas
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $accountName, now()->addHours(24));
                                
                                return $accountName;
                            }
                            
                            return "ID: {$state}";
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error al obtener datos de cuenta Instagram', [
                                'account_id' => $state,
                                'error' => $e->getMessage()
                            ]);
                            
                            return "ID: {$state}";
                        }
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),

                Tables\Columns\TextColumn::make('business')
                    ->label('Negocio'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country_id')
                    ->label('País')
                    ->options(Country::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

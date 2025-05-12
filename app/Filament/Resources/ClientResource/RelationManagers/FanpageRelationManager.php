<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AdvertisingAccount;

class FanpageRelationManager extends RelationManager
{
    protected static string $relationship = 'fanpages'; // Relación definida en el modelo Client

    protected static ?string $recordTitleAttribute = 'facebook_page_id';

    public function form(Form $form): Form
    {
        // Obtenemos la cuenta publicitaria seleccionada de la sesión
        $selectedAccountId = session('selected_advertising_account_id');
        $advertisingAccount = null;

        if ($selectedAccountId) {
            $advertisingAccount = AdvertisingAccount::find($selectedAccountId);
        }
        return $form
            ->schema([
                Select::make('facebook_page_id')
                    ->label('Fanpage de Facebook')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $facebookToken = config('services.facebook.access_token');

                        if (empty($facebookToken)) {
                            Log::error('Token de Facebook no disponible');
                            return [];
                        }

                        try {
                            $response = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
                                'access_token' => $facebookToken,
                                'fields' => 'id,name,category,picture',
                                'limit' => 200,
                            ]);

                            if ($response->successful()) {
                                $pages = $response->json('data', []);
                                return collect($pages)->pluck('name', 'id')->toArray();
                            }

                            Log::error('Error al obtener fanpages', ['response' => $response->json()]);
                            return [];
                        } catch (\Exception $e) {
                            Log::error('Excepción al obtener fanpages', ['error' => $e->getMessage()]);
                            return [];
                        }
                    })
                    ->required(),

                Select::make('instagram_account_id')
                    ->label('Cuenta de Instagram')
                    ->searchable()
                    ->preload()
                    ->options(function ($get) {
                        $pageId = $get('facebook_page_id');
                        $facebookToken = config('services.facebook.access_token');

                        if (empty($pageId) || empty($facebookToken)) {
                            return [];
                        }

                        try {
                            $response = Http::get("https://graph.facebook.com/v18.0/{$pageId}", [
                                'fields' => 'instagram_business_account',
                                'access_token' => $facebookToken,
                            ]);

                            $instagramAccountId = $response->json('instagram_business_account.id');

                            if ($instagramAccountId) {
                                $igResponse = Http::get("https://graph.facebook.com/v18.0/{$instagramAccountId}", [
                                    'fields' => 'username',
                                    'access_token' => $facebookToken,
                                ]);

                                if ($igResponse->successful()) {
                                    $igData = $igResponse->json();
                                    return [$instagramAccountId => $igData['username']];
                                }
                            }

                            return [];
                        } catch (\Exception $e) {
                            Log::error('Excepción al obtener cuentas de Instagram', ['error' => $e->getMessage()]);
                            return [];
                        }
                    })
                    ->helperText('Selecciona la cuenta de Instagram asociada a la fanpage'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('facebook_page_id')
                    ->label('Fanpage')
                    ->formatStateUsing(function ($state) {
                        // Si no hay page_id, mostrar placeholder
                        if (empty($state)) {
                            return '-';
                        }
                        
                        // Intentar obtener del caché primero
                        $cacheKey = "facebook_page_{$state}";
                        if (Cache::has($cacheKey)) {
                            return Cache::get($cacheKey);
                        }
                        
                        try {
                            // Obtener token de Facebook desde la configuración
                            $facebookToken = config('services.facebook.access_token');
                            
                            if (empty($facebookToken)) {
                                return "ID: {$state}";
                            }
                            
                            // Hacer llamada a la API de Facebook para obtener detalles de la página
                            $response = Http::get("https://graph.facebook.com/v18.0/{$state}", [
                                'fields' => 'name,category',
                                'access_token' => $facebookToken
                            ]);
                            
                            if ($response->successful()) {
                                $pageData = $response->json();
                                $pageName = $pageData['name'] ?? "ID: {$state}";
                                
                                // Guardar en caché por 24 horas
                                Cache::put($cacheKey, $pageName, now()->addHours(24));
                                
                                return $pageName;
                            }
                            
                            return "ID: {$state}";
                        } catch (\Exception $e) {
                            Log::error('Error al obtener datos de página Facebook', [
                                'page_id' => $state,
                                'error' => $e->getMessage()
                            ]);
                            
                            return "ID: {$state}";
                        }
                    })
                    ->url(function ($record) {
                        if (!empty($record->facebook_page_id)) {
                            return "https://facebook.com/{$record->facebook_page_id}";
                        }
                        return null;
                    }, true),

                Tables\Columns\TextColumn::make('instagram_account_id')
                    ->label('Cuenta de Instagram')
                    ->formatStateUsing(function ($state, $record) {
                        // Si no hay account_id, mostrar placeholder
                        if (empty($state)) {
                            return '-';
                        }
                        
                        // Intentar obtener del caché primero
                        $cacheKey = "instagram_account_{$state}";
                        if (Cache::has($cacheKey)) {
                            return Cache::get($cacheKey);
                        }
                        
                        try {
                            // Obtener token de Facebook desde la configuración
                            $facebookToken = config('services.facebook.access_token');
                            
                            if (empty($facebookToken)) {
                                return "ID: {$state}";
                            }
                            
                            // Hacer llamada a la API de Facebook para obtener detalles de la cuenta IG
                            $response = Http::get("https://graph.facebook.com/v18.0/{$state}", [
                                'fields' => 'username,name',
                                'access_token' => $facebookToken
                            ]);
                            
                            if ($response->successful()) {
                                $accountData = $response->json();
                                
                                // Debug para ver qué está devolviendo la API
                                Log::info('Datos de Instagram obtenidos', [
                                    'account_id' => $state,
                                    'data' => $accountData
                                ]);
                                
                                $accountName = $accountData['username'] ?? $accountData['name'] ?? "ID: {$state}";
                                
                                // Guardar en caché por 24 horas
                                Cache::put($cacheKey, $accountName, now()->addHours(24));
                                
                                return $accountName;
                            }
                            
                            return "ID: {$state}";
                        } catch (\Exception $e) {
                            Log::error('Error al obtener datos de cuenta Instagram', [
                                'account_id' => $state,
                                'error' => $e->getMessage()
                            ]);
                            
                            return "ID: {$state}";
                        }
                    })
                    ->url(function ($record) {
                        if (empty($record->instagram_account_id)) {
                            return null;
                        }
                        
                        try {
                            // Intentar obtener username directamente de la API
                            $facebookToken = config('services.facebook.access_token');
                            if (empty($facebookToken)) {
                                return null;
                            }
                            
                            // Intentar obtener del caché primero
                            $cacheKey = "instagram_account_{$record->instagram_account_id}";
                            if (Cache::has($cacheKey)) {
                                $username = Cache::get($cacheKey);
                                // Si es un username válido (no un ID)
                                if ($username && !str_starts_with($username, "ID:")) {
                                    return "https://instagram.com/{$username}";
                                }
                            }
                            
                            // Si no está en caché, hacer la llamada a la API
                            $response = Http::get("https://graph.facebook.com/v18.0/{$record->instagram_account_id}", [
                                'fields' => 'username',
                                'access_token' => $facebookToken
                            ]);
                            
                            if ($response->successful()) {
                                $data = $response->json();
                                if (isset($data['username'])) {
                                    // Guardar en caché para futuras referencias
                                    Cache::put($cacheKey, $data['username'], now()->addHours(24));
                                    return "https://instagram.com/{$data['username']}";
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error al construir URL de Instagram', [
                                'instagram_id' => $record->instagram_account_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        return null;
                    }, true),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 
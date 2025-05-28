<?php

namespace App\Filament\Widgets;

use App\Models\AdvertisingAccount;
use App\Models\FacebookAccount;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use App\Services\FacebookAds\FacebookAdsService;

class AdvertisingAccountsWidget extends Widget
{
    protected function getTableQuery(): Builder
    {
        // Obtener el ID de la cuenta de Facebook de la sesión
        $facebookAccountId = session('facebook_account_id');
        
        // Si hay una cuenta de Facebook en la sesión
        if ($facebookAccountId) {
            return AdvertisingAccount::query()
                ->where('facebook_account_id', $facebookAccountId);
        }
        
        // Si no hay sesión, intenta encontrar cualquier cuenta de Facebook activa
        $latestFacebookAccount = FacebookAccount::latest()->first();
        
        if ($latestFacebookAccount) {
            // Actualiza la sesión para uso futuro
            session(['facebook_account_id' => $latestFacebookAccount->id]);
            
            return AdvertisingAccount::query()
                ->where('facebook_account_id', $latestFacebookAccount->id);
        }
        
        // Si no hay ninguna cuenta de Facebook, devuelve una consulta vacía
        return AdvertisingAccount::query()->whereRaw('1 = 0');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable(),
            Tables\Columns\TextColumn::make('account_id')
                ->label('ID de Cuenta')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('currency')
                ->label('Moneda'),
            Tables\Columns\TextColumn::make('timezone')
                ->label('Zona Horaria'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Creada')
                ->dateTime('d/m/Y H:i'),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    protected static string $view = 'filament.widgets.advertising-accounts-widget';
    
    protected int | string | array $columnSpan = 'full';

    // Propiedad para guardar la cuenta seleccionada
    public $selectedAccountId = null;
    
    // Método para cargar datos iniciales
    public function mount()
    {
        // Cargar la cuenta seleccionada de la sesión al iniciar
        $this->selectedAccountId = session('selected_advertising_account_id');
    }

    /**
     * Método para seleccionar una cuenta publicitaria
     */
    public function selectAccount($accountId)
    {
        try {
            // Verificar qué tipo de ID estamos recibiendo
            if (is_string($accountId) && str_starts_with($accountId, 'act_')) {
                // Es un account_id de Facebook, buscar el registro correspondiente
                $account = AdvertisingAccount::where('account_id', $accountId)->first();
                
                if (!$account) {
                    // Si no existe, crearlo
                    // (Implementación depende de tus datos disponibles)
                    throw new \Exception("La cuenta publicitaria con ID {$accountId} no existe en la base de datos");
                }
                
                // Guardar el ID de la base de datos en la sesión
                session(['selected_advertising_account_id' => $account->id]);
                // Guardar también el account_id de Facebook por si es necesario
                session(['selected_advertising_account_fb_id' => $accountId]);
                
                // Actualizar la propiedad local con el ID de la base de datos
                $this->selectedAccountId = $account->id;
            } else {
                // Es un ID de la base de datos
                $account = AdvertisingAccount::findOrFail($accountId);
                
                // Guardar en sesión
                session(['selected_advertising_account_id' => $accountId]);
                session(['selected_advertising_account_fb_id' => $account->account_id]);
                
                $this->selectedAccountId = $accountId;
            }
            
            // Notificar al usuario
            Notification::make()
                ->title('Cuenta seleccionada')
                ->body("Ahora estás trabajando con la cuenta: {$account->name}. Sincronizando datos...")
                ->success()
                ->send();
            
            // Sincronizar jerarquía completa en segundo plano
            try {
                // Truncar datos antiguos
                \App\Models\Ad::truncate();
                \App\Models\AdsSet::truncate();
                \App\Models\AdsCampaign::truncate();
                
                $service = new FacebookAdsService($account->account_id);
                $result = $service->syncCompleteHierarchy();
                
                Notification::make()
                    ->title('Sincronización completada')
                    ->body("Datos actualizados: {$result['campaigns']} campañas, {$result['adsets']} adsets, {$result['ads']} ads")
                    ->success()
                    ->send();
                
            } catch (\Exception $e) {
                Log::error('Error en sincronización automática: ' . $e->getMessage());
                
                Notification::make()
                    ->title('Advertencia')
                    ->body('Cuenta seleccionada, pero hubo un error en la sincronización automática. Puedes sincronizar manualmente.')
                    ->warning()
                    ->send();
            }
            
            // Registrar esta acción
            Log::info('Cuenta publicitaria seleccionada', [
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'account_name' => $account->name,
                'facebook_account_id' => $account->account_id
            ]);
            
            $this->dispatch('account-selected', accountId: $account->id);
        } catch (\Exception $e) {
            Log::error('Error al seleccionar cuenta publicitaria', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Error al seleccionar cuenta')
                ->body('Ha ocurrido un error. Por favor, intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    /**
     * Obtener las cuentas publicitarias asociadas a la cuenta de Facebook actual
     */
    public function getAdvertisingAccounts()
    {
        // Obtener la cuenta de Facebook desde la sesión o la última creada
        $facebookAccountId = session('facebook_account_id');
        $facebookAccount = null;
        
        if ($facebookAccountId) {
            $facebookAccount = FacebookAccount::find($facebookAccountId);
        }
        
        if (!$facebookAccount) {
            $facebookAccount = FacebookAccount::latest()->first();
            
            if ($facebookAccount) {
                session(['facebook_account_id' => $facebookAccount->id]);
            }
        }
        
        // Si no hay cuenta de Facebook o no tiene un token válido, devolver array vacío
        if (!$facebookAccount || !$facebookAccount->hasValidToken()) {
            Log::info('No hay cuenta de Facebook válida para obtener cuentas publicitarias');
            return [];
        }
        
        try {
            // Obtener las cuentas publicitarias y convertirlas a un formato seguro para JSON
            $accounts = $facebookAccount->advertisingAccounts()
                ->select([
                    'id', 
                    'account_id', 
                    'name', 
                    'status', 
                    'currency', 
                    'timezone', 
                    'updated_at'
                ])
                ->get()
                ->map(function ($account) {
                    // Normalizar el ID para asegurarnos que sea compatible con la URL
                    if (str_starts_with($account->account_id, 'act_')) {
                        $account->clean_account_id = substr($account->account_id, 4);
                    } else {
                        $account->clean_account_id = $account->account_id;
                    }
                    return $account;
                })
                ->toArray();
            
            Log::info('Cuentas publicitarias obtenidas', [
                'facebook_account_id' => $facebookAccount->id,
                'facebook_user' => $facebookAccount->facebook_user_name,
                'accounts_count' => count($accounts)
            ]);
            
            return $accounts;
        } catch (\Exception $e) {
            Log::error('Error al obtener cuentas publicitarias', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtener la cuenta seleccionada actualmente
     */
    public function getSelectedAccount()
    {
        if (!$this->selectedAccountId) {
            return null;
        }
        
        // Si el ID seleccionado comienza con 'act_', se está usando el account_id de Facebook
        if (is_string($this->selectedAccountId) && str_starts_with($this->selectedAccountId, 'act_')) {
            // Buscar por account_id en lugar de id
            return AdvertisingAccount::where('account_id', $this->selectedAccountId)->first();
        }
        
        // De lo contrario, asumimos que es un ID de la base de datos
        return AdvertisingAccount::find($this->selectedAccountId);
    }
} 
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
            // Buscar la cuenta publicitaria
            $account = AdvertisingAccount::find($accountId);
            
            if (!$account) {
                Notification::make()
                    ->title('Cuenta no encontrada')
                    ->danger()
                    ->send();
                return;
            }
            
            // Verificar que tenga account_id válido
            if (empty($account->account_id)) {
                Notification::make()
                    ->title('Error en la cuenta')
                    ->body('La cuenta seleccionada no tiene un ID de Facebook válido')
                    ->danger()
                    ->send();
                return;
            }
            
            // Guardar la ID de la cuenta en la sesión
            session(['selected_advertising_account_id' => $accountId]);
            
            // Actualizar la propiedad local
            $this->selectedAccountId = $accountId;
            
            // Guardar información adicional útil
            session([
                'selected_advertising_account_name' => $account->name,
                'selected_advertising_account_fb_id' => $account->account_id
            ]);
            
            // Notificar al usuario
            Notification::make()
                ->title('Cuenta seleccionada')
                ->body("Ahora estás trabajando con la cuenta: {$account->name}")
                ->success()
                ->send();
            
            // Registrar esta acción
            Log::info('Cuenta publicitaria seleccionada', [
                'user_id' => auth()->id(),
                'account_id' => $accountId,
                'account_name' => $account->name,
                'facebook_account_id' => $account->account_id
            ]);
            
            $this->dispatch('account-selected', accountId: $accountId);
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
        
        return AdvertisingAccount::find($this->selectedAccountId);
    }
} 
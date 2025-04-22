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

class AdvertisingAccountsWidget extends BaseWidget
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
        
        // Si no hay cuenta de Facebook o no tiene un token válido, devolver colección vacía
        if (!$facebookAccount || !$facebookAccount->hasValidToken()) {
            Log::info('No hay cuenta de Facebook válida para obtener cuentas publicitarias');
            return collect([]);
        }
        
        // Obtener las cuentas publicitarias
        $accounts = $facebookAccount->advertisingAccounts;
        
        Log::info('Cuentas publicitarias obtenidas', [
            'facebook_account_id' => $facebookAccount->id,
            'facebook_user' => $facebookAccount->facebook_user_name,
            'accounts_count' => $accounts->count()
        ]);
        
        return $accounts;
    }
} 
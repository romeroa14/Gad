<?php

namespace App\Filament\Widgets;

use App\Models\AdvertisingAccount;
use App\Models\FacebookAccount;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

    public function getAdvertisingAccounts()
    {
        $user = Auth::user();
        if (!$user) return collect();
        
        return $user->advertisingAccounts()
            ->get();
    }
} 
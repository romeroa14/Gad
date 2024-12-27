<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ConnectedAccountsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        
        if (!$user) {



        }
        return [
            

            
       
            Stat::make('Estado de Conexión', $user->hasConnectedFacebookAccount() ? 'Conectado' : 'No Conectado')
                ->description('Facebook Business')
                ->color($user->hasConnectedFacebookAccount() ? 'success' : 'danger')
                ->icon('heroicon-o-signal'),

            Stat::make('Cuentas Publicitarias', (string)$user->advertisingAccounts()->count())
                ->description('Cuentas Conectadas')
                ->icon('heroicon-o-building-office')
                ->color('primary'),
        ];
    }
}

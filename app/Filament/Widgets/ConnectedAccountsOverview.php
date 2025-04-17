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
            Stat::make('Estado', Auth::check() ? 'Autenticado' : 'No autenticado')
                ->color(Auth::check() ? 'success' : 'danger')
                ->icon(Auth::check() ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

            // Si el usuario no está autenticado, mostrar botón de login
            ($user && $user->facebook_token) ? null : 
                Action::make('facebook_login')
                    ->label($user ? 'Conectar con Facebook' : 'Iniciar Sesión con Facebook')
                    ->icon('heroicon-o-users')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
       
            Stat::make('Estado de Conexión', $user && $user->advertisingAccounts()->exists() ? 'Conectado' : 'No Conectado')
                ->description('Facebook Business')
                ->color($user && $user->advertisingAccounts()->exists() ? 'success' : 'danger')
                ->icon('heroicon-o-signal'),

            Stat::make('Cuentas Publicitarias', (string)($user ? $user->advertisingAccounts()->count() : 0))
                ->description('Cuentas Conectadas')
                ->icon('heroicon-o-building-office')
                ->color('primary'),
        ];
    }
}

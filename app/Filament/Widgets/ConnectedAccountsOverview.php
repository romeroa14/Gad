<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConnectedAccountsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        // Registra información para depuración
        Log::info('ConnectedAccountsOverview - Estado de usuario', [
            'usuario_existe' => !is_null($user),
            'id' => $user ? $user->id : null,
            'nombre' => $user ? $user->name : null,
            'token_facebook' => $user && !empty($user->facebook_access_token) ? 'Presente' : 'Ausente',
            'cuentas_publicitarias' => $user ? $user->advertisingAccounts()->count() : 0
        ]);
        
        // Si no hay usuario autenticado
        if (!$user) {
            return [
                Stat::make('Estado', 'No autenticado')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle'),

                Action::make('facebook_login')
                    ->label('Iniciar Sesión con Facebook')
                    ->icon('heroicon-o-users')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
                
                Stat::make('Estado de Conexión', 'No Conectado')
                    ->description('Facebook Business')
                    ->color('danger')
                    ->icon('heroicon-o-signal'),

                Stat::make('Cuentas Publicitarias', '0')
                    ->description('Cuentas Conectadas')
                    ->icon('heroicon-o-building-office')
                    ->color('danger'),
            ];
        }

        // Si el usuario está autenticado
        $adAccountsCount = $user->advertisingAccounts()->count();
        $isConnected = !empty($user->facebook_access_token);

        return [
            Stat::make('Estado', 'Autenticado')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            $isConnected ? 
            Action::make('facebook_disconnect')
            ->label('Desconectar Facebook')
            ->icon('heroicon-o-link-slash')
            ->size(ActionSize::Large)
            ->color('danger')
            ->url(route('facebook.disconnect'))
            ->extraAttributes([
                'title' => 'Token expira: ' . ($user->facebook_token_expires_at ? $user->facebook_token_expires_at->format('d/m/Y') : 'Desconocido')
            ])  : 
                Action::make('facebook_login')
                    ->label('Conectar con Facebook')
                    ->icon('heroicon-o-users')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
       
            Stat::make('Estado de Conexión', $adAccountsCount > 0 ? 'Conectado' : 'No Conectado')
                ->description('Facebook Business')
                ->color($adAccountsCount > 0 ? 'success' : 'danger')
                ->icon('heroicon-o-signal'),

            Stat::make('Cuentas Publicitarias', (string)$adAccountsCount)
                ->description('Cuentas Conectadas')
                ->icon('heroicon-o-building-office')
                ->color($adAccountsCount > 0 ? 'primary' : 'danger'),
        ];
    }
}

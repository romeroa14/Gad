<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class ConnectedAccountsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $facebookAccountId = session('facebook_account_id');
        $facebookAccount = null;
        
        if ($facebookAccountId) {
            $facebookAccount = FacebookAccount::find($facebookAccountId);
        }
        
        if (!$facebookAccount) {
            $facebookAccount = FacebookAccount::latest()->first();
            
            if ($facebookAccount) {
                session(['facebook_account_id' => $facebookAccount->id]);
                Log::info('Cuenta de Facebook encontrada y guardada en sesión', [
                    'id' => $facebookAccount->id,
                    'name' => $facebookAccount->facebook_user_name
                ]);
            }
        }
        
        // Determinar si el token existe y es válido
        $tokenExists = $facebookAccount && !empty($facebookAccount->facebook_access_token);
        $tokenIsValid = $tokenExists && $facebookAccount->hasValidToken();
        $needsRefresh = $tokenExists && !$tokenIsValid;
        
        Log::info('Estado de conexión Facebook', [
            'account_exists' => $facebookAccount ? 'true' : 'false',
            'token_exists' => $tokenExists ? 'true' : 'false',
            'token_valid' => $tokenIsValid ? 'true' : 'false',
            'needs_refresh' => $needsRefresh ? 'true' : 'false'
        ]);
        
        $isConnected = $tokenIsValid;
        $adAccountsCount = $isConnected ? $facebookAccount->advertisingAccounts()->count() : 0;
        
        // Construir el array de acciones y estadísticas
        $stats = [];
        
        // Agregar el botón de conexión/desconexión
        $stats[] = $isConnected ? 
            Action::make('facebook_disconnect')
                ->label('Desconectar Facebook')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->url(route('facebook.disconnect'))
                ->extraAttributes([
                    'title' => 'Expira: ' . ($facebookAccount ? $facebookAccount->facebook_token_expires_at->format('d/m/Y H:i') : '')
                ]) : 
            Action::make('facebook_login')
                ->label('Conectar con Facebook')
                ->icon('heroicon-o-users')
                ->size(ActionSize::Large)
                ->color('primary')
                ->url(route('facebook.login'));
        
        // Agregar el botón de refrescar SOLO si realmente se necesita
        if ($needsRefresh) {
            $stats[] = Action::make('facebook_refresh')
                ->label('Refrescar Conexión')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->size(ActionSize::Large)
                ->url(route('facebook.login'));
        }
        
        // Agregar las estadísticas
        $stats[] = Stat::make('Estado de Conexión', $isConnected ? 'Conectado' : 'No Conectado')
            ->description($isConnected && $facebookAccount ? 
                'Facebook: ' . $facebookAccount->facebook_user_name . ' (Expira: ' . $facebookAccount->facebook_token_expires_at->format('d/m/Y H:i') . ')' : 
                'Facebook Business')
            ->color($isConnected ? 'success' : 'danger')
            ->icon('heroicon-o-signal');
        
        $stats[] = Stat::make('Cuentas Publicitarias', (string)$adAccountsCount)
            ->description('Cuentas Conectadas')
            ->icon('heroicon-o-building-office')
            ->color($adAccountsCount > 0 ? 'primary' : 'danger');
        
        return $stats;
    }
}

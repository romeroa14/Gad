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
        $currentUser = auth()->user();
        
        // Obtener la fecha/hora de inicio de sesión actual del usuario
        $userSessionStartTime = session('login_time', now());
        
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
        
        // Determinar si el usuario inició sesión antes de que expire el token
        $showRefreshButton = false;
        if ($tokenExists && $facebookAccount->facebook_token_expires_at) {
            // Solo mostrar el botón si:
            // 1. El token no es válido (ya expiró), O
            // 2. El usuario inició sesión antes de la expiración del token (sesión más antigua que token)
            $showRefreshButton = !$tokenIsValid || 
                                 ($userSessionStartTime < $facebookAccount->facebook_token_expires_at);
            
            Log::info('Evaluando visibilidad del botón refrescar', [
                'user_session_start' => $userSessionStartTime,
                'token_expires_at' => $facebookAccount->facebook_token_expires_at,
                'token_is_valid' => $tokenIsValid ? 'true' : 'false',
                'show_refresh_button' => $showRefreshButton ? 'true' : 'false'
            ]);
        }
        
        Log::info('Estado de conexión Facebook', [
            'account_exists' => $facebookAccount ? 'true' : 'false',
            'token_exists' => $tokenExists ? 'true' : 'false',
            'token_valid' => $tokenIsValid ? 'true' : 'false',
            'user_session_start' => $userSessionStartTime
        ]);
        
        $isConnected = $tokenIsValid;
        $adAccountsCount = $isConnected ? $facebookAccount->advertisingAccounts()->count() : 0;
        
        return [
            // Botón para conectar/desconectar según estado
            $isConnected ? 
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
                    ->url(route('facebook.login')),
    
            // Botón para refrescar manualmente - Simplificamos la lógica para que solo aparezca si el token expiró
            Action::make('facebook_refresh')
                ->label('Refrescar Conexión')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->size(ActionSize::Large)
                ->url(route('facebook.login'))
                ->visible($tokenExists && !$tokenIsValid),
            
            Stat::make('Estado de Conexión', $isConnected ? 'Conectado' : 'No Conectado')
                ->description($isConnected && $facebookAccount ? 
                    'Facebook: ' . $facebookAccount->facebook_user_name . ' (Expira: ' . $facebookAccount->facebook_token_expires_at->format('d/m/Y H:i') . ')' : 
                    'Facebook Business')
                ->color($isConnected ? 'success' : 'danger')
                ->icon('heroicon-o-signal'),

            Stat::make('Cuentas Publicitarias', (string)$adAccountsCount)
                ->description('Cuentas Conectadas')
                ->icon('heroicon-o-building-office')
                ->color($adAccountsCount > 0 ? 'primary' : 'danger'),
        ];
    }
}

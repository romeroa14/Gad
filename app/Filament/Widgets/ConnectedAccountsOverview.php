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
        
        Log::info('Estado de conexión Facebook', [
            'account_exists' => $facebookAccount ? 'true' : 'false',
            'account_id' => $facebookAccount ? $facebookAccount->id : null,
            'token_exists' => $facebookAccount && !empty($facebookAccount->facebook_access_token) ? 'true' : 'false',
            'token_expiry' => $facebookAccount ? $facebookAccount->facebook_token_expires_at : null,
            'token_valid' => $facebookAccount ? $facebookAccount->hasValidToken() : 'false'
        ]);
        
        // Si existe una cuenta pero su token no es válido, intenta verificar directamente con Facebook
        if ($facebookAccount && !$facebookAccount->hasValidToken()) {
            // Este es un método opcional que puedes implementar para verificar el token con Facebook
            if (method_exists($facebookAccount, 'verifyTokenWithFacebook')) {
                $isValid = $facebookAccount->verifyTokenWithFacebook();
                
                if ($isValid) {
                    Log::info('Token verificado y revalidado con Facebook', [
                        'account_id' => $facebookAccount->id,
                        'expires_at' => $facebookAccount->facebook_token_expires_at
                    ]);
                }
            }
        }
        
        $isConnected = $facebookAccount && $facebookAccount->hasValidToken();
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
                        'title' => 'Expira: ' . $facebookAccount->facebook_token_expires_at->format('d/m/Y H:i')
                    ]) : 
                Action::make('facebook_login')
                    ->label('Conectar con Facebook')
                    ->icon('heroicon-o-users')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
        
            // Añadimos un botón para refrescar manualmente
            Action::make('facebook_refresh')
                ->label('Refrescar Conexión')
                ->icon('heroicon-o-arrow-path')
                ->color('secondary')
                ->size($isConnected ? ActionSize::Small : ActionSize::Large)
                ->url(route('facebook.login'))
                ->visible($facebookAccount && !empty($facebookAccount->facebook_access_token)),
        
            Stat::make('Estado de Conexión', $isConnected ? 'Conectado' : 'No Conectado')
                ->description($isConnected ? 
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

<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\AdvertisingAccount;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use App\Filament\Widgets\ConnectedAccountsOverview;
use App\Filament\Widgets\AdvertisingAccountsWidget;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Inicio';
    protected static bool $shouldRegisterNavigation = false;

    protected function getActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            // Usuario no autenticado - mostrar solo el botón de inicio de sesión con Facebook
            return [
                Action::make('facebook_login')
                    ->label('Iniciar Sesión con Facebook')
                    ->icon('heroicon-o-login')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
            ];
        }

        // Usuario autenticado - mostrar botones relevantes para usuarios autenticados
        $actions = [];
        
        // Mostrar información sobre la cuenta activa si hay una seleccionada
        $selectedAdAccountId = session('selected_advertising_account_id');
        if ($selectedAdAccountId) {
            $adAccount = AdvertisingAccount::find($selectedAdAccountId);
            if ($adAccount) {
                $actions[] = Action::make('current_ad_account')
                    ->label('Cuenta Activa: ' . $adAccount->name)
                    ->icon('heroicon-o-building-office')
                    ->color('success')
                    ->size(ActionSize::Large)
                    ->badge(function () use ($adAccount) {
                        // Mostrar el estado de la cuenta como un badge
                        $statusText = 'Inactivo';
                        $color = 'gray';
                        
                        if ($adAccount->status === 1) {
                            $statusText = 'Activo';
                            $color = 'success';
                        } elseif ($adAccount->status === 2) {
                            $statusText = 'Deshabilitado';
                            $color = 'warning';
                        }
                        
                        return $statusText;
                    })
                    ->extraAttributes(['title' => 'ID: ' . $adAccount->account_id])
                    ->disabled();
            }
        }
        
        // Botón para cambiar cuenta publicitaria - visible solo si tiene cuentas conectadas
        if ($user->advertisingAccounts()->exists()) {
            $actions[] = Action::make('change_ad_account')
                ->label('Cambiar Cuenta')
                ->icon('heroicon-o-arrows-right-left')
                ->size(ActionSize::Large)
                ->url('#advertising-accounts-widget')
                ->extraAttributes(['data-scroll-to' => 'advertising-accounts-widget']);
        }
        
        // Si no tiene una conexión de Facebook activa, mostrar el botón para conectar
        if (!$user->hasConnectedFacebookAccount()) {
            $actions[] = Action::make('connect_facebook')
                ->label('Conectar con Facebook')
                ->icon('heroicon-o-link')
                ->size(ActionSize::Large)
                ->color('primary')
                ->url(route('facebook.login'));
        }
        
        // Siempre mostrar el botón de cerrar sesión para usuarios autenticados
        $actions[] = Action::make('logout')
            ->label('Cerrar Sesión')
            ->icon('heroicon-o-logout')
            ->size(ActionSize::Large)
            ->color('danger')
            ->url(route('logout'));
        
        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        // Solo mostrar widgets si el usuario está autenticado
        if (!Auth::check()) {
            return [];
        }
        
        return [
            ConnectedAccountsOverview::class,
            AdvertisingAccountsWidget::class,
        ];
    }
    
    /**
     * Obtener la cuenta publicitaria activa actual
     */
    public function getActiveAdvertisingAccount()
    {
        $accountId = session('selected_advertising_account_id');
        if (!$accountId) {
            return null;
        }
        
        return AdvertisingAccount::find($accountId);
    }
} 
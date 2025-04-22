<?php

namespace App\Filament\Pages;

use App\Models\User;
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
        
        // Botón para seleccionar cuenta publicitaria - visible solo si tiene cuentas conectadas
        if ($user->advertisingAccounts()->exists()) {
            $actions[] = Action::make('select_ad_account')
                ->label('Seleccionar Cuenta Publicitaria')
                ->icon('heroicon-o-building-office')
                ->size(ActionSize::Large)
                ->url(route('filament.resources.advertising-accounts.index'));
        }
        
        // Si no tiene una conexión de Facebook activa, mostrar el botón para conectar
        if (!$user->facebook_access_token) {
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
} 
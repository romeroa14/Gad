<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use App\Filament\Widgets\ConnectedAccountsOverview;
use App\Filament\Widgets\AdvertisingAccountsSelector;
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

        // Si no hay usuario autenticado en Laravel
        if (!$user) {
            return [
                Action::make('facebook_login')
                    ->label('Iniciar Sesión con Facebook')
                    ->icon('heroicon-o-login')
                    ->size(ActionSize::Large)
                    ->color('primary')
                    ->url(route('facebook.login')),
            ];
        }

        $actions = [];
        
        // Si el usuario está autenticado en Laravel pero NO tiene token de Facebook
        if (!$user->facebook_token) {
            $actions[] = Action::make('facebook_login')
                ->label('Conectar con Facebook')
                ->icon('heroicon-o-link')
                ->size(ActionSize::Large)
                ->color('primary')
                ->url(route('facebook.login'));
        }
        // Si el usuario tiene token de Facebook
        else {
            // Botón para seleccionar cuenta publicitaria
            $actions[] = Action::make('select_ad_account')
                ->label('Seleccionar Cuenta Publicitaria')
                ->icon('heroicon-o-building-office')
                ->size(ActionSize::Large)
                ->url(route('filament.resources.advertising-accounts.index'));
            
            // Botón para cerrar sesión de Facebook
            $actions[] = Action::make('facebook_logout')
                ->label('Desconectar Facebook')
                ->icon('heroicon-o-x-mark')
                ->size(ActionSize::Large)
                ->color('warning')
                ->url(route('facebook.logout'));
        }
        
        // Botón general de logout siempre visible
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
        return [
            ConnectedAccountsOverview::class,
        ];
    }
} 
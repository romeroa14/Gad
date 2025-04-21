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

        return [
            Action::make('select_ad_account')
                ->label('Seleccionar Cuenta Publicitaria')
                ->icon('heroicon-o-building-office')
                ->size(ActionSize::Large)
                ->url(route('filament.resources.advertising-accounts.index'))
                ->visible($user->hasConnectedFacebookAccount()),

            Action::make('logout')
                ->label('Cerrar Sesión')
                ->icon('heroicon-o-logout')
                ->size(ActionSize::Large)
                ->color('danger')
                ->url(route('logout'))
                ->visible(true),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConnectedAccountsOverview::class,
        ];
    }
} 
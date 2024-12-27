<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use App\Filament\Widgets\ConnectedAccountsOverview;
use App\Filament\Widgets\AdvertisingAccountsSelector;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Inicio';
    protected static bool $shouldRegisterNavigation = false;

    protected function getActions(): array
    {
        /** @var User|null $user */
        // $user = Auth::user();

        $facebookUserData = Socialite::driver('facebook')->user();
        $user = User::where('email', $facebookUserData->getEmail());

        if (!$user?->token) {
            return [
                Action::make('facebook_login')
                    ->label('Conectar con Facebook')
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
                ->url(route('filament.resources.advertising-accounts.index')),

            Action::make('logout')
                ->label('Cerrar Sesión')
                ->icon('heroicon-o-logout')
                ->size(ActionSize::Large)
                ->color('danger')
                ->url(route('facebook.disconnect'))
                ->visible(true),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConnectedAccountsOverview::class,
            \App\Filament\Widgets\AdvertisingAccountsWidget::class,
        ];
    }
} 
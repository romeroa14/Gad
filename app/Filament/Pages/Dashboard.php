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
            AdvertisingAccountsWidget::class,
        ];
    }
} 
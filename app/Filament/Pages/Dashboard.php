<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use App\Filament\Widgets\ConnectedAccountsOverview;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return [
            Action::make('connect_facebook')
                ->label('Conectar con Facebook')
                ->icon('heroicon-o-link')
                ->size(ActionSize::Large)
                ->color('primary')
                ->url(route('facebook.login'))
                ->visible(!$user->hasConnectedFacebookAccount()),

            Action::make('select_ad_account')
                ->label('Seleccionar Cuenta Publicitaria')
                ->icon('heroicon-o-building-office')
                ->size(ActionSize::Large)
                ->url(route('filament.resources.advertising-accounts.index'))
                ->visible($user->hasConnectedFacebookAccount()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConnectedAccountsOverview::class,
        ];
    }
} 
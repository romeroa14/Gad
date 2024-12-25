<?php

namespace App\Filament\Widgets;

use App\Models\AdvertisingAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdvertisingAccountsSelector extends Widget
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.advertising-accounts-selector';

    public ?string $selectedAccount = null;
    public array $accounts = [];

    public function mount(): void
    {
        $this->loadAccounts();
    }

    protected function loadAccounts(): void
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user || !$user->hasConnectedFacebookAccount()) {
            return;
        }

        $this->accounts = $user->advertisingAccounts()
            ->pluck('name', 'account_id')
            ->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedAccount')
                    ->label('Seleccionar Cuenta Publicitaria')
                    ->options($this->accounts)
                    ->placeholder('Selecciona una cuenta')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        session(['selected_ad_account' => $state]);
                    })
            ]);
    }
} 
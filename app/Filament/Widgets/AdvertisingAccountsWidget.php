<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AdvertisingAccountsWidget extends Widget
{
    protected static string $view = 'filament.widgets.advertising-accounts-widget';
    
    protected int | string | array $columnSpan = 'full';

    public ?string $selectedAccount = null;

    public function mount()
    {
        $this->selectedAccount = session('selected_ad_account');
    }

    public function getAdvertisingAccounts()
    {
        return Auth::user()->advertisingAccounts;
    }

    public function selectAccount($accountId)
    {
        $this->selectedAccount = $accountId;
        session(['selected_ad_account' => $accountId]);
        $this->dispatch('advertising-account-selected', accountId: $accountId);
    }
} 
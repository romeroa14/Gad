<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdvertisingAccountsWidget extends Widget
{
    protected static string $view = 'filament.widgets.advertising-accounts-widget';
    protected int | string | array $columnSpan = 'full';
    
    public ?string $selectedAccount = null;
    public bool $isConnected = false;

    public function mount()
    {
        $this->selectedAccount = session('selected_ad_account');
        $this->isConnected = (bool) Auth::user()->facebook_access_token;
    }

    public function getAdvertisingAccounts()
    {
        return Auth::user()->advertisingAccounts;
    }

    public function getConnectionStatus()
    {
        return $this->isConnected ? 'Conectado' : 'No Conectado';
    }

   
} 
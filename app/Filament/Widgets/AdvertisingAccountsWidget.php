<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdvertisingAccountsWidget extends Widget
{
    protected static string $view = 'filament.widgets.advertising-accounts-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getAdvertisingAccounts()
    {
        return Auth::user()->advertisingAccounts;
    }
} 
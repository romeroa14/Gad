<?php

namespace App\Filament\Widgets;

use App\Models\AdvertisingAccount;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdvertisingAccountsWidget extends Widget
{
    protected static string $view = 'filament.widgets.advertising-accounts-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getAdvertisingAccounts()
    {
        $user = Auth::user();
        if (!$user) return collect();
        
        return $user->advertisingAccounts()
            ->get();
    }
} 
<?php

namespace App\View\Components;

use App\Models\AdvertisingAccount;
use Illuminate\View\Component;

class SelectedAdvertisingAccountBadge extends Component
{
    public $account;

    public function __construct()
    {
        $selectedAccountId = session('selected_advertising_account_id');
        
        if ($selectedAccountId) {
            if (is_string($selectedAccountId) && str_starts_with($selectedAccountId, 'act_')) {
                $this->account = AdvertisingAccount::where('account_id', $selectedAccountId)->first();
            } else {
                $this->account = AdvertisingAccount::find($selectedAccountId);
            }
        }
    }

    public function render()
    {
        return view('components.selected-advertising-account-badge');
    }
} 
<?php

namespace App\Livewire;

use Livewire\Component;

class AdvertisingAccountSelector extends Component
{
    public $clients = [];
    public $selectedClientId = null;
    public $showClientDropdown = false;

    public function mount()
    {
        $this->selectedAccountId = session('selected_advertising_account_id');
        $this->selectedClientId = session('selected_client_id');
        $this->loadAccounts();
        $this->loadClients();
    }

    public function loadClients()
    {
        $this->clients = \App\Models\Client::all();
    }

    public function toggleClientDropdown()
    {
        $this->showClientDropdown = !$this->showClientDropdown;
    }

    public function selectClient($clientId)
    {
        $this->selectedClientId = $clientId;
        session(['selected_client_id' => $clientId]);
        $this->showClientDropdown = false;
        
        // Recargar la página para actualizar los filtros
        return redirect(request()->header('Referer'));
    }
} 
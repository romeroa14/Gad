<?php

namespace App\Filament\Pages;

use App\Models\AdvertisingAccount;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;

class AdvertisingAccountsPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Cuentas Publicitarias';
    protected static ?string $title = 'Seleccionar Cuenta Publicitaria';
    protected static string $view = 'filament.pages.advertising-accounts';
    
    public ?array $data = [];
    public array $accounts = [];
    
    public function mount(): void
    {
        $this->loadAccounts();
        $this->form->fill();
    }
    
    protected function loadAccounts(): void
    {
        /** @var \App\Models\User $user */
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
                Select::make('account_id')
                    ->label('Cuenta Publicitaria')
                    ->options($this->accounts)
                    ->required()
                    ->placeholder('Selecciona una cuenta publicitaria')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return;
        }
        
        // Guarda la cuenta seleccionada en la sesión o en la base de datos
        session(['selected_ad_account' => $data['account_id']]);
        
        // Opcional: También puedes guardarla en la base de datos
        $user->update([
            'current_advertising_account_id' => $data['account_id'],
        ]);
        
        Notification::make()
            ->title('Cuenta publicitaria seleccionada correctamente')
            ->success()
            ->send();
    }
} 
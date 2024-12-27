<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class FacebookAuthWidget extends Widget
{
    protected static string $view = 'filament.widgets.facebook-auth-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function disconnect(): void
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            
            $user->update([
                'name' => null,
                'email' => null,
                'password' => null,
                'email_verified_at' => null,
                'facebook_access_token' => null,
                'facebook_token_expires_at' => null,
                'facebook_id' => null
            ]);
            
            $user->advertisingAccounts()->delete();
            
            Notification::make()
                ->success()
                ->title('Cuenta de Facebook desconectada exitosamente')
                ->send();

            $this->redirect('/admin');
            
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al desconectar la cuenta de Facebook')
                ->send();
        }
    }
} 
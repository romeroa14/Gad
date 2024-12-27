<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FacebookAuthWidget extends Widget
{
    protected static string $view = 'filament.widgets.facebook-auth-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function disconnect(): void
    {
        try {
            DB::beginTransaction();
            
            /** @var User $user */
            $user = Auth::user();
            
            // Primero eliminamos las cuentas publicitarias
            $user->advertisingAccounts()->delete();
            
            // Solo actualizamos los campos de Facebook
            $user->update([
                'facebook_access_token' => null,
                'facebook_token_expires_at' => null,
                'facebook_id' => null
            ]);
            
            DB::commit();
            
            Notification::make()
                ->success()
                ->title('Cuenta de Facebook desconectada exitosamente')
                ->send();

            $this->redirect('/admin');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al desconectar Facebook: ' . $e->getMessage());
            
            Notification::make()
                ->danger()
                ->title('Error al desconectar la cuenta de Facebook')
                ->send();
        }
    }
} 
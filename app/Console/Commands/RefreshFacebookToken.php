<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FacebookAds\FacebookAdsService;

class RefreshFacebookToken extends Command
{
    protected $signature = 'facebook:refresh-token';
    protected $description = 'Refresca el token de acceso de Facebook';

    public function handle(FacebookAdsService $facebookService)
    {
        try {
            $currentToken = env('EAAIkrOlnGSABOz59q6ZBDNkwgk2nIh9olHwXVgr6b0Bn6wBKZAy8VUH89JVCOxvSgcAEQIYESwxVSY0uPG0HgrofQto4dLMgasv1qLurf4vReQdwx9bF5oLaNjZBlXgAlf5Wnj2TTMz352EqjPDlZCJr5Swu1XGoajcnlUpxgVZAVliFHdFlmxdctfuiVJfaIVToYZCZC3D33gIAXgjRYi8ZC3IL2fpGo7ykfVAoqB9x');
            $newToken = $facebookService->exchangeToken($currentToken);

            // Aquí podrías guardar el nuevo token en tu base de datos
            // o actualizar el archivo .env

            $this->info('Token actualizado exitosamente');
        } catch (\Exception $e) {
            $this->error('Error al actualizar token: ' . $e->getMessage());
        }
    }
} 
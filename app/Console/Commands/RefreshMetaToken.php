<?php

namespace App\Console\Commands;

use App\Models\ServiceIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshMetaToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:meta-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresca el token de Meta Ads';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $service = ServiceIntegration::where('name', 'meta')->first();
            if (!$service) {
                throw new \Exception('Servicio de Meta no encontrado');
            }

            $credentials = $service->getMetaCredentials();
            if (!$credentials || !isset($credentials['app_id']) || !isset($credentials['app_secret'])) {
                throw new \Exception('Faltan credenciales de Meta en la configuración');
            }

            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $credentials['app_id'],
                'client_secret' => $credentials['app_secret'],
                'fb_exchange_token' => $credentials['access_token']
            ]);

            if ($response->successful()) {
                $newToken = $response->json()['access_token'];
                $credentials['access_token'] = $newToken;
                $service->setMetaCredentials($credentials);
                
                $this->info('Token actualizado y encriptado correctamente');
                Log::info('Meta token actualizado y encriptado exitosamente');
            } else {
                throw new \Exception('Error al refrescar el token: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error al refrescar Meta token: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 
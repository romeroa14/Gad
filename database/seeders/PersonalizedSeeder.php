<?php

use Illuminate\Database\Seeder;
use App\Models\Personalized;
use App\Models\Client;
use App\Models\AdsCampaign;

class PersonalizedSeeder extends Seeder
{
    public function run()
    {
        // Asegúrate de tener clientes y campañas de anuncios creados
        $client = Client::first();
        $adsCampaign = AdsCampaign::first();

        if ($client && $adsCampaign) {
            Personalized::create([
                'client_id' => $client->id,
                'ads_campaign_id' => $adsCampaign->id,
                'description' => 'Servicio de Trafficker',
                'fees' => 100.00,
                'duration' => 30, // Duración en días
            ]);
        }
    }
}

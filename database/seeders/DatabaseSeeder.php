<?php

use Illuminate\Database\Seeder;
use App\Models\Personalized;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Models\Client;
use App\Models\AdsCampaign;
use Database\Seeders\PlanSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear un cliente de prueba
        $client = Client::firstOrCreate([
            'email' => 'cliente@prueba.com',
        ], [
            'name' => 'Cliente de Prueba',
            'last name' => 'Apellido',
            'phone' => '123456789',
            'business' => 'Negocio de Prueba',
            'country' => 'País',
            'state' => 'Estado',
            'city' => 'Ciudad',
            'address' => 'Dirección de Prueba',
        ]);

        // Crear planes
        $this->call(PlanSeeder::class);

        // Crear una campaña de anuncios de prueba
        $adsCampaign = AdsCampaign::firstOrCreate([
            'name' => 'Campaña de Prueba',
            'client_id' => $client->id,
            'plan_id' => 1, // Asegúrate de que este plan exista
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 100.00,
            'status' => 'Active',
        ]);

        

        // Crear servicios personalizados
        $personalized = Personalized::create([
            'client_id' => $client->id,
            'ads_campaign_id' => $adsCampaign->id,
            'description' => 'Servicio Personalizado',
            'fees' => 100.00,
            'duration' => 30,
        ]);

        // Crear servicios relacionados
        $plans = Plan::all();
        foreach ($plans as $plan) {
            Service::create([
                'serviceable_id' => $plan->id,
                'serviceable_type' => Plan::class,
                'name' => 'Planes publicitarios',
            ]);
        }

        Service::create([
            'serviceable_id' => $personalized->id,
            'serviceable_type' => Personalized::class,
            'name' => 'Servicio Personalizado',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('12345'),
        ]);
    }
}
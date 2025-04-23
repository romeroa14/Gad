<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Crear usuario administrador para acceso directo al sistema
        // (sin necesidad de autenticación Facebook)
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@admetricas.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Opcionalmente, si necesitas más usuarios para pruebas
        User::create([
            'name' => 'Alfredo Romero',
            'email' => 'ceoromeroalfredo@admetricas.com',
            'password' => Hash::make('Marketing21$'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Ruben De Lamas',
            'email' => 'traffickerruben@admetricas.com',
            'password' => Hash::make('ads2025'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Hazabeth Romero',
            'email' => 'Investorhazabeth@admetricas.com',
            'password' => Hash::make('$Inversiones$2025'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Valeria Ramos',
            'email' => 'Valeriaramos@admetricas.com',
            'password' => Hash::make('Orionvs20$'),
            'email_verified_at' => now(),
        ]);
        
        
        
        
        // NOTA: No creamos FacebookAccount ni AdvertisingAccount en el seeder
        // porque estos se crearán dinámicamente cuando el usuario se conecte con Facebook
    }
} 
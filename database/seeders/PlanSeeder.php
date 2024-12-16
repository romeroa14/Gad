<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        Plan::create([
            'daily_investment' => '1$ diarios',
            'duration' => '30 días',
            'scope' => 'Local',
            'investment' => 30.00,
            'price' => 100.00,
        ]);

        Plan::create([
            'daily_investment' => '2$ diarios',
            'duration' => '60 días',
            'scope' => 'Regional',
            'investment' => 60.00,
            'price' => 180.00,
        ]);

        Plan::create([
            'daily_investment' => '3$ diarios',
            'duration' => '90 días',
            'scope' => 'Nacional',
            'investment' => 90.00,
            'price' => 250.00,
        ]);
    }
}

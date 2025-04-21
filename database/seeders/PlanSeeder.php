<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
{
    $plans = [
        // Planes de 1$ diarios (Local)
        ['1$ diarios', 3, 3.00, 6.00],
        ['1$ diarios', 4, 4.00, 8.00],
        ['1$ diarios', 5, 5.00, 10.00],
        ['1$ diarios', 6, 6.00, 12.00],
        ['1$ diarios', 7, 7.00, 14.00],
        ['1$ diarios', 8, 8.00, 16.00],
        ['1$ diarios', 9, 9.00, 18.00],
        ['1$ diarios', 10, 10.00, 20.00],
        ['1$ diarios', 15, 15.00, 30.00],

        // Planes de 2$ diarios (Regional)
        ['2$ diarios', 3, 6.00, 11.00],
        ['2$ diarios', 4, 8.00, 13.00],
        ['2$ diarios', 5, 10.00, 15.00],
        ['2$ diarios', 6, 12.00, 18.00],
        ['2$ diarios', 7, 14.00, 20.00],
        ['2$ diarios', 8, 16.00, 24.00],
        ['2$ diarios', 9, 18.00, 26.00],
        ['2$ diarios', 10, 20.00, 32.00],
        ['2$ diarios', 15, 30.00, 45.00],

        // Planes de 3$ diarios (Nacional)
        ['3$ diarios', 3, 9.00, 15.00],
        ['3$ diarios', 4, 12.00, 18.00],
        ['3$ diarios', 5, 15.00, 22.00],
        ['3$ diarios', 6, 18.00, 25.00],
        ['3$ diarios', 7, 21.00, 29.00],
        ['3$ diarios', 8, 24.00, 32.00],
        ['3$ diarios', 9, 27.00, 36.00],
        ['3$ diarios', 10, 30.00, 39.00],
        ['3$ diarios', 15, 45.00, 57.00],
    ];

    foreach ($plans as $plan) {
        Plan::create([
            'daily_investment' => $plan[0],
            'duration' => $plan[1] . ' días',
            'scope' => $this->getScope($plan[0]),
            'investment' => $plan[2],
            'price' => $plan[3],
        ]);
    }
}

private function getScope($dailyInvestment)
{
    return match($dailyInvestment) {
        '1$ diarios' => 'Local',
        '2$ diarios' => 'Regional',
        '3$ diarios' => 'Nacional',
        default => 'Local'
    };
}
}

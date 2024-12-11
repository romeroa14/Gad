<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        // Lógica de renovación de token
        // Se recomienda cada 60 días
    }
} 
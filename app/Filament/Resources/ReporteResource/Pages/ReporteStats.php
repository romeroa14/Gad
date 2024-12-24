<?php

namespace App\Filament\Resources\ReporteResource\Pages;

use App\Filament\Resources\ReporteResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use App\Services\FacebookAds\FacebookAdsService;

class ReporteStats extends Page
{
    protected static string $resource = ReporteResource::class;

    protected static string $view = 'filament.resources.reporte-resource.pages.reporte-stats';

    public $reporte;
    protected $facebookAdsService;

    public function mount($record)
    {
        $this->reporte = $record;
        $this->facebookAdsService = app(FacebookAdsService::class);
    }

    public function getInsights()
    {
        return $this->facebookAdsService->getAccountInsights();
    }
} 
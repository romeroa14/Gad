<?php

namespace App\Filament\Resources\PersonalizedResource\Pages;

use App\Filament\Resources\PersonalizedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonalizeds extends ListRecords
{
    protected static string $resource = PersonalizedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

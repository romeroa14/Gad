<?php

namespace App\Filament\Resources\PersonalizedResource\Pages;

use App\Filament\Resources\PersonalizedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonalized extends EditRecord
{
    protected static string $resource = PersonalizedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

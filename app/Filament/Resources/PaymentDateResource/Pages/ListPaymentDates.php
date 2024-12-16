<?php

namespace App\Filament\Resources\PaymentDateResource\Pages;

use App\Filament\Resources\PaymentDateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentDates extends ListRecords
{
    protected static string $resource = PaymentDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

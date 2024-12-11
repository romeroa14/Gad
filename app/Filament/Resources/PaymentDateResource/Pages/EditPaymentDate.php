<?php

namespace App\Filament\Resources\PaymentDateResource\Pages;

use App\Filament\Resources\PaymentDateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentDate extends EditRecord
{
    protected static string $resource = PaymentDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

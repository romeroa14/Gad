<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentDateResource\Pages;
use App\Filament\Resources\PaymentDateResource\RelationManagers;
use App\Models\PaymentDate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentDateResource extends Resource
{
    protected static ?string $model = PaymentDate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2; // Segundo en el menú
    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Fecha de Pago';
    protected static ?string $pluralModelLabel = 'Fechas de Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentDates::route('/'),
            'create' => Pages\CreatePaymentDate::route('/create'),
            'edit' => Pages\EditPaymentDate::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalizedResource\Pages;
use App\Filament\Resources\PersonalizedResource\RelationManagers;
use App\Models\Personalized;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonalizedResource extends Resource
{
    protected static ?string $model = Personalized::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?string $navigationLabel = 'Campaña Personalizada';
    protected static ?string $pluralModelLabel = 'Campañas Personalizadas';

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
            'index' => Pages\ListPersonalizeds::route('/'),
            'create' => Pages\CreatePersonalized::route('/create'),
            'edit' => Pages\EditPersonalized::route('/{record}/edit'),
        ];
    }
}

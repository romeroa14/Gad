<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Planes';
    protected static ?string $pluralModelLabel = 'Planes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('daily_investment')
                    ->label('Inversión Diaria')
                    ->options([
                        '1$ diarios' => '1$ diarios',
                        '2$ diarios' => '2$ diarios',
                        '3$ diarios' => '3$ diarios',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duración')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('scope')
                    ->label('Alcance')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('investment')
                    ->label('Inversión Total')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre del Plan')->searchable(),
                Tables\Columns\TextColumn::make('daily_investment')->label('Inversión Diaria'),
                Tables\Columns\TextColumn::make('duration')->label('Duración'),
                Tables\Columns\TextColumn::make('scope')->label('Alcance'),
                Tables\Columns\TextColumn::make('investment')->label('Inversión Total')->money('usd', true),
                Tables\Columns\TextColumn::make('price')->label('Precio')->money('usd', true),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha de Creación')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('daily_investment')
                    ->label('Inversión Diaria')
                    ->options([
                        '1$ diarios' => '1$ diarios',
                        '2$ diarios' => '2$ diarios',
                        '3$ diarios' => '3$ diarios',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdsCampaignResource\Pages;
use App\Filament\Resources\AdsCampaignResource\RelationManagers;
use App\Models\AdsCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdsCampaignResource extends Resource
{
    protected static ?string $model = AdsCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Campañas Publicitarias';
    protected static ?string $pluralModelLabel = 'Campañas Publicitarias';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Nombre de la Campaña'),
            Forms\Components\Select::make('client_id')
                ->relationship('client', 'name')
                ->label('Cliente')
                ->required(),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->label('Plan')
                ->nullable(),
            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->label('Fecha de Inicio'),
            Forms\Components\DatePicker::make('end_date')
                ->required()
                ->label('Fecha de Fin'),
            Forms\Components\TextInput::make('budget')
                ->numeric()
                ->required()
                ->label('Presupuesto'),
            Forms\Components\TextInput::make('actual_cost')
                ->numeric()
                ->label('Costo Real'),
            Forms\Components\Select::make('status')
                ->options([
                    'Active' => 'Activa',
                    'Paused' => 'Pausada',
                    'Finished' => 'Finalizada',
                ])
                ->required()
                ->label('Estado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre'),
            Tables\Columns\TextColumn::make('client.name')->label('Cliente'),
            Tables\Columns\TextColumn::make('plan.name')->label('Plan'),
            Tables\Columns\TextColumn::make('start_date')->label('Inicio'),
            Tables\Columns\TextColumn::make('end_date')->label('Fin'),
            Tables\Columns\TextColumn::make('budget')->label('Presupuesto')->money('usd', true),
            Tables\Columns\TextColumn::make('actual_cost')->label('Costo Real')->money('usd', true),
            
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'Active' => 'Activa',
                    'Paused' => 'Pausada',
                    'Finished' => 'Finalizada',
                ]),
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
            'index' => Pages\ListAdsCampaigns::route('/'),
            'create' => Pages\CreateAdsCampaign::route('/create'),
            'edit' => Pages\EditAdsCampaign::route('/{record}/edit'),
        ];
    }
}

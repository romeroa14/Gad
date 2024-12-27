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
use App\Services\MetaAdsService;
use App\Filament\Widgets\AdvertisingAccountsWidget;

class AdsCampaignResource extends Resource
{
    protected static ?string $model = AdsCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Campañas Publicitarias';
    protected static ?string $pluralModelLabel = 'Campañas Publicitarias';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Campaña')
                    ->required(),
                
                Forms\Components\Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "Nombre: {$record->name} | Negocio: {$record->business}")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('plan')
                    ->label('Plan')
                    ->options([
                        'basic' => 'Básico',
                        'premium' => 'Premium',
                        'enterprise' => 'Empresarial'
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->required(),

                Forms\Components\TextInput::make('budget')
                    ->label('Presupuesto')
                    ->numeric()
                    ->required(),

                // Forms\Components\TextInput::make('meta_campaign_id')
                //     ->label('ID de Campaña en Meta')
                //     ->helperText('Opcional - Para sincronización con Meta Ads')
                //     ->rules(['nullable', function($attribute, $value, $fail) {
                //         if ($value && !(new MetaAdsService())->validateCampaignExists($value)) {
                //             $fail('La campaña no existe en Meta Ads');
                //         }
                //     }]),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'completed' => 'Completada'
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('budget')
                    ->label('Presupuesto')
                    ->money('usd')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('actual_cost')
                    ->label('Costo Real')
                    ->money('usd')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'completed' => 'danger',
                        default => 'secondary',
                    })
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'completed' => 'Finalizada',
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

    public static function getHeaderWidgets(): array
    {
        return [
            AdvertisingAccountsWidget::class,
        ];
    }
}

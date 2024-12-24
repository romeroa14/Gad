<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReporteResource\Pages;
use App\Models\Reporte;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Card;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;

class ReporteResource extends Resource
{
    protected static ?string $model = Reporte::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Estadísticas';
    protected static ?string $navigationLabel = 'Reporte';
    protected static ?string $pluralModelLabel = 'Reportes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Select::make('advertising_account_id')
                            ->label('Cuenta Publicitaria')
                            ->relationship(
                                name: 'advertisingAccount',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id())
                            )
                            ->required(),
                            
                        Select::make('date_range')
                            ->label('Rango de Fechas')
                            ->options([
                                'last_7_days' => 'Últimos 7 días',
                                'last_30_days' => 'Últimos 30 días',
                                'this_month' => 'Este mes',
                                'custom' => 'Personalizado'
                            ])
                            ->default('last_30_days')
                            ->reactive(),

                        Select::make('metrics')
                            ->label('Métricas')
                            ->multiple()
                            ->options([
                                'impressions' => 'Impresiones',
                                'clicks' => 'Clics',
                                'spend' => 'Gastos',
                                'ctr' => 'CTR',
                                'reach' => 'Alcance',
                                'frequency' => 'Frecuencia'
                            ])
                            ->default(['impressions', 'clicks', 'spend']),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('advertising_account.name')
                    ->label('Cuenta Publicitaria')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('advertising_account_id')
                    ->label('Cuenta Publicitaria')
                    ->relationship('advertisingAccount', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
            ])
            ->actions([
                Tables\Actions\Action::make('view_stats')
                    ->label('Ver Estadísticas')
                    ->icon('heroicon-o-chart-bar')
                    ->size(ActionSize::Large)
                    ->url(fn (Reporte $record): string => route('filament.resources.reportes.stats', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListReportes::route('/'),
            'create' => Pages\CreateReporte::route('/create'),
            'edit' => Pages\EditReporte::route('/{record}/edit'),
            'stats' => Pages\ReporteStats::route('/{record}/stats'),
        ];
    }
}

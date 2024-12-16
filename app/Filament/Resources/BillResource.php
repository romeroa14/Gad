<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Filament\Resources\BillResource\RelationManagers;
use App\Models\Bill;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 2; // Segundo en el menú
    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Factura';
    protected static ?string $pluralModelLabel = 'Facturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->required(),
                
                Forms\Components\Select::make('service_id')
                    ->label('Servicio')
                    ->options(function () {
                        return Service::query()
                            ->with('serviceable')
                            ->get()
                            ->mapWithKeys(function ($service) {
                                $relatedName = method_exists($service->serviceable, 'getName')
                                    ? $service->serviceable->getName()
                                    : $service->serviceable->name ?? 'N/A';

                                return [$service->id => $service->name . ' (' . $relatedName . ')'];
                            });
                    })
                    ->required(),
    
                Forms\Components\Select::make('payment_method_id')
                    ->label('Método de Pago')
                    ->relationship('paymentMethod', 'name')
                    ->required(),
    
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->nullable()
                    ->maxLength(500),
    
                Forms\Components\DatePicker::make('date_beginning')
                    ->label('Fecha de Inicio')
                    ->required(),
    
                Forms\Components\DatePicker::make('date_end')
                    ->label('Fecha de Fin')
                    ->required()
                    ->after('date_beginning'),
    
                Forms\Components\TextInput::make('discount_percentage')
                    ->label('Descuento (%)')
                    ->numeric()
                    // ->min(0)
                    // ->max(100)
                    ->nullable(),
    
                Forms\Components\TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
    
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'Pending' => 'Pendiente',
                        'Paid' => 'Pagada',
                        'Cancelled' => 'Cancelada',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Método de Pago')
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('date_beginning')
                    ->label('Inicio')
                    ->date()
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('date_end')
                    ->label('Fin')
                    ->date()
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('usd', true)
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Pending' => 'Pendiente',
                        'Paid' => 'Pagada',
                        'Cancelled' => 'Cancelada',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('date_beginning', 'asc');
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }
}

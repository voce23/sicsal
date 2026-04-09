<?php

namespace App\Filament\Resources\MetaInes\Tables;

use App\Models\MetaIne;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MetaInesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('centroSalud.nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Centro de Salud'),
                TextColumn::make('anio')
                    ->sortable()
                    ->label('Año'),
                TextColumn::make('grupo_etareo')
                    ->badge()
                    ->label('Grupo Etáreo'),
                TextColumn::make('sexo')
                    ->badge()
                    ->label('Sexo'),
                TextColumn::make('cantidad')
                    ->numeric()
                    ->sortable()
                    ->label('Cantidad'),
            ])
            ->filters([
                SelectFilter::make('anio')
                    ->options(fn () => MetaIne::distinct()->pluck('anio', 'anio')->toArray())
                    ->label('Año'),
                SelectFilter::make('sexo')
                    ->options(['M' => 'Masculino', 'F' => 'Femenino', 'ambos' => 'Ambos'])
                    ->label('Sexo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

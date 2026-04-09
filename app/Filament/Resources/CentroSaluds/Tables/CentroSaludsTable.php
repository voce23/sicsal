<?php

namespace App\Filament\Resources\CentroSaluds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CentroSaludsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                TextColumn::make('codigo_snis')
                    ->searchable()
                    ->label('Código SNIS'),
                TextColumn::make('municipio.nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Municipio'),
                TextColumn::make('red_salud')
                    ->searchable()
                    ->label('Red de Salud'),
                TextColumn::make('subsector')
                    ->badge()
                    ->label('Subsector'),
                TextColumn::make('poblacion_ine')
                    ->numeric()
                    ->sortable()
                    ->label('Pob. INE'),
                IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
            ])
            ->filters([
                //
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

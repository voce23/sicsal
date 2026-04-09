<?php

namespace App\Filament\Resources\Comunidads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComunidadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                TextColumn::make('centroSalud.nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Centro de Salud'),
                TextColumn::make('distancia_km')
                    ->numeric()
                    ->sortable()
                    ->suffix(' km')
                    ->label('Distancia'),
                IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
            ])
            ->filters([])
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

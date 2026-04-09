<?php

namespace App\Filament\Resources\Defuncions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DefuncionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable()
                    ->label('Nombres'),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable()
                    ->label('Apellidos'),
                TextColumn::make('comunidad.nombre')
                    ->searchable()
                    ->label('Comunidad'),
                TextColumn::make('fecha_defuncion')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fecha defunción'),
                TextColumn::make('sexo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'M' ? 'Masculino' : 'Femenino')
                    ->label('Sexo'),
                TextColumn::make('lugar')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'establecimiento' => 'Establecimiento',
                        'domicilio'       => 'Domicilio',
                        'referido'        => 'Referido',
                        'en_transito'     => 'En tránsito',
                        default           => $state,
                    })
                    ->label('Lugar'),
                TextColumn::make('grupo_etareo_defuncion')
                    ->badge()
                    ->label('Grupo etáreo'),
                TextColumn::make('registradoPor.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('lugar')
                    ->options([
                        'establecimiento' => 'Establecimiento',
                        'domicilio'       => 'Domicilio',
                        'referido'        => 'Referido',
                        'en_transito'     => 'En tránsito',
                    ])
                    ->label('Lugar'),
                SelectFilter::make('grupo_etareo_defuncion')
                    ->options([
                        'neonatal'         => 'Neonatal',
                        'infantil_menor_1' => 'Infantil <1 año',
                        'menor_5'          => 'Menor de 5 años',
                        '5_a_59'           => '5 a 59 años',
                        'adulto_mayor'     => 'Adulto mayor (≥60)',
                    ])
                    ->label('Grupo etáreo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_defuncion', 'desc');
    }
}

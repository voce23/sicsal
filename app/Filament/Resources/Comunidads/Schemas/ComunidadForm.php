<?php

namespace App\Filament\Resources\Comunidads\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ComunidadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('centro_salud_id')
                    ->relationship('centroSalud', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Centro de Salud')
                    ->disabled(fn () => auth()->user()?->hasRole('admin'))
                    ->default(fn () => auth()->user()?->centro_salud_id),
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100)
                    ->label('Nombre de la comunidad'),
                TextInput::make('distancia_km')
                    ->numeric()
                    ->minValue(0)
                    ->label('Distancia (km)'),
                TextInput::make('latitud')
                    ->numeric()
                    ->label('Latitud'),
                TextInput::make('longitud')
                    ->numeric()
                    ->label('Longitud'),
                Toggle::make('activo')
                    ->default(true)
                    ->label('Activo'),
            ]);
    }
}

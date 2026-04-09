<?php

namespace App\Filament\Resources\CentroSaluds\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CentroSaludForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->columns(2)
                    ->schema([
                        Select::make('municipio_id')
                            ->relationship('municipio', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Municipio')
                            ->columnSpan(1),
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(150)
                            ->label('Nombre del centro')
                            ->columnSpan(1),
                        TextInput::make('codigo_snis')
                            ->maxLength(20)
                            ->label('Código SNIS')
                            ->columnSpan(1),
                        Select::make('subsector')
                            ->options([
                                'Público' => 'Público',
                                'Seguro Social' => 'Seguro Social',
                                'Privado' => 'Privado',
                                'ONG' => 'ONG',
                            ])
                            ->default('Público')
                            ->required()
                            ->label('Subsector')
                            ->columnSpan(1),
                        TextInput::make('red_salud')
                            ->maxLength(100)
                            ->label('Red de Salud')
                            ->columnSpan(1),
                        TextInput::make('poblacion_ine')
                            ->numeric()
                            ->default(0)
                            ->label('Población INE asignada')
                            ->columnSpan(1),
                        Toggle::make('activo')
                            ->default(true)
                            ->label('Activo')
                            ->columnSpan(2),
                    ]),

                Section::make('Ubicación Geográfica')
                    ->description('Coordenadas GPS del establecimiento para mostrar en el mapa público. Para obtenerlas: abre Google Maps, haz clic derecho sobre el establecimiento y copia las coordenadas.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('latitud')
                            ->numeric()
                            ->label('Latitud')
                            ->placeholder('-17.886227')
                            ->helperText('Ejemplo: -17.886227 (negativo para hemisferio sur)')
                            ->rules(['nullable', 'numeric', 'between:-90,90'])
                            ->columnSpan(1),
                        TextInput::make('longitud')
                            ->numeric()
                            ->label('Longitud')
                            ->placeholder('-66.201318')
                            ->helperText('Ejemplo: -66.201318 (negativo para Bolivia)')
                            ->rules(['nullable', 'numeric', 'between:-180,180'])
                            ->columnSpan(1),
                        Placeholder::make('mapa_ayuda')
                            ->label('')
                            ->content('💡 Cómo obtener coordenadas: 1) Abrir Google Maps → 2) Buscar el establecimiento → 3) Clic derecho sobre el lugar exacto → 4) Copiar los números (latitud, longitud) que aparecen arriba del menú.')
                            ->columnSpan(2),
                    ]),
            ]);
    }
}

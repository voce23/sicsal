<?php

namespace App\Filament\Resources\MetaInes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MetaIneForm
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
                TextInput::make('anio')
                    ->required()
                    ->numeric()
                    ->minValue(2020)
                    ->maxValue(2050)
                    ->default(date('Y'))
                    ->label('Año'),
                Select::make('grupo_etareo')
                    ->options([
                        'menor_1'               => 'Menor de 1 año',
                        '1_anio'                => '1 año',
                        '2_anios'               => '2 años',
                        '3_anios'               => '3 años',
                        '4_anios'               => '4 años',
                        '1_4'                   => '1 a 4 años',
                        '5_anios'               => '5 años',
                        '6_anios'               => '6 años',
                        'menor_5'               => 'Menor de 5 años',
                        'mayor_5'               => 'Mayor de 5 años',
                        'menor_2'               => 'Menor de 2 años',
                        '2_3'                   => '2 a 3 años',
                        '2_4'                   => '2 a 4 años',
                        '5_9'                   => '5 a 9 años',
                        '7_9'                   => '7 a 9 años',
                        '10'                    => '10 años',
                        '10_14'                 => '10 a 14 años',
                        '15_19'                 => '15 a 19 años',
                        '20_39'                 => '20 a 39 años',
                        '40_49'                 => '40 a 49 años',
                        '50_59'                 => '50 a 59 años',
                        'mayor_60'              => 'Mayor de 60 años',
                        'mef_15_40'             => 'MEF 15 a 40 años',
                        '7_49'                  => '7 a 49 años',
                        'adolescentes_10_19'    => 'Adolescentes 10 a 19 años',
                        'mujeres_menor_20'      => 'Mujeres menores de 20 años',
                        'embarazos_esperados'   => 'Embarazos esperados',
                        'partos_esperados'      => 'Partos esperados',
                        'nacimientos_esperados' => 'Nacimientos esperados',
                        'dt_7_49'               => 'dT 7 a 49 años',
                    ])
                    ->required()
                    ->label('Grupo Etáreo'),
                Select::make('sexo')
                    ->options([
                        'M'     => 'Masculino',
                        'F'     => 'Femenino',
                        'ambos' => 'Ambos',
                    ])
                    ->required()
                    ->label('Sexo'),
                TextInput::make('cantidad')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->label('Cantidad'),
            ]);
    }
}

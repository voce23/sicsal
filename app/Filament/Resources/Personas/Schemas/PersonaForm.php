<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->schema([
                        TextInput::make('nombres')
                            ->required()
                            ->maxLength(100)
                            ->label('Nombres'),
                        TextInput::make('apellidos')
                            ->required()
                            ->maxLength(100)
                            ->label('Apellidos'),
                        DatePicker::make('fecha_nacimiento')
                            ->required()
                            ->maxDate(now())
                            ->label('Fecha de nacimiento'),
                        Radio::make('sexo')
                            ->options(['M' => 'Masculino', 'F' => 'Femenino'])
                            ->required()
                            ->inline()
                            ->label('Sexo'),
                        TextInput::make('ci')
                            ->maxLength(20)
                            ->label('Cédula de identidad'),
                    ])
                    ->columns(2),

                Section::make('Seguro y ubicación')
                    ->schema([
                        Select::make('tipo_seguro')
                            ->options([
                                'SUS'     => 'SUS (Seguro Universal de Salud)',
                                'privado' => 'Privado',
                                'ninguno' => 'Ninguno',
                            ])
                            ->default('ninguno')
                            ->required()
                            ->label('Tipo de seguro'),
                        Select::make('comunidad_id')
                            ->relationship(
                                'comunidad',
                                'nombre',
                                fn ($query) => $query->where(
                                    'centro_salud_id',
                                    auth()->user()?->centro_salud_id
                                )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Comunidad'),
                        DatePicker::make('fecha_registro')
                            ->required()
                            ->default(today())
                            ->label('Fecha de registro'),
                    ])
                    ->columns(2),

                Section::make('Estado migratorio')
                    ->schema([
                        Select::make('estado')
                            ->options([
                                'residente' => 'Residente',
                                'temporal'  => 'Temporal',
                                'migrado'   => 'Migrado',
                                'fallecido' => 'Fallecido',
                            ])
                            ->default('residente')
                            ->required()
                            ->live()
                            ->label('Estado'),
                        TextInput::make('destino_migracion')
                            ->maxLength(150)
                            ->label('Destino de migración')
                            ->hidden(fn ($get) => $get('estado') !== 'migrado'),
                        DatePicker::make('fecha_migracion')
                            ->label('Fecha de migración')
                            ->hidden(fn ($get) => $get('estado') !== 'migrado'),
                    ])
                    ->columns(2),

                Section::make('Verificación del padrón')
                    ->description('Confirme si la persona fue verificada como residente en la visita domiciliaria.')
                    ->schema([
                        Toggle::make('verificado')
                            ->label('Persona verificada')
                            ->helperText('Active si la presencia de esta persona fue confirmada en la visita.')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('fecha_verificacion', today()->format('Y-m-d'));
                                    $set('verificado_por', auth()->id());
                                }
                            }),
                        DatePicker::make('fecha_verificacion')
                            ->label('Fecha de verificación')
                            ->hidden(fn ($get) => ! $get('verificado')),
                    ])
                    ->columns(2),

                Section::make('Observaciones')
                    ->schema([
                        Textarea::make('observaciones')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

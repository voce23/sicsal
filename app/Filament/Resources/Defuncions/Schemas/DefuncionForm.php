<?php

namespace App\Filament\Resources\Defuncions\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DefuncionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vinculación al padrón')
                    ->schema([
                        Select::make('persona_id')
                            ->label('Buscar en el padrón (opcional)')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Persona::query()
                                ->where('centro_salud_id', auth()->user()?->centro_salud_id)
                                ->where(function ($q) use ($search) {
                                    $q->where('nombres', 'like', "%{$search}%")
                                        ->orWhere('apellidos', 'like', "%{$search}%")
                                        ->orWhere('ci', 'like', "%{$search}%");
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Persona $p) => [$p->id => "{$p->nombres} {$p->apellidos} ({$p->ci})"])
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value) => Persona::find($value)?->nombre_completo ?? $value
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $persona = Persona::find($state);
                                if (! $persona) {
                                    return;
                                }
                                $set('nombres', $persona->nombres);
                                $set('apellidos', $persona->apellidos);
                                $set('fecha_nacimiento', $persona->fecha_nacimiento?->format('Y-m-d'));
                                $set('sexo', $persona->sexo);
                                $set('comunidad_id', $persona->comunidad_id);
                            }),
                    ]),

                Section::make('Datos del fallecido')
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
                            ->label('Fecha de nacimiento'),
                        Select::make('sexo')
                            ->options(['M' => 'Masculino', 'F' => 'Femenino'])
                            ->required()
                            ->label('Sexo'),
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
                            ->label('Comunidad'),
                    ])
                    ->columns(2),

                Section::make('Datos de la defunción')
                    ->schema([
                        DatePicker::make('fecha_defuncion')
                            ->required()
                            ->maxDate(today())
                            ->label('Fecha de defunción'),
                        Select::make('lugar')
                            ->options([
                                'establecimiento' => 'En el establecimiento',
                                'domicilio' => 'Domicilio',
                                'referido' => 'Referido',
                                'en_transito' => 'En tránsito',
                            ])
                            ->required()
                            ->label('Lugar de defunción'),
                        Textarea::make('causa_defuncion')
                            ->rows(3)
                            ->label('Causa de defunción')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

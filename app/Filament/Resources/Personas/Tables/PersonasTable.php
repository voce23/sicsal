<?php

namespace App\Filament\Resources\Personas\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('verificado')
                    ->label('Ver.')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->falseIcon('heroicon-m-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($state) => $state ? 'Verificado' : 'Sin verificar')
                    ->sortable(),

                TextColumn::make('nombres')
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->sortable()
                    ->label('Nombres'),

                TextColumn::make('apellidos')
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->sortable()
                    ->label('Apellidos'),

                TextColumn::make('comunidad.nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Comunidad'),

                TextColumn::make('edad')
                    ->getStateUsing(fn ($record) => $record->edad.' años')
                    ->sortable(false)
                    ->label('Edad'),

                TextColumn::make('sexo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'M' => 'info',
                        'F' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => $state === 'M' ? 'Masculino' : 'Femenino')
                    ->label('Sexo'),

                TextColumn::make('tipo_seguro')
                    ->badge()
                    ->label('Seguro'),

                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'residente' => 'success',
                        'temporal' => 'warning',
                        'migrado' => 'danger',
                        'fallecido' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'residente' => 'Residente',
                        'temporal' => 'Temporal',
                        'migrado' => 'Migrado',
                        'fallecido' => 'Fallecido',
                        default => $state,
                    })
                    ->label('Estado'),

                TextColumn::make('fecha_verificacion')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Verificado el')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fecha_registro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Registro')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('comunidad_id')
                    ->relationship('comunidad', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Comunidad'),

                SelectFilter::make('sexo')
                    ->options(['M' => 'Masculino', 'F' => 'Femenino'])
                    ->label('Sexo'),

                SelectFilter::make('tipo_seguro')
                    ->options(['SUS' => 'SUS', 'privado' => 'Privado', 'ninguno' => 'Ninguno'])
                    ->label('Tipo de seguro'),

                SelectFilter::make('estado')
                    ->options([
                        'residente' => 'Residente',
                        'temporal' => 'Temporal',
                        'migrado' => 'Migrado',
                        'fallecido' => 'Fallecido',
                    ])
                    ->label('Estado'),

                TernaryFilter::make('verificado')
                    ->label('Verificación')
                    ->trueLabel('Solo verificados')
                    ->falseLabel('Sin verificar')
                    ->nullable(),
            ])
            ->recordActions([
                // Acción rápida: verificar sin abrir el formulario completo
                Action::make('verificar_rapido')
                    ->label('Verificar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->verificado && in_array($record->estado, ['residente', 'temporal']))
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar verificación')
                    ->modalDescription(fn ($record) => "¿Confirma que {$record->nombre_completo} sigue residiendo en la comunidad?")
                    ->action(function ($record) {
                        $record->update([
                            'verificado' => true,
                            'fecha_verificacion' => today(),
                            'verificado_por' => auth()->id(),
                        ]);
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Marcar grupo como verificado
                    BulkAction::make('verificar_masivo')
                        ->label('Marcar como verificadas')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verificar personas seleccionadas')
                        ->modalDescription('Se marcará que todas las personas seleccionadas siguen residiendo en la comunidad.')
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'verificado' => true,
                                'fecha_verificacion' => today(),
                                'verificado_por' => auth()->id(),
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Registrar migración masiva
                    BulkAction::make('registrar_migracion')
                        ->label('Registrar como migrados')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->form([
                            TextInput::make('destino_migracion')
                                ->label('Destino (ciudad / país)')
                                ->maxLength(150),
                            DatePicker::make('fecha_migracion')
                                ->label('Fecha de migración')
                                ->default(today())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(fn ($r) => $r->update([
                                'estado' => 'migrado',
                                'destino_migracion' => $data['destino_migracion'] ?? null,
                                'fecha_migracion' => $data['fecha_migracion'],
                                'verificado' => true,
                                'fecha_verificacion' => today(),
                                'verificado_por' => auth()->id(),
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Registrar fallecimiento masivo
                    BulkAction::make('registrar_fallecido')
                        ->label('Registrar como fallecidos')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Registrar fallecimiento')
                        ->modalDescription('Las personas seleccionadas se marcarán como fallecidas y se excluirán del padrón activo.')
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'estado' => 'fallecido',
                                'verificado' => true,
                                'fecha_verificacion' => today(),
                                'verificado_por' => auth()->id(),
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label('Nombres'),
                TextColumn::make('apellidos')
                    ->sortable()
                    ->searchable()
                    ->label('Apellidos'),
                TextColumn::make('usuario')
                    ->searchable()
                    ->label('Usuario'),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('Rol'),
                TextColumn::make('centroSalud.nombre')
                    ->searchable()
                    ->label('Centro de Salud'),
                IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
                TextColumn::make('ultimo_acceso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('\u00daltimo acceso')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('activo')
                    ->options([1 => 'Activos', 0 => 'Inactivos'])
                    ->label('Estado'),
            ])
            ->defaultSort('activo', 'asc') // pendientes primero
            ->recordActions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->activo)
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar usuario')
                    ->modalDescription(fn ($record) => "¿Activar la cuenta de {$record->name} {$record->apellidos}?")
                    ->action(fn ($record) => $record->update(['activo' => true])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

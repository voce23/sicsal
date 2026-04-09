<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen_portada')
                    ->label('Portada')
                    ->disk('public')
                    ->width(80)
                    ->height(45)
                    ->defaultImageUrl(null),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Post::CATEGORIAS[$state] ?? ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'vacunacion'    => 'info',
                        'salud_materna' => 'danger',
                        'nutricion'     => 'warning',
                        'programas'     => 'primary',
                        'comunidad'     => 'success',
                        'informes'      => 'gray',
                        default         => 'secondary',
                    }),

                IconColumn::make('publicado')
                    ->label('Publicado')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('publicado_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('autor_nombre')
                    ->label('Autor')
                    ->searchable(),

                TextColumn::make('vistas')
                    ->label('Vistas')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('publicado_at', 'desc')
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(Post::CATEGORIAS),

                TernaryFilter::make('publicado')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Publicados')
                    ->falseLabel('Borradores'),
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

<?php

namespace App\Filament\Resources\CausasConsultaExterna;

use App\Filament\Resources\CausasConsultaExterna\Pages\CreateCausaConsultaExterna;
use App\Filament\Resources\CausasConsultaExterna\Pages\EditCausaConsultaExterna;
use App\Filament\Resources\CausasConsultaExterna\Pages\ListCausasConsultaExterna;
use App\Models\CausaConsultaExterna;
use App\Models\CentroSalud;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class CausaConsultaExternaResource extends Resource
{
    protected static ?string $model = CausaConsultaExterna::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'Causas Consulta Externa';

    protected static ?string $modelLabel = 'Causa de Consulta Externa';

    protected static ?string $pluralModelLabel = 'Causas de Consulta Externa';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('centro_salud_id', $user->centro_salud_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $meses = CausaConsultaExterna::$meses;

        return $schema->components([
            Section::make('Período y Centro')
                ->schema([
                    Select::make('centro_salud_id')
                        ->label('Centro de Salud')
                        ->options(fn () => CentroSalud::orderBy('nombre')->pluck('nombre', 'id'))
                        ->default(fn () => auth()->user()->centro_salud_id)
                        ->required()
                        ->disabled(fn () => ! auth()->user()->hasRole('superadmin'))
                        ->dehydrated(true),

                    Select::make('mes')
                        ->label('Mes')
                        ->options($meses)
                        ->default((int) date('n'))
                        ->required(),

                    TextInput::make('anio')
                        ->label('Año')
                        ->numeric()
                        ->minValue(2020)
                        ->maxValue((int) date('Y') + 1)
                        ->default((int) date('Y'))
                        ->required(),
                ])
                ->columns(3),

            Section::make('Diagnóstico y Grupo Etáreo')
                ->schema([
                    TextInput::make('diagnostico')
                        ->label('Diagnóstico (CIE-10 o descripción)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('grupo_etareo')
                        ->label('Grupo Etáreo')
                        ->options(CausaConsultaExterna::$grupos)
                        ->required(),
                ])
                ->columns(2),

            Section::make('Cantidad por Sexo')
                ->schema([
                    TextInput::make('masculino')
                        ->label('Masculino')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),

                    TextInput::make('femenino')
                        ->label('Femenino')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('centroSalud.nombre')
                    ->label('Centro')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mes')
                    ->label('Mes')
                    ->formatStateUsing(fn ($state) => CausaConsultaExterna::$meses[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),

                TextColumn::make('diagnostico')
                    ->label('Diagnóstico')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('grupo_etareo')
                    ->label('Grupo Etáreo')
                    ->formatStateUsing(fn ($state) => CausaConsultaExterna::$grupos[$state] ?? $state),

                TextColumn::make('masculino')
                    ->label('Masc.')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('femenino')
                    ->label('Fem.')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn ($record) => $record->masculino + $record->femenino)
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold'),
            ])
            ->defaultSort('anio', 'desc')
            ->filters([
                SelectFilter::make('mes')
                    ->label('Mes')
                    ->options(CausaConsultaExterna::$meses),

                SelectFilter::make('anio')
                    ->label('Año')
                    ->options(
                        collect(range((int) date('Y'), 2020))
                            ->mapWithKeys(fn ($y) => [$y => $y])
                            ->toArray()
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCausasConsultaExterna::route('/'),
            'create' => CreateCausaConsultaExterna::route('/create'),
            'edit'   => EditCausaConsultaExterna::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\MetaInes;

use App\Filament\Resources\MetaInes\Pages\CreateMetaIne;
use App\Filament\Resources\MetaInes\Pages\EditMetaIne;
use App\Filament\Resources\MetaInes\Pages\ListMetaInes;
use App\Filament\Resources\MetaInes\Schemas\MetaIneForm;
use App\Filament\Resources\MetaInes\Tables\MetaInesTable;
use App\Models\MetaIne;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MetaIneResource extends Resource
{
    protected static ?string $model = MetaIne::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Metas INE';

    protected static ?string $modelLabel = 'Meta INE';

    protected static ?string $pluralModelLabel = 'Metas INE';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['superadmin', 'admin']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('centro_salud_id', $user->centro_salud_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return MetaIneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetaInesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMetaInes::route('/'),
            'create' => CreateMetaIne::route('/create'),
            'edit' => EditMetaIne::route('/{record}/edit'),
        ];
    }
}

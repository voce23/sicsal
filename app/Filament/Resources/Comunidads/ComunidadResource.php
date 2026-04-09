<?php

namespace App\Filament\Resources\Comunidads;

use App\Filament\Resources\Comunidads\Pages\CreateComunidad;
use App\Filament\Resources\Comunidads\Pages\EditComunidad;
use App\Filament\Resources\Comunidads\Pages\ListComunidads;
use App\Filament\Resources\Comunidads\Schemas\ComunidadForm;
use App\Filament\Resources\Comunidads\Tables\ComunidadsTable;
use App\Models\Comunidad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComunidadResource extends Resource
{
    protected static ?string $model = Comunidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Comunidades';

    protected static ?string $modelLabel = 'Comunidad';

    protected static ?string $pluralModelLabel = 'Comunidades';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nombre';

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
        return ComunidadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComunidadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComunidads::route('/'),
            'create' => CreateComunidad::route('/create'),
            'edit' => EditComunidad::route('/{record}/edit'),
        ];
    }
}


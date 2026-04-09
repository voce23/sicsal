<?php

namespace App\Filament\Resources\CentroSaluds;

use App\Filament\Resources\CentroSaluds\Pages\CreateCentroSalud;
use App\Filament\Resources\CentroSaluds\Pages\EditCentroSalud;
use App\Filament\Resources\CentroSaluds\Pages\ListCentroSaluds;
use App\Filament\Resources\CentroSaluds\Schemas\CentroSaludForm;
use App\Filament\Resources\CentroSaluds\Tables\CentroSaludsTable;
use App\Models\CentroSalud;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CentroSaludResource extends Resource
{
    protected static ?string $model = CentroSalud::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Centros de Salud';

    protected static ?string $modelLabel = 'Centro de Salud';

    protected static ?string $pluralModelLabel = 'Centros de Salud';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return CentroSaludForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentroSaludsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCentroSaluds::route('/'),
            'create' => CreateCentroSalud::route('/create'),
            'edit'   => EditCentroSalud::route('/{record}/edit'),
        ];
    }
}


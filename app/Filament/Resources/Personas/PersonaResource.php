<?php

namespace App\Filament\Resources\Personas;

use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Filament\Resources\Personas\Pages\EditPersona;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Filament\Resources\Personas\Tables\PersonasTable;
use App\Models\Persona;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Padrón Poblacional';

    protected static ?string $navigationLabel = 'Padrón de Personas';

    protected static ?string $modelLabel = 'Persona';

    protected static ?string $pluralModelLabel = 'Personas';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombres';

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
        return PersonaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
            'create' => CreatePersona::route('/create'),
            'edit' => EditPersona::route('/{record}/edit'),
        ];
    }
}

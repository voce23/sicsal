<?php

namespace App\Filament\Resources\Defuncions;

use App\Filament\Resources\Defuncions\Pages\CreateDefuncion;
use App\Filament\Resources\Defuncions\Pages\EditDefuncion;
use App\Filament\Resources\Defuncions\Pages\ListDefuncions;
use App\Filament\Resources\Defuncions\Schemas\DefuncionForm;
use App\Filament\Resources\Defuncions\Tables\DefuncionsTable;
use App\Models\Defuncion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DefuncionResource extends Resource
{
    protected static ?string $model = Defuncion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Padrón Poblacional';

    protected static ?string $navigationLabel = 'Defunciones';

    protected static ?string $modelLabel = 'Defunción';

    protected static ?string $pluralModelLabel = 'Defunciones';

    protected static ?int $navigationSort = 2;

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
        return DefuncionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DefuncionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDefuncions::route('/'),
            'create' => CreateDefuncion::route('/create'),
            'edit' => EditDefuncion::route('/{record}/edit'),
        ];
    }
}

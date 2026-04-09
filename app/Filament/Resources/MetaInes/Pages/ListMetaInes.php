<?php

namespace App\Filament\Resources\MetaInes\Pages;

use App\Filament\Resources\MetaInes\MetaIneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMetaInes extends ListRecords
{
    protected static string $resource = MetaIneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

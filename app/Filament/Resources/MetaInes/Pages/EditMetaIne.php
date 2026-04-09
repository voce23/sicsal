<?php

namespace App\Filament\Resources\MetaInes\Pages;

use App\Filament\Resources\MetaInes\MetaIneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMetaIne extends EditRecord
{
    protected static string $resource = MetaIneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

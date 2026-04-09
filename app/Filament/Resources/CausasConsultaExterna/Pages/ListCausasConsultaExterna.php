<?php

namespace App\Filament\Resources\CausasConsultaExterna\Pages;

use App\Filament\Resources\CausasConsultaExterna\CausaConsultaExternaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCausasConsultaExterna extends ListRecords
{
    protected static string $resource = CausaConsultaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

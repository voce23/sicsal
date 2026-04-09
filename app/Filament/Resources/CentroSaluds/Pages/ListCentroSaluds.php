<?php

namespace App\Filament\Resources\CentroSaluds\Pages;

use App\Filament\Resources\CentroSaluds\CentroSaludResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCentroSaluds extends ListRecords
{
    protected static string $resource = CentroSaludResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

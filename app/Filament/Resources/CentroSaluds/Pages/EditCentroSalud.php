<?php

namespace App\Filament\Resources\CentroSaluds\Pages;

use App\Filament\Resources\CentroSaluds\CentroSaludResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCentroSalud extends EditRecord
{
    protected static string $resource = CentroSaludResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

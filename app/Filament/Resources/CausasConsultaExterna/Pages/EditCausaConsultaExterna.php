<?php

namespace App\Filament\Resources\CausasConsultaExterna\Pages;

use App\Filament\Resources\CausasConsultaExterna\CausaConsultaExternaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCausaConsultaExterna extends EditRecord
{
    protected static string $resource = CausaConsultaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

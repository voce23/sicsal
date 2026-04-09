<?php

namespace App\Filament\Resources\CausasConsultaExterna\Pages;

use App\Filament\Resources\CausasConsultaExterna\CausaConsultaExternaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCausaConsultaExterna extends CreateRecord
{
    protected static string $resource = CausaConsultaExternaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()->hasRole('superadmin')) {
            $data['centro_salud_id'] = auth()->user()->centro_salud_id;
        }

        return $data;
    }
}

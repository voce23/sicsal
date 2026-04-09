<?php

namespace App\Filament\Resources\Defuncions\Pages;

use App\Filament\Resources\Defuncions\DefuncionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDefuncion extends EditRecord
{
    protected static string $resource = DefuncionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

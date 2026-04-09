<?php

namespace App\Filament\Resources\Defuncions\Pages;

use App\Filament\Resources\Defuncions\DefuncionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDefuncions extends ListRecords
{
    protected static string $resource = DefuncionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AboutCoreValues\Pages;

use App\Filament\Resources\AboutCoreValues\AboutCoreValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAboutCoreValues extends ListRecords
{
    protected static string $resource = AboutCoreValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

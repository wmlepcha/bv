<?php

namespace App\Filament\Resources\HomeServiceCards\Pages;

use App\Filament\Resources\HomeServiceCards\HomeServiceCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeServiceCards extends ListRecords
{
    protected static string $resource = HomeServiceCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

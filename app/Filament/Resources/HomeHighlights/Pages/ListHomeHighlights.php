<?php

namespace App\Filament\Resources\HomeHighlights\Pages;

use App\Filament\Resources\HomeHighlights\HomeHighlightResource;
use Filament\Resources\Pages\ListRecords;

class ListHomeHighlights extends ListRecords
{
    protected static string $resource = HomeHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

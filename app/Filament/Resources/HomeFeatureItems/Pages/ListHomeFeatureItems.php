<?php

namespace App\Filament\Resources\HomeFeatureItems\Pages;

use App\Filament\Resources\HomeFeatureItems\HomeFeatureItemResource;
use Filament\Resources\Pages\ListRecords;

class ListHomeFeatureItems extends ListRecords
{
    protected static string $resource = HomeFeatureItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

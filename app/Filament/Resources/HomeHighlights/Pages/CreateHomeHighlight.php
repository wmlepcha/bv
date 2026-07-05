<?php

namespace App\Filament\Resources\HomeHighlights\Pages;

use App\Filament\Resources\HomeHighlights\HomeHighlightResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeHighlight extends CreateRecord
{
    protected static string $resource = HomeHighlightResource::class;
}

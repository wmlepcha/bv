<?php

namespace App\Filament\Resources\HomeHighlights\Pages;

use App\Filament\Resources\HomeHighlights\HomeHighlightResource;
use Filament\Resources\Pages\EditRecord;

class EditHomeHighlight extends EditRecord
{
    protected static string $resource = HomeHighlightResource::class;
}

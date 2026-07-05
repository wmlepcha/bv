<?php

namespace App\Filament\Resources\AboutParagraphs\Pages;

use App\Filament\Resources\AboutParagraphs\AboutParagraphResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAboutParagraphs extends ListRecords
{
    protected static string $resource = AboutParagraphResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

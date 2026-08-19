<?php

namespace App\Filament\Resources\HomeProgramCards\Pages;

use App\Filament\Resources\HomeProgramCards\HomeProgramCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeProgramCards extends ListRecords
{
    protected static string $resource = HomeProgramCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

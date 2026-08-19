<?php

namespace App\Filament\Resources\Recruiters\Pages;

use App\Filament\Resources\Recruiters\RecruiterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruiters extends ListRecords
{
    protected static string $resource = RecruiterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

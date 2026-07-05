<?php

namespace App\Filament\Resources\AboutJourneyMilestones\Pages;

use App\Filament\Resources\AboutJourneyMilestones\AboutJourneyMilestoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAboutJourneyMilestones extends ListRecords
{
    protected static string $resource = AboutJourneyMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

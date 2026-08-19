<?php

namespace App\Filament\Resources\TrainingManagers\Pages;

use App\Filament\Resources\TrainingManagers\TrainingManagerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingManagers extends ListRecords
{
    protected static string $resource = TrainingManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

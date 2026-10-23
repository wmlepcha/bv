<?php

namespace App\Filament\Resources\RecruitmentManagers\Pages;

use App\Filament\Resources\RecruitmentManagers\RecruitmentManagerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentManagers extends ListRecords
{
    protected static string $resource = RecruitmentManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

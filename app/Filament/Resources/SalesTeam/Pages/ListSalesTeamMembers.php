<?php

namespace App\Filament\Resources\SalesTeam\Pages;

use App\Filament\Resources\SalesTeam\SalesTeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesTeamMembers extends ListRecords
{
    protected static string $resource = SalesTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

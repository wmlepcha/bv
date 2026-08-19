<?php

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCenter extends EditRecord
{
    protected static string $resource = CenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

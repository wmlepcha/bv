<?php

namespace App\Filament\Resources\ActivationCodes\Pages;

use App\Filament\Resources\ActivationCodes\ActivationCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivationCodes extends ListRecords
{
    protected static string $resource = ActivationCodeResource::class;

    /**
     * Filament remembers each person's column choices in their session, which would keep showing the columns
     * we just hid by default. Bump the version when the default columns change so everyone gets the new default once.
     */
    public function getTableColumnsSessionKey(): string
    {
        return parent::getTableColumnsSessionKey().'_v2';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

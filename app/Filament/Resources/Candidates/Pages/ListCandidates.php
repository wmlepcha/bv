<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Resources\Pages\ListRecords;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

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
        return [];
    }
}

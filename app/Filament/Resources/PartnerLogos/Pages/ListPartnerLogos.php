<?php

namespace App\Filament\Resources\PartnerLogos\Pages;

use App\Filament\Resources\PartnerLogos\PartnerLogoResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerLogos extends ListRecords
{
    protected static string $resource = PartnerLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

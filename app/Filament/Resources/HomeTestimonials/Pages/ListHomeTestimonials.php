<?php

namespace App\Filament\Resources\HomeTestimonials\Pages;

use App\Filament\Resources\HomeTestimonials\HomeTestimonialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeTestimonials extends ListRecords
{
    protected static string $resource = HomeTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AboutParagraphs\Schemas;

use App\Models\AboutParagraph;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AboutParagraphForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('section')
                    ->options([
                        AboutParagraph::SECTION_OVERVIEW => 'Overview',
                        AboutParagraph::SECTION_LEAPFROG => 'LEAPFROG',
                        AboutParagraph::SECTION_CORE_VALUES_INTRO => 'Core values intro',
                    ])
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->rows(4),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_visible')
                    ->label('Visible')
                    ->default(true),
            ]);
    }
}

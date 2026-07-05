<?php

namespace App\Filament\Resources\HomeTestimonials\Schemas;

use App\Models\HomeTestimonial;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeTestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('section')
                    ->options([
                        HomeTestimonial::SECTION_CRAFTING => 'Crafting',
                        HomeTestimonial::SECTION_RECRUITERS => 'Recruiters',
                    ])
                    ->required(),
                Textarea::make('quote')
                    ->required()
                    ->rows(3),
                TextInput::make('author')
                    ->required()
                    ->maxLength(255),
                TextInput::make('role')
                    ->required()
                    ->maxLength(255),
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

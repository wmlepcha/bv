<?php

namespace App\Filament\Resources\HomeFeatureItems\Schemas;

use App\Models\HomeFeatureItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeFeatureItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('section')
                    ->options([
                        HomeFeatureItem::SECTION_CRAFTING => 'Crafting (pillars)',
                        HomeFeatureItem::SECTION_RECRUITERS => 'Recruiters (benefits)',
                    ])
                    ->required(),
                TextInput::make('label')
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

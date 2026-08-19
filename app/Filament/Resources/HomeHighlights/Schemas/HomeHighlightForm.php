<?php

namespace App\Filament\Resources\HomeHighlights\Schemas;

use App\Models\HomeHighlight;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeHighlightForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('group')
                    ->label('Column')
                    ->options(HomeHighlight::groupOptions())
                    ->required()
                    ->default(HomeHighlight::GROUP_SERVICES),
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

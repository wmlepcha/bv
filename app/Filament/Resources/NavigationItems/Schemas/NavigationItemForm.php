<?php

namespace App\Filament\Resources\NavigationItems\Schemas;

use App\Support\NavigationIcons;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NavigationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('href')
                    ->label('URL path')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('/about'),
                Select::make('icon')
                    ->options(NavigationIcons::options())
                    ->required(),
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

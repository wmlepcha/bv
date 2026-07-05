<?php

namespace App\Filament\Resources\HomeServiceCards\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeServiceCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('href')
                    ->label('URL path')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('/services/language-training'),
                TextInput::make('image_path')
                    ->label('Image path or URL')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('images/example.jpg or https://...'),
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

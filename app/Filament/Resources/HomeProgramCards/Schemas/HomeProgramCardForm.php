<?php

namespace App\Filament\Resources\HomeProgramCards\Schemas;

use App\Filament\Support\ImageUploadField;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeProgramCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
                TextInput::make('href')
                    ->label('URL path')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('/services/language-training'),
                ImageUploadField::configure(
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('images')
                        ->required()
                        ->maxFiles(1),
                ),
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

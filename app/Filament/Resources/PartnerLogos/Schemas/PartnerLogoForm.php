<?php

namespace App\Filament\Resources\PartnerLogos\Schemas;

use App\Filament\Support\ImageUploadField;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PartnerLogoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                ImageUploadField::configure(
                    FileUpload::make('image_path')
                        ->label('Logo image')
                        ->image()
                        ->disk('public')
                        ->directory('images')
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => $file->getClientOriginalName(),
                        ),
                ),
                TextInput::make('alt_text')
                    ->label('Alt text')
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

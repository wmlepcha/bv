<?php

namespace App\Filament\Resources\AboutJourneyMilestones\Schemas;

use App\Filament\Support\ImageUploadField;
use App\Models\AboutJourneyMilestone;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AboutJourneyMilestoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('ID slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('established'),
                TextInput::make('period')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('2021 – 2023'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('layout')
                    ->options([
                        AboutJourneyMilestone::LAYOUT_CENTER => 'Center',
                        AboutJourneyMilestone::LAYOUT_TEXT_LEFT => 'Text left',
                        AboutJourneyMilestone::LAYOUT_TEXT_RIGHT => 'Text right',
                    ])
                    ->required()
                    ->default(AboutJourneyMilestone::LAYOUT_CENTER),
                ImageUploadField::configure(
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('images')
                        ->maxFiles(1),
                ),
                TextInput::make('image_alt')
                    ->label('Image alt text')
                    ->maxLength(255),
                Repeater::make('bullets')
                    ->schema([
                        TextInput::make('text')
                            ->label('Bullet')
                            ->required(),
                    ])
                    ->defaultItems(0)
                    ->columnSpanFull(),
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

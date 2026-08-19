<?php

namespace App\Filament\Resources\ServiceItems\Schemas;

use App\Filament\Support\ImageUploadField;
use App\Models\ServiceItem;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ServiceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_group_id')
                    ->label('Group')
                    ->relationship('group', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                TextInput::make('family_label')
                    ->label('Family label')
                    ->helperText('Groups language variants together, e.g. "German" for Offline/Online/Hybrid.')
                    ->maxLength(255),
                Textarea::make('family_description')
                    ->label('Family description')
                    ->helperText('Optional intro shown above language variant cards. Set on one item per family.')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('kind')
                    ->options([
                        ServiceItem::KIND_COURSE => 'Course',
                        ServiceItem::KIND_PLACEMENT => 'Placement',
                    ])
                    ->required()
                    ->default(ServiceItem::KIND_COURSE)
                    ->live(),
                CheckboxList::make('delivery_modes')
                    ->label('Delivery modes')
                    ->options([
                        'offline' => 'Offline Classes',
                        'online_live' => 'Online Live Classes',
                        'hybrid' => 'Hybrid Classes',
                    ])
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE)
                    ->columnSpanFull(),
                TextInput::make('level_range')
                    ->label('Level range')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE),
                TextInput::make('hours')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE),
                TextInput::make('duration_months')
                    ->label('Duration (months)')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE),
                TagsInput::make('centres')
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE)
                    ->columnSpanFull(),
                TagsInput::make('intake_periods')
                    ->label('Intake periods')
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_COURSE)
                    ->columnSpanFull(),
                TagsInput::make('venues')
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_PLACEMENT)
                    ->columnSpanFull(),
                TextInput::make('timeline')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_PLACEMENT),
                TagsInput::make('certificates')
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_PLACEMENT)
                    ->columnSpanFull(),
                TagsInput::make('sectors')
                    ->visible(fn (Get $get): bool => $get('kind') === ServiceItem::KIND_PLACEMENT)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
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

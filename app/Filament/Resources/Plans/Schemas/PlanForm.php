<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('price_rupees')
                    ->label('Price (INR, incl. GST)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('₹'),
                TextInput::make('duration_months')
                    ->label('Duration (months)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TagsInput::make('features')
                    ->label('Feature bullets')
                    ->placeholder('Add a feature')
                    ->columnSpanFull(),
                TextInput::make('badge_text')
                    ->label('Badge text')
                    ->maxLength(255)
                    ->placeholder('Best value'),
                Toggle::make('is_recommended')
                    ->label('Recommended plan'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}

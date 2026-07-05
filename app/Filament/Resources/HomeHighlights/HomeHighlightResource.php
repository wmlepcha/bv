<?php

namespace App\Filament\Resources\HomeHighlights;

use App\Filament\Resources\HomeHighlights\Pages\CreateHomeHighlight;
use App\Filament\Resources\HomeHighlights\Pages\EditHomeHighlight;
use App\Filament\Resources\HomeHighlights\Pages\ListHomeHighlights;
use App\Filament\Resources\HomeHighlights\Schemas\HomeHighlightForm;
use App\Filament\Resources\HomeHighlights\Tables\HomeHighlightsTable;
use App\Models\HomeHighlight;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeHighlightResource extends Resource
{
    protected static ?string $model = HomeHighlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Highlights';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return HomeHighlightForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeHighlightsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeHighlights::route('/'),
            'create' => CreateHomeHighlight::route('/create'),
            'edit' => EditHomeHighlight::route('/{record}/edit'),
        ];
    }
}

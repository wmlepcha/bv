<?php

namespace App\Filament\Resources\HomeStats;

use App\Filament\Resources\HomeStats\Pages\CreateHomeStat;
use App\Filament\Resources\HomeStats\Pages\EditHomeStat;
use App\Filament\Resources\HomeStats\Pages\ListHomeStats;
use App\Filament\Resources\HomeStats\Schemas\HomeStatForm;
use App\Filament\Resources\HomeStats\Tables\HomeStatsTable;
use App\Models\HomeStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeStatResource extends Resource
{
    protected static ?string $model = HomeStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Stats';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return HomeStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeStatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeStats::route('/'),
            'create' => CreateHomeStat::route('/create'),
            'edit' => EditHomeStat::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\HomeServiceCards;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\HomeServiceCards\Pages\CreateHomeServiceCard;
use App\Filament\Resources\HomeServiceCards\Pages\EditHomeServiceCard;
use App\Filament\Resources\HomeServiceCards\Pages\ListHomeServiceCards;
use App\Filament\Resources\HomeServiceCards\Schemas\HomeServiceCardForm;
use App\Filament\Resources\HomeServiceCards\Tables\HomeServiceCardsTable;
use App\Models\HomeServiceCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeServiceCardResource extends Resource
{
    protected static ?string $model = HomeServiceCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Service Cards';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return HomeServiceCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeServiceCardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeServiceCards::route('/'),
            'create' => CreateHomeServiceCard::route('/create'),
            'edit' => EditHomeServiceCard::route('/{record}/edit'),
        ];
    }
}

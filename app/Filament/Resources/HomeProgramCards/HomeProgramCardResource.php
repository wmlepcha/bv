<?php

namespace App\Filament\Resources\HomeProgramCards;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\HomeProgramCards\Pages\CreateHomeProgramCard;
use App\Filament\Resources\HomeProgramCards\Pages\EditHomeProgramCard;
use App\Filament\Resources\HomeProgramCards\Pages\ListHomeProgramCards;
use App\Filament\Resources\HomeProgramCards\Schemas\HomeProgramCardForm;
use App\Filament\Resources\HomeProgramCards\Tables\HomeProgramCardsTable;
use App\Models\HomeProgramCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeProgramCardResource extends Resource
{
    protected static ?string $model = HomeProgramCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Program Cards';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return HomeProgramCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeProgramCardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeProgramCards::route('/'),
            'create' => CreateHomeProgramCard::route('/create'),
            'edit' => EditHomeProgramCard::route('/{record}/edit'),
        ];
    }
}

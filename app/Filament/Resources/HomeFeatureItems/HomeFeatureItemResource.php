<?php

namespace App\Filament\Resources\HomeFeatureItems;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\HomeFeatureItems\Pages\CreateHomeFeatureItem;
use App\Filament\Resources\HomeFeatureItems\Pages\EditHomeFeatureItem;
use App\Filament\Resources\HomeFeatureItems\Pages\ListHomeFeatureItems;
use App\Filament\Resources\HomeFeatureItems\Schemas\HomeFeatureItemForm;
use App\Filament\Resources\HomeFeatureItems\Tables\HomeFeatureItemsTable;
use App\Models\HomeFeatureItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeFeatureItemResource extends Resource
{
    protected static ?string $model = HomeFeatureItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Features';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return HomeFeatureItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeFeatureItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeFeatureItems::route('/'),
            'create' => CreateHomeFeatureItem::route('/create'),
            'edit' => EditHomeFeatureItem::route('/{record}/edit'),
        ];
    }
}

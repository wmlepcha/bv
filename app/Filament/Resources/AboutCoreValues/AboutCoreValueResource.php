<?php

namespace App\Filament\Resources\AboutCoreValues;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\AboutCoreValues\Pages\CreateAboutCoreValue;
use App\Filament\Resources\AboutCoreValues\Pages\EditAboutCoreValue;
use App\Filament\Resources\AboutCoreValues\Pages\ListAboutCoreValues;
use App\Filament\Resources\AboutCoreValues\Schemas\AboutCoreValueForm;
use App\Filament\Resources\AboutCoreValues\Tables\AboutCoreValuesTable;
use App\Models\AboutCoreValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AboutCoreValueResource extends Resource
{
    protected static ?string $model = AboutCoreValue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'About Page';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Core Values';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AboutCoreValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutCoreValuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAboutCoreValues::route('/'),
            'create' => CreateAboutCoreValue::route('/create'),
            'edit' => EditAboutCoreValue::route('/{record}/edit'),
        ];
    }
}

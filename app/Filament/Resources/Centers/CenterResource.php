<?php

namespace App\Filament\Resources\Centers;

use App\Filament\Resources\Centers\Pages\CreateCenter;
use App\Filament\Resources\Centers\Pages\EditCenter;
use App\Filament\Resources\Centers\Pages\ListCenters;
use App\Filament\Resources\Centers\Schemas\CenterForm;
use App\Filament\Resources\Centers\Tables\CentersTable;
use App\Models\Center;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CenterResource extends Resource
{
    protected static ?string $model = Center::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Training';

    protected static ?string $navigationLabel = 'Centers';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return CenterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCenters::route('/'),
            'create' => CreateCenter::route('/create'),
            'edit' => EditCenter::route('/{record}/edit'),
        ];
    }
}

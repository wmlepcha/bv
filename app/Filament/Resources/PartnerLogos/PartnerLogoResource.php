<?php

namespace App\Filament\Resources\PartnerLogos;

use App\Filament\Resources\PartnerLogos\Pages\CreatePartnerLogo;
use App\Filament\Resources\PartnerLogos\Pages\EditPartnerLogo;
use App\Filament\Resources\PartnerLogos\Pages\ListPartnerLogos;
use App\Filament\Resources\PartnerLogos\Schemas\PartnerLogoForm;
use App\Filament\Resources\PartnerLogos\Tables\PartnerLogosTable;
use App\Models\PartnerLogo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PartnerLogoResource extends Resource
{
    protected static ?string $model = PartnerLogo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Header';

    protected static ?string $navigationLabel = 'Partner Logos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PartnerLogoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerLogosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerLogos::route('/'),
            'create' => CreatePartnerLogo::route('/create'),
            'edit' => EditPartnerLogo::route('/{record}/edit'),
        ];
    }
}

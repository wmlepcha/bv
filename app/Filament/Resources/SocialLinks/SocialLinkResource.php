<?php

namespace App\Filament\Resources\SocialLinks;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\SocialLinks\Pages\CreateSocialLink;
use App\Filament\Resources\SocialLinks\Pages\EditSocialLink;
use App\Filament\Resources\SocialLinks\Pages\ListSocialLinks;
use App\Filament\Resources\SocialLinks\Schemas\SocialLinkForm;
use App\Filament\Resources\SocialLinks\Tables\SocialLinksTable;
use App\Models\SocialLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SocialLinkResource extends Resource
{
    protected static ?string $model = SocialLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static string|UnitEnum|null $navigationGroup = 'Footer';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Social Links';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SocialLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SocialLinksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialLinks::route('/'),
            'create' => CreateSocialLink::route('/create'),
            'edit' => EditSocialLink::route('/{record}/edit'),
        ];
    }
}

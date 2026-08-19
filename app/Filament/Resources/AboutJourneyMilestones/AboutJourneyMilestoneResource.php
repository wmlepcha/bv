<?php

namespace App\Filament\Resources\AboutJourneyMilestones;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Resources\AboutJourneyMilestones\Pages\CreateAboutJourneyMilestone;
use App\Filament\Resources\AboutJourneyMilestones\Pages\EditAboutJourneyMilestone;
use App\Filament\Resources\AboutJourneyMilestones\Pages\ListAboutJourneyMilestones;
use App\Filament\Resources\AboutJourneyMilestones\Schemas\AboutJourneyMilestoneForm;
use App\Filament\Resources\AboutJourneyMilestones\Tables\AboutJourneyMilestonesTable;
use App\Models\AboutJourneyMilestone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AboutJourneyMilestoneResource extends Resource
{
    protected static ?string $model = AboutJourneyMilestone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'About Page';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Journey Milestones';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AboutJourneyMilestoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutJourneyMilestonesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAboutJourneyMilestones::route('/'),
            'create' => CreateAboutJourneyMilestone::route('/create'),
            'edit' => EditAboutJourneyMilestone::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\RecruiterProjects;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\RecruiterProjects\Pages\ListRecruiterProjects;
use App\Filament\Resources\RecruiterProjects\Pages\ViewRecruiterProject;
use App\Filament\Resources\RecruiterProjects\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\RecruiterProjects\Schemas\RecruiterProjectInfolist;
use App\Filament\Resources\RecruiterProjects\Tables\RecruiterProjectsTable;
use App\Models\RecruiterProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RecruiterProjectResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = RecruiterProject::class;

    protected static ?string $slug = 'recruiter-projects';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Placements';

    protected static ?string $navigationLabel = 'Placement projects';

    protected static ?string $modelLabel = 'Placement project';

    protected static ?int $navigationSort = 1;

    /**
     * Only projects the recruiter has sent to BV.
     *
     * @return Builder<RecruiterProject>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('finalized_at');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = RecruiterProject::query()
            ->whereNotNull('finalized_at')
            ->whereNotNull('recruiter_updated_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecruiterProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruiterProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruiterProjects::route('/'),
            'view' => ViewRecruiterProject::route('/{record}'),
        ];
    }
}

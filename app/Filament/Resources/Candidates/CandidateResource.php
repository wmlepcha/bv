<?php

namespace App\Filament\Resources\Candidates;

use App\Enums\UserRole;
use App\Filament\Clusters\CandidatesCluster;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Schemas\CandidateForm;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CandidateResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'candidates';

    protected static ?string $cluster = CandidatesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Candidates';

    protected static ?string $modelLabel = 'Candidate';

    protected static ?string $pluralModelLabel = 'Candidates';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Student);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CandidatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidates::route('/'),
            'edit' => EditCandidate::route('/{record}/edit'),
        ];
    }
}

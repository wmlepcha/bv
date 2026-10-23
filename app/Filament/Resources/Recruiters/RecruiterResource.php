<?php

namespace App\Filament\Resources\Recruiters;

use App\Enums\UserRole;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Recruiters\Pages\CreateRecruiter;
use App\Filament\Resources\Recruiters\Pages\EditRecruiter;
use App\Filament\Resources\Recruiters\Pages\ListRecruiters;
use App\Filament\Resources\Users\Schemas\IdentityUserForm;
use App\Filament\Resources\Users\Tables\IdentityUsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RecruiterResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'recruiters';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Recruiters';

    protected static ?string $modelLabel = 'Recruiter';

    protected static ?string $pluralModelLabel = 'Recruiters';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Recruiter);
    }

    public static function form(Schema $schema): Schema
    {
        return IdentityUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdentityUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruiters::route('/'),
            'create' => CreateRecruiter::route('/create'),
            'edit' => EditRecruiter::route('/{record}/edit'),
        ];
    }
}

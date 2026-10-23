<?php

namespace App\Filament\Resources\RecruitmentManagers;

use App\Enums\UserRole;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\RecruitmentManagers\Pages\CreateRecruitmentManager;
use App\Filament\Resources\RecruitmentManagers\Pages\EditRecruitmentManager;
use App\Filament\Resources\RecruitmentManagers\Pages\ListRecruitmentManagers;
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

class RecruitmentManagerResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'recruitment-managers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Recruitment Managers';

    protected static ?string $modelLabel = 'Recruitment Manager';

    protected static ?string $pluralModelLabel = 'Recruitment Managers';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::RecruitmentManager);
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
            'index' => ListRecruitmentManagers::route('/'),
            'create' => CreateRecruitmentManager::route('/create'),
            'edit' => EditRecruitmentManager::route('/{record}/edit'),
        ];
    }
}

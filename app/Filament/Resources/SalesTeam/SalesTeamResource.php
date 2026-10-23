<?php

namespace App\Filament\Resources\SalesTeam;

use App\Enums\UserRole;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\SalesTeam\Pages\CreateSalesTeamMember;
use App\Filament\Resources\SalesTeam\Pages\EditSalesTeamMember;
use App\Filament\Resources\SalesTeam\Pages\ListSalesTeamMembers;
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

class SalesTeamResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'sales-team';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Sales Team Members';

    protected static ?string $modelLabel = 'Sales Team Member';

    protected static ?string $pluralModelLabel = 'Sales Team Members';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::SalesTeam);
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
            'index' => ListSalesTeamMembers::route('/'),
            'create' => CreateSalesTeamMember::route('/create'),
            'edit' => EditSalesTeamMember::route('/{record}/edit'),
        ];
    }
}

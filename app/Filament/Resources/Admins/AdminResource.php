<?php

namespace App\Filament\Resources\Admins;

use App\Enums\UserRole;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
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

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'admins';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Admins';

    protected static ?string $modelLabel = 'Admin';

    protected static ?string $pluralModelLabel = 'Admins';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Admin);
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
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}

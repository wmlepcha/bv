<?php

namespace App\Filament\Resources\Teachers;

use App\Enums\UserRole;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
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

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'teachers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Trainers / Teachers';

    protected static ?string $modelLabel = 'Trainer / Teacher';

    protected static ?string $pluralModelLabel = 'Trainers / Teachers';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Teacher);
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
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}

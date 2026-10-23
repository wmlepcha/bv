<?php

namespace App\Filament\Resources\TrainingManagers;

use App\Enums\UserRole;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\TrainingManagers\Pages\CreateTrainingManager;
use App\Filament\Resources\TrainingManagers\Pages\EditTrainingManager;
use App\Filament\Resources\TrainingManagers\Pages\ListTrainingManagers;
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

class TrainingManagerResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'training-managers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Training Managers';

    protected static ?string $modelLabel = 'Training Manager';

    protected static ?string $pluralModelLabel = 'Training Managers';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::TrainingManager);
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
            'index' => ListTrainingManagers::route('/'),
            'create' => CreateTrainingManager::route('/create'),
            'edit' => EditTrainingManager::route('/{record}/edit'),
        ];
    }
}

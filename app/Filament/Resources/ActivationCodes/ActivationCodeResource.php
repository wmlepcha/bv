<?php

namespace App\Filament\Resources\ActivationCodes;

use App\Filament\Clusters\CandidatesCluster;
use App\Filament\Resources\ActivationCodes\Pages\CreateActivationCode;
use App\Filament\Resources\ActivationCodes\Pages\EditActivationCode;
use App\Filament\Resources\ActivationCodes\Pages\ListActivationCodes;
use App\Filament\Resources\ActivationCodes\Schemas\ActivationCodeForm;
use App\Filament\Resources\ActivationCodes\Tables\ActivationCodesTable;
use App\Models\ActivationCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActivationCodeResource extends Resource
{
    protected static ?string $model = ActivationCode::class;

    protected static ?string $cluster = CandidatesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Activation Codes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ActivationCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivationCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivationCodes::route('/'),
            'create' => CreateActivationCode::route('/create'),
            'edit' => EditActivationCode::route('/{record}/edit'),
        ];
    }
}

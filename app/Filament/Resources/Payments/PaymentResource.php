<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Clusters\PaymentsCluster;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $cluster = PaymentsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    /**
     * Offline payments are recorded by admins and the sales team (see RecordOfflinePaymentAction).
     */
    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();

        return $user?->isAdmin() === true || $user?->isSalesTeam() === true;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * The sales team can look at payments but only admins can remove them.
     */
    public static function canDelete($record): bool
    {
        return Filament::auth()->user()?->isAdmin() === true;
    }

    public static function canDeleteAny(): bool
    {
        return Filament::auth()->user()?->isAdmin() === true;
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentsCluster extends Cluster
{
    /**
     * The cluster page itself is only reachable when at least one page inside it is.
     */
    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?int $navigationSort = 5;
}

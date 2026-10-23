<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CandidatesCluster extends Cluster
{
    /**
     * The cluster page itself is only reachable when at least one page inside it is.
     */
    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Candidates';

    protected static ?int $navigationSort = 1;
}

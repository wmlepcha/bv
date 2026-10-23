<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FrontendEditsCluster extends Cluster
{
    /**
     * The cluster page itself is only reachable when at least one page inside it is.
     */
    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'FrontendEdits';
}

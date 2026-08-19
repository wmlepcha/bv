<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FrontendEditsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'FrontendEdits';
}

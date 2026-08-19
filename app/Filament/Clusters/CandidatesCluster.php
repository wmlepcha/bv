<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CandidatesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Candidates';

    protected static ?int $navigationSort = 1;
}

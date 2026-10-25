<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;

class ComplianceAndSecurity extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Compliance & Security';
}

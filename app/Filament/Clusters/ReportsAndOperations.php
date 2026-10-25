<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;

class ReportsAndOperations extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports & Operations';
}

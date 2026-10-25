<?php

namespace App\Filament\Clusters;

use App\Filament\Resources\GroupApprovalRequests\GroupApprovalRequestResource;
use BackedEnum;
use Filament\Clusters\Cluster;

class AccessManagement extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Access Management';

}

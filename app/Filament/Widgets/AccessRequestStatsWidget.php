<?php

namespace App\Filament\Widgets;

use App\Models\GroupApprovalRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AccessRequestStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool) ($user && ! $user->isOperator());
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        $query = GroupApprovalRequest::query();
        if ($user && ! $user->isAdmin()) {
            $query->where('approver_email', $user->email);
        }

        $pendingCount = (clone $query)->where('status', 'PENDING')->count();
        $approvedCount = (clone $query)->where('status', 'APPROVED')->count();
        $rejectedCount = (clone $query)->where('status', 'REJECTED')->count();

        return [
            Stat::make('Pending Approvals', $pendingCount)
                ->description('Awaiting supervisor review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Grants', $approvedCount)
                ->description('Provisioned to Active Directory')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected Requests', $rejectedCount)
                ->description('Declined by supervisor')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}

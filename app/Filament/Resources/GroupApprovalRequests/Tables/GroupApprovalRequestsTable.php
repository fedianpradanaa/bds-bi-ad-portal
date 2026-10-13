<?php

namespace App\Filament\Resources\GroupApprovalRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GroupApprovalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Access Requests Found')
            ->emptyStateDescription('Access requests will appear here.')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date Requested')
                    ->icon('heroicon-m-calendar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->icon('heroicon-m-shield-check')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('target_username')
                    ->label('Target User')
                    ->icon('heroicon-m-user')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('target_group_name')
                    ->label('Target Group')
                    ->icon('heroicon-m-user-group')
                    ->badge()
                    ->color('warning')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'EXPIRED' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        'EXPIRED' => 'Expired',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Proceed / View')
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-m-arrow-right-circle'),
            ]);
    }
}

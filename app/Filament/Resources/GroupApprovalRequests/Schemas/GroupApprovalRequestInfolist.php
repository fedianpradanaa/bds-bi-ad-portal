<?php

namespace App\Filament\Resources\GroupApprovalRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GroupApprovalRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        // Left Column: Target Scope & Business Justification (2 columns)
                        Section::make('Active Directory Provisioning Details')
                            ->columnSpan(['default' => 3, 'lg' => 2])
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('target_username')
                                            ->label('Target AD User Account')
                                            ->icon('heroicon-m-user')
                                            ->badge()
                                            ->color('info')
                                            ->copyable(),

                                        TextEntry::make('target_group_name')
                                            ->label('Requested Security Group')
                                            ->icon('heroicon-m-user-group')
                                            ->badge()
                                            ->color('warning')
                                            ->copyable(),
                                    ]),

                                TextEntry::make('business_justification')
                                    ->label('Operational & Business Justification')
                                    ->columnSpanFull()
                                    ->placeholder('No justification specified.'),

                                TextEntry::make('rejection_reason')
                                    ->label('Rejection Justification')
                                    ->visible(fn ($record) => ! empty($record->rejection_reason))
                                    ->color('danger')
                                    ->columnSpanFull(),
                            ]),

                        // Right Column: Governance, Status & Timestamps (1 column)
                        Section::make('Request Governance & Status')
                            ->columnSpan(['default' => 3, 'lg' => 1])
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Approval Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'PENDING' => 'warning',
                                        'APPROVED' => 'success',
                                        'REJECTED' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('requester_name')
                                    ->label('Initiated By (Admin)')
                                    ->icon('heroicon-m-shield-check'),

                                TextEntry::make('approver_email')
                                    ->label('Designated Approver')
                                    ->icon('heroicon-m-envelope')
                                    ->placeholder('Any Authorized Supervisor')
                                    ->copyable(),

                                TextEntry::make('created_at')
                                    ->label('Date Requested')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('actioned_at')
                                    ->label('Action Completed At')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-check-badge')
                                    ->visible(fn ($record) => ! empty($record->actioned_at)),
                            ]),
                    ]),
            ]);
    }
}

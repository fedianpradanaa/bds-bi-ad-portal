<?php

namespace App\Filament\Resources\UserCreationRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserCreationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        // Left Column: User Details (2 columns)
                        Section::make('User Details')
                            ->columnSpan(['default' => 3, 'lg' => 2])
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('display_name')
                                            ->label('Display Name')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->copyable(),

                                        TextEntry::make('username')
                                            ->label('Username')
                                            ->icon('heroicon-m-at-symbol')
                                            ->badge()
                                            ->color('info')
                                            ->copyable(),

                                        TextEntry::make('initial_group_name')
                                            ->label('Requested Group')
                                            ->icon('heroicon-m-user-group')
                                            ->badge()
                                            ->color('warning'),

                                        TextEntry::make('initial_password')
                                            ->label('Initial Password')
                                            ->icon('heroicon-m-key')
                                            ->formatStateUsing(function ($record) {
                                                if ($record->status === 'APPROVED') {
                                                    return '[REDACTED - Must Change on 1st Logon]';
                                                }

                                                return '•••••••••••• (Encrypted at Rest)';
                                            }),
                                    ]),

                                TextEntry::make('business_justification')
                                    ->label('Operational Justification')
                                    ->columnSpanFull(),

                                TextEntry::make('rejection_reason')
                                    ->label('Rejection Justification')
                                    ->visible(fn ($record) => ! empty($record->rejection_reason))
                                    ->color('danger')
                                    ->columnSpanFull(),
                            ]),

                        // Right Column: Governance & Status Metadata (1 column)
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

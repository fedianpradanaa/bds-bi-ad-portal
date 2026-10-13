<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Audit Trail Details')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('action')
                                    ->label('Security Action')
                                    ->badge()
                                    ->color(fn (string $state): string => match (true) {
                                        str_contains($state, 'APPROVED') || str_contains($state, 'CREATED') => 'success',
                                        str_contains($state, 'REJECTED') || str_contains($state, 'FAILED') => 'danger',
                                        default => 'primary',
                                    }),

                                TextEntry::make('actor')
                                    ->label('Initiated By / Actor')
                                    ->icon('heroicon-m-user')
                                    ->copyable(),

                                TextEntry::make('ip_address')
                                    ->label('Client IP Address')
                                    ->icon('heroicon-m-globe-alt')
                                    ->placeholder('127.0.0.1')
                                    ->copyable(),

                                TextEntry::make('created_at')
                                    ->label('Timestamp')
                                    ->dateTime('d M Y, H:i:s')
                                    ->icon('heroicon-m-clock'),

                                TextEntry::make('group_approval_request_id')
                                    ->label('Related Group Request ID')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('user_creation_request_id')
                                    ->label('Related User Creation ID')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('gray'),
                            ]),

                        TextEntry::make('details')
                            ->label('Action Details & Execution Log')
                            ->placeholder('No additional payload details.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

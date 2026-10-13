<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Security Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'REQUEST_CREATED' => 'info',
                        'APPROVED' => 'success',
                        'REJECTED' => 'warning',
                        'LDAP_SUCCESS' => 'success',
                        'LDAP_FAILED' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('actor')
                    ->label('Actor / Initiator')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('approvalRequest.target_username')
                    ->label('Target AD User')
                    ->badge()
                    ->color('info')
                    ->placeholder('N/A')
                    ->searchable(),

                TextColumn::make('approvalRequest.target_group_name')
                    ->label('Target Group')
                    ->badge()
                    ->color('warning')
                    ->placeholder('N/A')
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),

                TextColumn::make('details')
                    ->label('Log Details / Response')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->details),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'REQUEST_CREATED' => 'Request Created',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        'LDAP_SUCCESS' => 'LDAP Success',
                        'LDAP_FAILED' => 'LDAP Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions for immutable audit trail
            ]);
    }
}

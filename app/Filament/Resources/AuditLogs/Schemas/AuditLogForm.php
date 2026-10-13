<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('group_approval_request_id')
                    ->numeric(),
                TextInput::make('action')
                    ->required(),
                TextInput::make('actor')
                    ->required(),
                TextInput::make('ip_address'),
                Textarea::make('details')
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\GroupApprovalRequests\Pages;

use App\Filament\Resources\GroupApprovalRequests\GroupApprovalRequestResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateGroupApprovalRequest extends CreateRecord
{
    protected static string $resource = GroupApprovalRequestResource::class;

    protected static bool $canCreateAnother = false;

    public function canCreateAnother(): bool
    {
        return false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::SevenExtraLarge;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requester_id'] = Auth::id();
        $data['requester_name'] = Auth::user()?->name ?? 'Portal Admin';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        AuditLog::logAction(
            action: 'REQUEST_CREATED',
            actor: Auth::user()?->email ?? $record->requester_name,
            requestId: $record->id,
            details: "Request created to assign '{$record->target_username}' to '{$record->target_group_name}'. Assigned to approver: {$record->approver_email}."
        );
    }
}

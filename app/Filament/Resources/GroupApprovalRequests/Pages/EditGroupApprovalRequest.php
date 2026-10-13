<?php

namespace App\Filament\Resources\GroupApprovalRequests\Pages;

use App\Filament\Resources\GroupApprovalRequests\GroupApprovalRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGroupApprovalRequest extends EditRecord
{
    protected static string $resource = GroupApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

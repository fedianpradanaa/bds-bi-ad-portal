<?php

namespace App\Filament\Resources\GroupApprovalRequests\Pages;

use App\Filament\Resources\GroupApprovalRequests\GroupApprovalRequestResource;
use App\Models\AuditLog;
use App\Models\GroupApprovalRequest;
use App\Services\ActiveDirectoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewGroupApprovalRequest extends ViewRecord
{
    protected static string $resource = GroupApprovalRequestResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            // ACTION: APPROVE (Supervisor ONLY - Admin strictly prohibited)
            Action::make('approve')
                ->label('Approve Request')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->canBeApprovedBy(Auth::user()))
                ->requiresConfirmation()
                ->modalHeading('Approve AD Access Request')
                ->modalDescription(fn (): string => "Authorize adding user '{$this->record->target_username}' to security group '{$this->record->target_group_name}' in Active Directory?")
                ->modalSubmitActionLabel('Confirm Approval')
                ->action(function (ActiveDirectoryService $adService) {
                    DB::transaction(function () use ($adService) {
                        $lockedRecord = GroupApprovalRequest::where('id', $this->record->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lockedRecord->isExpired()) {
                            Notification::make()
                                ->title('Request Expired')
                                ->body('This access request has expired and can no longer be approved.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($lockedRecord->status !== 'PENDING') {
                            Notification::make()
                                ->title('Already Actioned')
                                ->body("This request has already been {$lockedRecord->status}.")
                                ->warning()
                                ->send();

                            return;
                        }

                        if (! $lockedRecord->canBeApprovedBy(Auth::user())) {
                            Notification::make()
                                ->title('Segregation of Duties (SoD) Violation')
                                ->body('Action forbidden: Requesters and unauthorized users are strictly prohibited from approving requests. Secondary supervisor approval is required.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            // Execute LDAP assignment against Domain Controller
                            $adService->assignUserToGroup($lockedRecord->target_username, $lockedRecord->target_group_name);

                            $lockedRecord->update([
                                'status' => 'APPROVED',
                                'actioned_at' => now(),
                                'actioned_by_ip' => request()->ip(),
                            ]);

                            AuditLog::logAction(
                                action: 'APPROVED',
                                actor: Auth::user()?->email ?? 'Supervisor',
                                requestId: $lockedRecord->id,
                                details: "Successfully added {$lockedRecord->target_username} to {$lockedRecord->target_group_name} via Web Portal."
                            );

                            Notification::make()
                                ->title('Access Request Approved')
                                ->body("User {$lockedRecord->target_username} was successfully assigned to {$lockedRecord->target_group_name} in Active Directory.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            AuditLog::logAction(
                                action: 'LDAP_FAILED',
                                actor: Auth::user()?->email ?? 'Supervisor',
                                requestId: $lockedRecord->id,
                                details: 'LDAP sync failed: '.$e->getMessage()
                            );

                            Notification::make()
                                ->title('Provisioning Error')
                                ->body('Failed to assign user in Active Directory: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    });
                }),

            // ACTION: REJECT / DENY (Supervisor ONLY)
            Action::make('reject')
                ->label('Deny Request')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canBeApprovedBy(Auth::user()))
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Mandatory Rejection Justification')
                        ->placeholder('Provide reason for denying this access request...')
                        ->required()
                        ->minLength(5)
                        ->rows(3),
                ])
                ->modalHeading('Deny AD Access Request')
                ->modalSubmitActionLabel('Confirm Denial')
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $lockedRecord = GroupApprovalRequest::where('id', $this->record->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lockedRecord->isExpired()) {
                            Notification::make()
                                ->title('Request Expired')
                                ->body('This access request has expired.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($lockedRecord->status !== 'PENDING') {
                            Notification::make()
                                ->title('Already Actioned')
                                ->body("This request has already been {$lockedRecord->status}.")
                                ->warning()
                                ->send();

                            return;
                        }

                        if (! $lockedRecord->canBeApprovedBy(Auth::user())) {
                            Notification::make()
                                ->title('Segregation of Duties (SoD) Violation')
                                ->body('Action forbidden: Requesters and unauthorized users are strictly prohibited from rejecting requests. Secondary supervisor approval is required.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $lockedRecord->update([
                            'status' => 'REJECTED',
                            'rejection_reason' => $data['rejection_reason'],
                            'actioned_at' => now(),
                            'actioned_by_ip' => request()->ip(),
                        ]);

                        AuditLog::logAction(
                            action: 'REJECTED',
                            actor: Auth::user()?->email ?? 'Supervisor',
                            requestId: $lockedRecord->id,
                            details: "Request rejected with reason: {$data['rejection_reason']}"
                        );

                        Notification::make()
                            ->title('Access Request Denied')
                            ->body('The request has been rejected.')
                            ->warning()
                            ->send();
                    });
                }),
        ];
    }
}

<?php

namespace App\Filament\Resources\UserCreationRequests\Pages;

use App\Filament\Resources\UserCreationRequests\UserCreationRequestResource;
use App\Models\AuditLog;
use App\Models\UserCreationRequest;
use App\Services\ActiveDirectoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ViewUserCreationRequest extends ViewRecord
{
    protected static string $resource = UserCreationRequestResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            // ACTION: APPROVE (Supervisor ONLY - Admin strictly prohibited)
            Action::make('approve')
                ->label('Approve & Create Account')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->canBeApprovedBy(Auth::user()))
                ->requiresConfirmation()
                ->modalHeading('Approve User Account Creation')
                ->modalDescription(fn (): string => "Authorize creating new user '{$this->record->display_name}' ({$this->record->username}) and assigning to '{$this->record->initial_group_name}'?")
                ->modalSubmitActionLabel('Confirm & Provision')
                ->action(function (ActiveDirectoryService $adService) {
                    DB::transaction(function () use ($adService) {
                        $lockedRecord = UserCreationRequest::where('id', $this->record->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lockedRecord->isExpired()) {
                            Notification::make()
                                ->title('Request Expired')
                                ->body('This user creation request has expired and can no longer be approved.')
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
                            // Execute user creation & group assignment
                            $adService->createUserAccount(
                                username: $lockedRecord->username,
                                displayName: $lockedRecord->display_name,
                                password: $lockedRecord->initial_password,
                                initialGroup: $lockedRecord->initial_group_name,
                                firstName: $lockedRecord->first_name,
                                lastName: $lockedRecord->last_name
                            );

                            $lockedRecord->update([
                                'status' => 'APPROVED',
                                'initial_password' => null, // Zero Persistence: Purge password from database once provisioned
                                'actioned_at' => now(),
                                'actioned_by_ip' => request()->ip(),
                            ]);

                            AuditLog::logAction(
                                action: 'USER_CREATED_AND_ASSIGNED',
                                actor: Auth::user()?->email ?? Auth::user()?->username ?? 'SUPERVISOR',
                                details: "Provisioned account '{$lockedRecord->display_name}' ({$lockedRecord->username}) and added to group '{$lockedRecord->initial_group_name}'.",
                                userCreationRequestId: $lockedRecord->id
                            );

                            Notification::make()
                                ->title('Account Provisioned Successfully')
                                ->body("User '{$lockedRecord->display_name}' was created in Active Directory and added to {$lockedRecord->initial_group_name}.")
                                ->success()
                                ->send();

                            $this->refreshFormData(['status', 'actioned_at']);
                        } catch (Throwable $e) {
                            AuditLog::logAction(
                                action: 'LDAP_CREATION_FAILED',
                                actor: Auth::user()?->email ?? Auth::user()?->username ?? 'SUPERVISOR',
                                details: "Failed to provision '{$lockedRecord->username}': ".$e->getMessage(),
                                userCreationRequestId: $lockedRecord->id
                            );

                            Notification::make()
                                ->title('AD Provisioning Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    });
                }),

            // ACTION: DENY (Supervisor ONLY - Admin strictly prohibited)
            Action::make('deny')
                ->label('Deny Request')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canBeApprovedBy(Auth::user()))
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Mandatory Rejection Justification')
                        ->placeholder('Explain why this new account request is rejected...')
                        ->required()
                        ->minLength(5)
                        ->rows(3),
                ])
                ->modalHeading('Deny User Creation Request')
                ->modalSubmitActionLabel('Confirm Denial')
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $lockedRecord = UserCreationRequest::where('id', $this->record->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lockedRecord->isExpired()) {
                            Notification::make()
                                ->title('Request Expired')
                                ->body('This user creation request has expired.')
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
                                ->body('Action forbidden: Requesters and unauthorized users are strictly prohibited from denying requests. Secondary supervisor approval is required.')
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
                            action: 'USER_CREATION_REJECTED',
                            actor: Auth::user()?->email ?? Auth::user()?->username ?? 'SUPERVISOR',
                            details: 'Account creation rejected: '.$data['rejection_reason'],
                            userCreationRequestId: $lockedRecord->id
                        );

                        Notification::make()
                            ->title('Request Denied')
                            ->body("User creation request for '{$lockedRecord->display_name}' has been rejected.")
                            ->warning()
                            ->send();

                        $this->refreshFormData(['status', 'rejection_reason', 'actioned_at']);
                    });
                }),
        ];
    }
}

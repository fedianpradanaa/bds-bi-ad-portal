<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Rules\ActiveDirectoryPasswordRule;
use App\Services\ActiveDirectoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provision_ad')
                ->label('Provision to AD')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->visible(function () {
                    try {
                        return ! app(ActiveDirectoryService::class)->userExists($this->record->username);
                    } catch (\Throwable) {
                        return true;
                    }
                })
                ->modalHeading("Provision '{$this->record->name}' to Active Directory")
                ->modalDescription('Create this user in the Active Directory Domain Controller and attach them to the appropriate security group.')
                ->form([
                    TextInput::make('password')
                        ->label('Initial AD Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rules([
                            fn () => new ActiveDirectoryPasswordRule(
                                username: $this->record->username,
                                name: $this->record->name
                            ),
                        ])
                        ->default('Bds@Supervisor2026')
                        ->helperText('Initial password for Active Directory domain login (must not contain name or username).'),
                ])
                ->action(function (array $data) {
                    $user = $this->record;
                    $adService = app(ActiveDirectoryService::class);
                    $adGroup = match ($user->role) {
                        'admin', 'superadmin', 'super_admin' => 'BDS_Admin',
                        'supervisor' => 'BDS_Supervisor',
                        default => 'BDS_Supervisor',
                    };

                    try {
                        $adService->createUserAccount(
                            username: $user->username,
                            displayName: $user->name,
                            password: $data['password'],
                            initialGroup: $adGroup,
                            email: $user->email
                        );

                        $user->update([
                            'password' => Hash::make($data['password']),
                        ]);

                        AuditLog::logAction(
                            action: 'PORTAL_USER_PROVISIONED_AD',
                            actor: Auth::user()?->email ?? 'Super Administrator',
                            requestId: null,
                            details: "Superadmin provisioned '{$user->name}' ({$user->username}) into Active Directory with group '{$adGroup}'."
                        );

                        Notification::make()
                            ->title('Active Directory Provisioning Successful')
                            ->body("User '{$user->username}' ({$user->name}) is now live in Active Directory and added to group '{$adGroup}'.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Active Directory Provisioning Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => (int) $this->record->id !== (int) Auth::id()),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->record;

        $roleName = match ($user->role) {
            'superadmin', 'super_admin' => 'super_admin',
            'admin' => 'admin',
            'supervisor' => 'supervisor',
            default => null,
        };

        if ($roleName) {
            $spatieRole = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $user->syncRoles([$spatieRole]);
            $user->update([
                'is_admin' => in_array($roleName, ['admin', 'super_admin']),
            ]);
        }

        $adService = app(ActiveDirectoryService::class);
        $adGroup = match ($user->role) {
            'admin', 'superadmin', 'super_admin' => 'BDS_Admin',
            'supervisor' => 'BDS_Supervisor',
            default => null,
        };

        // If password was updated in form
        if (! empty($this->data['password'])) {
            try {
                if ($adService->userExists($user->username)) {
                    $adService->updateUserPassword($user->username, $this->data['password']);
                } else {
                    $adService->createUserAccount(
                        username: $user->username,
                        displayName: $user->name,
                        password: $this->data['password'],
                        initialGroup: $adGroup,
                        email: $user->email
                    );
                }
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Active Directory Sync Notice')
                    ->body('Local user saved, but Active Directory sync encountered an issue: '.$e->getMessage())
                    ->warning()
                    ->persistent()
                    ->send();
            }
        } elseif ($adGroup && $adService->userExists($user->username)) {
            try {
                $adService->assignUserToGroup($user->username, $adGroup);
            } catch (\Throwable) {
                // Ignore if already member
            }
        }
    }
}

<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Services\ActiveDirectoryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function canCreateAnother(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? 'supervisor';
        $adGroup = match ($role) {
            'admin', 'superadmin', 'super_admin' => 'BDS_Admin',
            'supervisor' => 'BDS_Supervisor',
            default => null,
        };

        $adService = app(ActiveDirectoryService::class);

        // Provision to Active Directory Domain Controller
        try {
            $adService->createUserAccount(
                username: $data['username'],
                displayName: $data['name'],
                password: $data['password'],
                initialGroup: $adGroup,
                email: $data['email'] ?? null
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Active Directory Provisioning Error')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'password' => 'Active Directory provisioning failed: '.$e->getMessage(),
            ]);
        }

        // Create local user in database
        $model = static::getModel();
        $record = new $model($data);
        $record->save();

        return $record;
    }

    protected function afterCreate(): void
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

        AuditLog::logAction(
            action: 'PORTAL_USER_CREATED',
            actor: Auth::user()?->email ?? 'Super Administrator',
            requestId: null,
            details: "Superadmin provisioned portal & AD user '{$user->name}' ({$user->username}) with role '{$user->role}'."
        );
    }
}

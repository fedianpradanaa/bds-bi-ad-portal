<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserCreationRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserCreationRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:UserCreationRequest') || $user->isAdmin() || $user->isSupervisor();
    }

    public function view(User $user, UserCreationRequest $request): bool
    {
        return $user->can('View:UserCreationRequest') || $user->isAdmin() || $user->isSupervisor();
    }

    public function create(User $user): bool
    {
        return $user->can('Create:UserCreationRequest') || $user->isAdmin();
    }

    public function update(User $user, UserCreationRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, UserCreationRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, UserCreationRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, UserCreationRequest $request): bool
    {
        return $user->isAdmin();
    }
}

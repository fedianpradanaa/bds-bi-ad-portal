<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GroupApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupApprovalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:GroupApprovalRequest') || $user->isAdmin() || $user->isSupervisor();
    }

    public function view(User $user, GroupApprovalRequest $groupApprovalRequest): bool
    {
        return $user->can('View:GroupApprovalRequest') || $user->isAdmin() || $user->isSupervisor();
    }

    public function create(User $user): bool
    {
        return $user->can('Create:GroupApprovalRequest') || $user->isAdmin();
    }

    public function update(User $user, GroupApprovalRequest $groupApprovalRequest): bool
    {
        return false;
    }

    public function delete(User $user, GroupApprovalRequest $groupApprovalRequest): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, GroupApprovalRequest $groupApprovalRequest): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, GroupApprovalRequest $groupApprovalRequest): bool
    {
        return $user->isAdmin();
    }
}

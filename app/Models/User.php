<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'role', 'is_team_leader', 'nip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_team_leader' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isSupervisor() || $this->isOperator();
    }

    public function isTeamLeader(): bool
    {
        return (bool) $this->is_team_leader;
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator' || $this->hasRole('operator');
    }

    public function isSuperAdmin(): bool
    {
        if (in_array($this->role, ['operator', 'technician', 'unauthorized', 'requester'])) {
            return false;
        }

        return $this->role === 'superadmin'
            || $this->role === 'super_admin'
            || $this->hasRole(['super_admin', 'superadmin']);
    }

    public function isSupervisor(): bool
    {
        if (in_array($this->role, ['operator', 'technician', 'unauthorized', 'requester'])) {
            return false;
        }

        return $this->role === 'supervisor' || $this->hasRole('supervisor');
    }

    public function isAdmin(): bool
    {
        if (in_array($this->role, ['operator', 'technician', 'unauthorized', 'requester'])) {
            return false;
        }

        return ((bool) $this->is_admin || $this->role === 'admin') && $this->hasRole(['super_admin', 'admin']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserCreationRequest extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'PENDING',
        'initial_group_name' => 'BDS_Technician',
    ];

    protected $fillable = [
        'requester_id',
        'requester_name',
        'first_name',
        'last_name',
        'full_name',
        'display_name',
        'username',
        'user_principal_name',
        'initial_password',
        'initial_group_name',
        'business_justification',
        'approver_email',
        'approval_token',
        'expires_at',
        'status',
        'rejection_reason',
        'actioned_at',
        'actioned_by_ip',
    ];

    protected function casts(): array
    {
        return [
            'initial_password' => 'encrypted',
            'expires_at' => 'datetime',
            'actioned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($request) {
            if (empty($request->approval_token)) {
                $request->approval_token = Str::random(40).bin2hex(random_bytes(12));
            }
            if (empty($request->expires_at)) {
                $request->expires_at = now()->addHours(48);
            }
            if (empty($request->requester_id) && auth()->check()) {
                $request->requester_id = auth()->id();
            }
            if (empty($request->requester_name) && auth()->check()) {
                $request->requester_name = auth()->user()->name;
            }
        });
    }

    /**
     * Check whether the request token has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'EXPIRED') {
            return true;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            if ($this->status === 'PENDING') {
                $this->update(['status' => 'EXPIRED']);
            }

            return true;
        }

        return false;
    }

    /**
     * Enforce strict Segregation of Duties (SoD) authorization.
     */
    public function canBeApprovedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // 0. Expiry check
        if ($this->isExpired()) {
            return false;
        }

        // 1. Status must be PENDING
        if ($this->status !== 'PENDING') {
            return false;
        }

        // 2. Administrators are strictly prohibited from approving/denying
        if ($user->isAdmin()) {
            return false;
        }

        // 3. Approver must hold the Supervisor role
        if (! $user->isSupervisor()) {
            return false;
        }

        // 4. Segregation of Duties (SoD): Requester can NEVER approve/deny their own request
        if ($this->requester_id !== null && (int) $this->requester_id === (int) $user->id) {
            return false;
        }

        if (! empty($this->requester_name) && strcasecmp(trim($this->requester_name), trim($user->name)) === 0) {
            return false;
        }

        // 5. Target account name matching current user
        $targetUsername = strtolower(trim($this->username ?? ''));
        $currentUsername = strtolower(trim($user->username ?? ''));
        if (! empty($targetUsername) && ! empty($currentUsername) && $targetUsername === $currentUsername) {
            return false;
        }

        // 6. If an explicit approver_email was designated, it must match
        if (! empty($this->approver_email) && strcasecmp(trim($user->email), trim($this->approver_email)) !== 0) {
            return false;
        }

        return true;
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_creation_request_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeForApprover(Builder $query, string $email): Builder
    {
        return $query->where('approver_email', $email);
    }
}

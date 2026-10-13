<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_approval_request_id',
        'user_creation_request_id',
        'action',
        'actor',
        'ip_address',
        'details',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(GroupApprovalRequest::class, 'group_approval_request_id');
    }

    public function userCreationRequest(): BelongsTo
    {
        return $this->belongsTo(UserCreationRequest::class, 'user_creation_request_id');
    }

    public static function logAction(
        string $action,
        string $actor,
        ?int $requestId = null,
        ?string $details = null,
        ?string $ipAddress = null,
        ?int $userCreationRequestId = null
    ): self {
        $payload = [
            'action' => $action,
            'actor' => $actor,
            'ip_address' => $ipAddress ?? request()->ip(),
            'details' => $details,
        ];

        if ($requestId !== null) {
            $payload['group_approval_request_id'] = $requestId;
        }

        if ($userCreationRequestId !== null) {
            $payload['user_creation_request_id'] = $userCreationRequestId;
        }

        return self::create($payload);
    }
}

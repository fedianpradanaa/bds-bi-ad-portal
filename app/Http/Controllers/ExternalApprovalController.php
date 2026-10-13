<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GroupApprovalRequest;
use App\Services\ActiveDirectoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalApprovalController extends Controller
{
    public function show(string $token)
    {
        $approvalRequest = GroupApprovalRequest::where('approval_token', $token)->firstOrFail();

        return view('approval.show', compact('approvalRequest'));
    }

    public function approve(Request $request, string $token, ActiveDirectoryService $adService)
    {
        return DB::transaction(function () use ($request, $token, $adService) {
            $approvalRequest = GroupApprovalRequest::where('approval_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approvalRequest->isExpired()) {
                return redirect()->route('approval.show', $token)
                    ->with('error', 'This access approval request has expired. Secondary authorization is required.');
            }

            if ($approvalRequest->status !== 'PENDING') {
                return redirect()->route('approval.show', $token)
                    ->with('error', "This access request has already been {$approvalRequest->status}.");
            }

            // Segregation of Duties (SoD) Enforcement: If user is authenticated, must pass canBeApprovedBy check
            if (auth()->check() && ! $approvalRequest->canBeApprovedBy(auth()->user())) {
                AuditLog::logAction(
                    action: 'SOD_VIOLATION_BLOCKED',
                    actor: auth()->user()->email ?? auth()->user()->username ?? 'Authenticated User',
                    requestId: $approvalRequest->id,
                    details: 'Segregation of Duties (SoD) violation: User attempted self-approval via remote link.'
                );

                return redirect()->route('approval.show', $token)
                    ->with('error', 'Segregation of Duties (SoD) Violation: Requesters and unauthorized users cannot approve their own requests.');
            }

            try {
                $adService->assignUserToGroup($approvalRequest->target_username, $approvalRequest->target_group_name);

                $approvalRequest->update([
                    'status' => 'APPROVED',
                    'actioned_at' => now(),
                    'actioned_by_ip' => $request->ip(),
                ]);

                AuditLog::logAction(
                    action: 'APPROVED',
                    actor: $approvalRequest->approver_email.' (Magic-Link)',
                    requestId: $approvalRequest->id,
                    details: "Approved via remote magic link. Added {$approvalRequest->target_username} to {$approvalRequest->target_group_name}."
                );

                AuditLog::logAction(
                    action: 'LDAP_SUCCESS',
                    actor: 'SYSTEM (svc-ad-portal)',
                    requestId: $approvalRequest->id,
                    details: "LDAP attach completed for group {$approvalRequest->target_group_name}."
                );

                return redirect()->route('approval.show', $token)
                    ->with('success', "Active Directory assignment for '{$approvalRequest->target_username}' to group '{$approvalRequest->target_group_name}' was successfully approved!");
            } catch (Exception $e) {
                AuditLog::logAction(
                    action: 'LDAP_FAILED',
                    actor: $approvalRequest->approver_email.' (Magic-Link)',
                    requestId: $approvalRequest->id,
                    details: 'Remote LDAP execution failure: '.$e->getMessage()
                );

                return redirect()->route('approval.show', $token)
                    ->with('error', 'AD Synchronization Failed: '.$e->getMessage());
            }
        });
    }

    public function reject(Request $request, string $token)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000',
        ]);

        return DB::transaction(function () use ($request, $token, $validated) {
            $approvalRequest = GroupApprovalRequest::where('approval_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approvalRequest->isExpired()) {
                return redirect()->route('approval.show', $token)
                    ->with('error', 'This access approval request has expired.');
            }

            if ($approvalRequest->status !== 'PENDING') {
                return redirect()->route('approval.show', $token)
                    ->with('error', "This access request has already been {$approvalRequest->status}.");
            }

            // Segregation of Duties (SoD) Enforcement: If user is authenticated, must pass canBeApprovedBy check
            if (auth()->check() && ! $approvalRequest->canBeApprovedBy(auth()->user())) {
                return redirect()->route('approval.show', $token)
                    ->with('error', 'Segregation of Duties (SoD) Violation: Requesters and unauthorized users cannot reject their own requests.');
            }

            $approvalRequest->update([
                'status' => 'REJECTED',
                'rejection_reason' => $validated['rejection_reason'],
                'actioned_at' => now(),
                'actioned_by_ip' => $request->ip(),
            ]);

            AuditLog::logAction(
                action: 'REJECTED',
                actor: $approvalRequest->approver_email.' (Magic-Link)',
                requestId: $approvalRequest->id,
                details: "Rejected via remote magic link. Reason: {$validated['rejection_reason']}"
            );

            return redirect()->route('approval.show', $token)
                ->with('warning', "Access request for '{$approvalRequest->target_username}' was rejected.");
        });
    }
}

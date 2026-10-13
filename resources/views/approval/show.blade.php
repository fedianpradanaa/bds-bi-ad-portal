<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Directory Group Approval - BDS Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Active Directory Group Authorization
                    </h1>
                    <p class="text-blue-100 text-sm mt-1">BDS Access Approval Portal (cmc.mhe.local)</p>
                </div>
                <div>
                    @if($approvalRequest->status === 'PENDING')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                            PENDING APPROVAL
                        </span>
                    @elseif($approvalRequest->status === 'APPROVED')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            APPROVED
                        </span>
                    @elseif($approvalRequest->status === 'REJECTED')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300">
                            REJECTED
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                            {{ $approvalRequest->status }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <div class="p-6 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-lg text-emerald-900 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-300 rounded-lg text-rose-900 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 bg-amber-50 border border-amber-300 rounded-lg text-amber-900 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>{{ session('warning') }}</div>
                </div>
            @endif

            <!-- Request Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-5 rounded-lg border border-slate-200">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Target User</label>
                    <div class="mt-1 font-semibold text-blue-700 text-base">{{ $approvalRequest->target_username }}</div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Requested Group</label>
                    <div class="mt-1 font-semibold text-amber-700 text-base">{{ $approvalRequest->target_group_name }}</div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Requester</label>
                    <div class="mt-1 text-slate-800 font-medium">{{ $approvalRequest->requester_name }}</div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Designated Approver</label>
                    <div class="mt-1 text-slate-800 font-medium">{{ $approvalRequest->approver_email }}</div>
                </div>

                <div class="md:col-span-2 pt-3 border-t border-slate-200">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Business Justification</label>
                    <div class="mt-1 text-slate-700 text-sm bg-white p-3 rounded border border-slate-200 shadow-sm">
                        {{ $approvalRequest->business_justification }}
                    </div>
                </div>

                @if($approvalRequest->rejection_reason)
                    <div class="md:col-span-2 pt-3 border-t border-slate-200">
                        <label class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Rejection Reason</label>
                        <div class="mt-1 text-rose-900 text-sm bg-rose-50 p-3 rounded border border-rose-200">
                            {{ $approvalRequest->rejection_reason }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Actions Area (When Pending) -->
            @if($approvalRequest->status === 'PENDING')
                <div class="border-t border-slate-200 pt-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-800">Supervisor Authorization</h3>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Approve Form -->
                        <form action="{{ route('approval.approve', $approvalRequest->approval_token) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Confirm adding user \'{{ $approvalRequest->target_username }}\' to AD group \'{{ $approvalRequest->target_group_name }}\'?')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-medium rounded-lg shadow transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Authorize & Assign Group
                            </button>
                        </form>

                        <!-- Toggle Reject Button -->
                        <button type="button" 
                                onclick="document.getElementById('reject-form-section').classList.toggle('hidden')"
                                class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 text-sm font-medium rounded-lg transition text-center">
                            Reject Request
                        </button>
                    </div>

                    <!-- Reject Collapsible Form -->
                    <div id="reject-form-section" class="hidden bg-slate-50 p-4 rounded-lg border border-rose-300 mt-4">
                        <form action="{{ route('approval.reject', $approvalRequest->approval_token) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="rejection_reason" class="block text-xs font-semibold text-rose-800 mb-1">
                                    Mandatory Rejection Justification:
                                </label>
                                <textarea name="rejection_reason" id="rejection_reason" rows="2" required minlength="5"
                                          placeholder="Explain why this request is rejected..."
                                          class="w-full bg-white border border-slate-300 rounded p-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold rounded shadow transition">
                                    Confirm Rejection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="border-t border-slate-200 pt-4 text-center text-xs text-slate-500">
                    This request was finalized on {{ $approvalRequest->actioned_at ? $approvalRequest->actioned_at->format('d M Y H:i:s') : '-' }}.
                </div>
            @endif

        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 text-center text-xs text-slate-500">
            Active Directory Web Portal &bull; Host: BDS-WEB-GD01 (10.111.167.30) &bull; Least Privilege Security Tier
        </div>

    </div>
</body>
</html>

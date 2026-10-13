@php
    $user = filament()->auth()->user();
    if (! $user) {
        return;
    }

    $rawRole = match(true) {
        $user->isSuperAdmin() => 'Super Administrator',
        (bool) $user->is_team_leader => 'Team Leader',
        $user->role === 'operator' => 'Operator',
        $user->role === 'supervisor' => 'Supervisor',
        $user->role === 'admin' => 'Admin',
        default => ucfirst($user->role ?? 'User'),
    };

    // Remove any occurrence of 'BDS' or 'BDS_' from name and role
    $cleanName = trim(preg_replace('/\bBDS\b/i', '', $user->name));
    $cleanName = trim(str_ireplace('BDS', '', $cleanName));
    $cleanName = preg_replace('/\s+/', ' ', $cleanName);
    if (empty($cleanName)) {
        $cleanName = $user->name;
    }

    $cleanRole = trim(preg_replace('/\bBDS\b/i', '', $rawRole));
    $cleanRole = trim(str_ireplace('BDS', '', $cleanRole));
    $cleanRole = preg_replace('/\s+/', ' ', $cleanRole);
    if (empty($cleanRole)) {
        $cleanRole = $rawRole;
    }
@endphp

<div style="display: flex; flex-direction: column; text-align: right; justify-content: center; margin-right: 0.75rem; line-height: 1.2; user-select: none;">
    <div style="font-weight: 600; font-size: 0.875rem; line-height: 1.25; color: #0f172a;" class="dark:text-gray-100">
        {{ $cleanName }}
    </div>
    <div style="font-weight: 500; font-size: 0.75rem; line-height: 1.15; color: #64748b;" class="dark:text-gray-400">
        {{ $cleanRole }}
    </div>
</div>

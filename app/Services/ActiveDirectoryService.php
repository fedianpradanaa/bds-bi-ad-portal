<?php

namespace App\Services;

use App\Models\User;
use App\Rules\ActiveDirectoryPasswordRule;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\Group as AdGroup;
use LdapRecord\Models\ActiveDirectory\User as AdUser;
use Spatie\Permission\Models\Role;

class ActiveDirectoryService
{
    protected string $userBaseDn;

    protected string $groupBaseDn;

    protected bool $mockMode;

    public function __construct()
    {
        $this->userBaseDn = env('LDAP_USER_BASE_DN', 'OU=Users,OU=Tier3-Test-UAT,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');
        $this->groupBaseDn = env('LDAP_GROUP_BASE_DN', 'OU=BDS-Groups,OU=CCS-Groups,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');
        $this->mockMode = (bool) config('app.ad_mock_mode', env('AD_MOCK_MODE', false));
    }

    /**
     * Determine whether mock mode is currently enabled.
     */
    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Quickly probe whether the Active Directory Domain Controller is reachable via socket.
     * Caches the result briefly to avoid repetitive network overhead.
     */
    public function isLdapReachable(): bool
    {
        if ($this->mockMode) {
            return true;
        }

        return (bool) cache()->remember('ldap_dc_is_reachable', now()->addSeconds(30), function () {
            $hosts = config('ldap.connections.default.hosts', ['10.111.121.10']);
            $port = (int) config('ldap.connections.default.port', 389);

            if (is_string($hosts)) {
                $hosts = explode(',', $hosts);
            }

            foreach ($hosts as $host) {
                $host = trim($host);
                if (empty($host)) {
                    continue;
                }

                $fp = @fsockopen($host, $port, $errno, $errstr, 0.3);
                if (is_resource($fp)) {
                    fclose($fp);

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Retrieve list of selectable AD users.
     *
     * @return array<string, string> Key: sAMAccountName / CN, Value: Display Label
     */
    public function getDirectoryUsers(): array
    {
        if ($this->mockMode) {
            return [
                'BDS Niklas' => 'BDS Niklas',
                'bds-niklas' => 'BDS Niklas',
                'BDS Ari' => 'BDS Ari',
                'bds-ari' => 'BDS Ari',
                'BDS Ghifari' => 'BDS Ghifari',
                'bds-ghifari' => 'BDS Ghifari',
                'BDS Lahcen' => 'BDS Lahcen',
                'bds-lahcen' => 'BDS Lahcen',
                'Agus Afrianto' => 'Agus Afrianto',
                'agus.afrianto' => 'Agus Afrianto',
                'BDS Budi' => 'BDS Budi',
                'bds-budi' => 'BDS Budi',
                'BDS Edi' => 'BDS Edi',
                'bds-edi' => 'BDS Edi',
                'BDS Fedian Tegar Pradana' => 'BDS Fedian Tegar Pradana',
                'bds-fedian' => 'BDS Fedian Tegar Pradana',
                'BDS Prio' => 'BDS Prio',
                'bds-prio' => 'BDS Prio',
                'BDS Syahputraa' => 'BDS Syahputraa',
                'bds-syahputraa' => 'BDS Syahputraa',
                'BDS thoriq' => 'BDS thoriq',
                'bds-thoriq' => 'BDS thoriq',
                'Boby Sterino' => 'Boby Sterino',
                'boby.sterino' => 'Boby Sterino',
                'Eko Susanto' => 'Eko Susanto',
                'eko.susanto' => 'Eko Susanto',
                'Indra Rosmana' => 'Indra Rosmana',
                'indra.rosmana' => 'Indra Rosmana',
                'Izarul Irfan' => 'Izarul Irfan',
                'izarul.irfan' => 'Izarul Irfan',
                'musafak' => 'musafak',
                'bds-musafak' => 'musafak',
                'Tonih royanih' => 'Tonih royanih',
                'bds-tonih' => 'Tonih royanih',
                'field.tech01' => 'field.tech01',
            ];
        }

        if (! $this->isLdapReachable()) {
            return User::pluck('name', 'username')->toArray();
        }

        try {
            $users = AdUser::in($this->userBaseDn)->get();
            $results = [];
            foreach ($users as $user) {
                $username = $user->getFirstAttribute('samaccountname') ?: $user->getFirstAttribute('cn');
                $displayName = $user->getFirstAttribute('displayname') ?: $username;
                if ($username) {
                    $results[$username] = $displayName;
                }
            }

            return $results;
        } catch (Exception $e) {
            Log::error('Failed to query AD Users: '.$e->getMessage());

            return [];
        }
    }

    public function getTier3Users(): array
    {
        return $this->getDirectoryUsers();
    }

    /**
     * Synchronize all users from Active Directory into the local portal database.
     *
     * @return array{synced: int, created: int, updated: int, errors: array<string>}
     */
    public function syncUsersFromDirectory(): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        if ($this->mockMode) {
            $mockUsers = [
                ['name' => 'Agus Afrianto', 'username' => 'agus.afrianto', 'email' => 'agus.afrianto@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Ari', 'username' => 'bds-ari', 'email' => 'bds-ari@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Budi', 'username' => 'bds-budi', 'email' => 'bds-budi@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Edi', 'username' => 'bds-edi', 'email' => 'bds-edi@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Fedian Tegar Pradana', 'username' => 'bds-fedian', 'email' => 'bds-fedian@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Ghifari', 'username' => 'bds-ghifari', 'email' => 'bds-ghifari@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Lahcen', 'username' => 'bds-lahcen', 'email' => 'bds-lahcen@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Niklas', 'username' => 'bds-niklas', 'email' => 'bds-niklas@cmc.mhe.local', 'role' => 'supervisor'],
                ['name' => 'BDS Prio', 'username' => 'bds-prio', 'email' => 'bds-prio@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'BDS Syahputraa', 'username' => 'bds-syahputraa', 'email' => 'bds-syahputraa@cmc.mhe.local', 'role' => 'admin'],
                ['name' => 'BDS thoriq', 'username' => 'bds-thoriq', 'email' => 'bds-thoriq@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'Boby Sterino', 'username' => 'boby.sterino', 'email' => 'boby.sterino@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'Eko Susanto', 'username' => 'eko.susanto', 'email' => 'eko.susanto@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'Indra Rosmana', 'username' => 'indra.rosmana', 'email' => 'indra.rosmana@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'Izarul Irfan', 'username' => 'izarul.irfan', 'email' => 'izarul.irfan@cmc.mhe.local', 'role' => 'operator'],
                ['name' => 'musafak', 'username' => 'bds-musafak', 'email' => 'musafak@bi.go.id', 'role' => 'supervisor'],
                ['name' => 'Tonih royanih', 'username' => 'bds-tonih', 'email' => 'tonih@cmc.mhe.local', 'role' => 'supervisor'],
            ];

            foreach ($mockUsers as $m) {
                $user = User::where('username', strtolower($m['username']))
                    ->orWhere('email', strtolower($m['email']))
                    ->first();

                if ($user) {
                    $user->update([
                        'name' => $m['name'],
                        'email' => strtolower($m['email']),
                    ]);
                    $updated++;
                } else {
                    $user = User::create([
                        'name' => $m['name'],
                        'username' => strtolower($m['username']),
                        'email' => strtolower($m['email']),
                        'password' => Hash::make(Str::random(32)),
                        'role' => $m['role'],
                        'is_admin' => $m['role'] === 'admin',
                    ]);
                    $created++;
                }

                $role = Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
                $user->syncRoles([$role]);
            }

            return ['synced' => $created + $updated, 'created' => $created, 'updated' => $updated, 'errors' => []];
        }

        if (! $this->isLdapReachable()) {
            Log::info('Active Directory DC is unreachable from this environment. Skipping live AD sync.');

            return [
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => ['Active Directory Domain Controller is unreachable from this network environment.'],
            ];
        }

        try {
            $users = AdUser::in($this->userBaseDn)->get();

            foreach ($users as $adUser) {
                $samAccount = $adUser->getFirstAttribute('samaccountname') ?: $adUser->getFirstAttribute('cn');
                if (! $samAccount) {
                    continue;
                }

                $displayName = $adUser->getFirstAttribute('displayname') ?: $adUser->getFirstAttribute('cn') ?: $samAccount;
                $mail = $adUser->getFirstAttribute('mail') ?: $adUser->getFirstAttribute('userprincipalname');
                if (! $mail) {
                    $mail = strtolower($samAccount).'@cmc.mhe.local';
                }

                $memberOf = (array) ($adUser->getAttribute('memberof') ?? []);
                $isAdmin = false;
                $isSupervisor = false;
                $isOperator = false;

                foreach ($memberOf as $groupDn) {
                    if (stripos($groupDn, 'BDS_Supervisor') !== false) {
                        $isSupervisor = true;
                    }
                    if (stripos($groupDn, 'BDS_Admin') !== false || stripos($groupDn, 'Domain Admins') !== false) {
                        $isAdmin = true;
                    }
                    if (stripos($groupDn, 'BDS_Operator') !== false) {
                        $isOperator = true;
                    }
                }

                $roleName = $isAdmin ? 'admin' : ($isSupervisor ? 'supervisor' : 'operator');

                $user = User::where('username', strtolower($samAccount))
                    ->orWhere('email', strtolower($mail))
                    ->first();

                if ($user) {
                    $updateData = [
                        'name' => $displayName,
                        'email' => strtolower($mail),
                    ];
                    if (! in_array($user->role, ['superadmin', 'super_admin'])) {
                        $updateData['role'] = $roleName;
                        $updateData['is_admin'] = $isAdmin;
                    }
                    $user->update($updateData);
                    $updated++;
                } else {
                    $user = User::create([
                        'name' => $displayName,
                        'username' => strtolower($samAccount),
                        'email' => strtolower($mail),
                        'password' => Hash::make(Str::random(32)),
                        'role' => $roleName,
                        'is_admin' => $isAdmin,
                    ]);
                    $created++;
                }

                $role = Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
                $user->syncRoles([$role]);
            }

            return ['synced' => $created + $updated, 'created' => $created, 'updated' => $updated, 'errors' => $errors];
        } catch (Exception $e) {
            Log::error('Failed to sync users from AD: '.$e->getMessage());

            return ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Retrieve list of selectable AD groups under OU=Groups,OU=CMC.MHE.
     *
     * @return array<string, string> Key: CN, Value: Display Label
     */
    public function getAvailableGroups(): array
    {
        if ($this->mockMode) {
            return [
                'BDS_Admin' => 'BDS_Admin',
                'BDS_Operator' => 'BDS_Operator',
                'BDS_Supervisor' => 'BDS_Supervisor',
                'BDS_Technician' => 'BDS_Technician',
            ];
        }

        if (! $this->isLdapReachable()) {
            return [
                'BDS_Admin' => 'BDS_Admin',
                'BDS_Operator' => 'BDS_Operator',
                'BDS_Supervisor' => 'BDS_Supervisor',
                'BDS_Technician' => 'BDS_Technician',
            ];
        }

        try {
            // Filter strictly to BDS-specific security groups
            $groups = AdGroup::in($this->groupBaseDn)
                ->whereStartsWith('cn', 'BDS_')
                ->get();

            $results = [];
            foreach ($groups as $group) {
                $cn = $group->getFirstAttribute('cn');
                if ($cn) {
                    $results[$cn] = $cn;
                }
            }

            if (! empty($results)) {
                ksort($results);

                return $results;
            }

            return [
                'BDS_Admin' => 'BDS_Admin',
                'BDS_Operator' => 'BDS_Operator',
                'BDS_Supervisor' => 'BDS_Supervisor',
                'BDS_Technician' => 'BDS_Technician',
            ];
        } catch (Exception $e) {
            Log::error('Failed to query AD Groups: '.$e->getMessage());

            return [
                'BDS_Admin' => 'BDS_Admin',
                'BDS_Operator' => 'BDS_Operator',
                'BDS_Supervisor' => 'BDS_Supervisor',
                'BDS_Technician' => 'BDS_Technician',
            ];
        }
    }

    /**
     * Retrieve list of selectable AD groups based on user authority.
     * Regular Admins are strictly limited to Operator and Technician.
     * Superadmins can request any available group.
     *
     * @return array<string, string> Key: CN, Value: CN
     */
    public function getRequestableGroups(?User $user = null): array
    {
        $allGroups = $this->getAvailableGroups();

        if ($user && $user->isSuperAdmin()) {
            return $allGroups;
        }

        // Operational groups only for standard Admins
        $allowed = ['BDS_Operator', 'BDS_Technician'];

        return array_filter($allGroups, fn ($group) => in_array($group, $allowed));
    }

    /**
     * Assigns an Active Directory user located in Tier3-Test-UAT to a designated security group.
     *
     * @param  string  $userIdentifier  sAMAccountName or CN of target user
     * @param  string  $groupCn  Common Name of the target security group
     *
     * @throws Exception
     */
    public function assignUserToGroup(string $userIdentifier, string $groupCn): bool
    {
        if ($this->mockMode) {
            Log::info("[AD-MOCK] Successfully assigned user '{$userIdentifier}' to group '{$groupCn}' in Tier3 scope.");

            return true;
        }

        // 1. Locate user in OU=Tier3-Test-UAT/Users (supports both sAMAccountName and CN formats)
        $user = AdUser::in($this->userBaseDn)
            ->where('samaccountname', '=', $userIdentifier)
            ->orWhere('cn', '=', $userIdentifier)
            ->first();

        if (! $user) {
            throw new Exception("Active Directory account '{$userIdentifier}' was not found in '{$this->userBaseDn}'.");
        }

        // 2. Locate target group in OU=Groups,OU=CMC.MHE
        $group = AdGroup::in($this->groupBaseDn)
            ->where('cn', '=', $groupCn)
            ->first();

        if (! $group) {
            throw new Exception("Security Group '{$groupCn}' was not found in '{$this->groupBaseDn}'.");
        }

        // 3. Verify existing membership
        if ($group->members()->exists($user)) {
            Log::info("AD user '{$userIdentifier}' is already a member of group '{$groupCn}'.");

            return true;
        }

        // 4. Attach member via LDAP (throws Exception on failure, returns void on success)
        try {
            $group->members()->attach($user);
            Log::info("Successfully attached AD user '{$userIdentifier}' to group '{$groupCn}'.");
        } catch (\Throwable $attachError) {
            Log::error("Failed to attach user '{$userIdentifier}' to group '{$groupCn}': ".$attachError->getMessage());
            throw new Exception("Failed to attach user '{$userIdentifier}' to group '{$groupCn}' via LDAP: ".$attachError->getMessage());
        }

        return true;
    }

    /**
     * Create a new Active Directory User in OU=Users,OU=Tier3-Test-UAT.
     *
     * @param  string  $username  e.g. BDS-Syahputra
     * @param  string  $displayName  e.g. BDS Ahmad Syahputra
     * @param  string  $password  Initial plaintext password
     * @param  string|null  $initialGroup  e.g. BDS_Technician
     *
     * @throws Exception
     */
    public function createUserAccount(
        string $username,
        string $displayName,
        string $password,
        ?string $initialGroup = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null
    ): bool {
        // Enforce password policy: complexity & disallow name/username
        $this->validatePasswordCompliance($password, $username, $displayName);

        if ($this->mockMode) {
            Log::info("[AD-MOCK] Created user account '{$username}' ({$displayName}) in {$this->userBaseDn} and assigned to group '{$initialGroup}'.");

            return true;
        }

        // 1. Check if user already exists
        $existing = AdUser::in($this->userBaseDn)
            ->where('samaccountname', '=', $username)
            ->orWhere('cn', '=', $displayName)
            ->first();

        if ($existing) {
            Log::info("AD account '{$username}' already exists in {$this->userBaseDn}. Ensuring group assignment...");
            if (! empty($initialGroup)) {
                $this->assignUserToGroup($username, $initialGroup);
            }

            return true;
        }

        // 2. Instantiate new AD User object
        $user = new AdUser;
        $user->inside($this->userBaseDn);
        $user->cn = $displayName;
        $user->samaccountname = $username;
        $user->userprincipalname = "{$username}@cmc.mhe.local";
        $user->displayname = $displayName;

        if ($email) {
            $user->mail = $email;
        }

        if ($firstName) {
            $user->givenname = $firstName;
        }
        if ($lastName) {
            $user->sn = $lastName;
        }

        // 3. Set password and enable account (512 = NORMAL_ACCOUNT)
        $user->unicodepwd = $password;
        $user->useraccountcontrol = 512;

        try {
            $user->save();
        } catch (\Throwable $saveError) {
            $errorMessage = $saveError->getMessage();

            try {
                $diag = null;
                $conn = Container::getDefaultConnection()->getLdapConnection();
                $resource = $conn?->getConnection();
                if ($resource) {
                    ldap_get_option($resource, 0x0032, $diag);
                }

                if ($diag) {
                    if (str_contains($diag, '52D') || str_contains($diag, 'WILL_NOT_PERFORM')) {
                        $errorMessage = 'Password does not meet Active Directory complexity requirements (minimum 7 characters, must contain uppercase, lowercase, numbers, and symbols, and cannot contain user account name).';
                    } elseif (str_contains($diag, '524') || str_contains($diag, 'ENTRY_EXISTS')) {
                        $errorMessage = "Account or CN '{$displayName}' already exists in Active Directory.";
                    } else {
                        $errorMessage .= ' [AD Diagnostic: '.trim($diag).']';
                    }
                }
            } catch (\Throwable) {
                // Ignore diagnostic lookup error
            }

            Log::error("Error creating AD user '{$username}': ".$errorMessage);
            throw new Exception("Active Directory error creating user '{$username}': ".$errorMessage);
        }

        // 4. Attach initial security group if requested
        if (! empty($initialGroup)) {
            $this->assignUserToGroup($username, $initialGroup);
        }

        return true;
    }

    /**
     * Retrieve list of supervisors available for request authorization.
     * Combines Active Directory members of BDS_Supervisor and local Supervisor accounts.
     *
     * @return array<string, string> Key: Email, Value: Display Label
     */
    public function getSupervisors(): array
    {
        $supervisors = [];

        // 1. Fetch from Active Directory BDS_Supervisor group members
        if (! $this->mockMode) {
            try {
                $group = AdGroup::in($this->groupBaseDn)
                    ->where('cn', '=', 'BDS_Supervisor')
                    ->first();

                if ($group) {
                    foreach ($group->members()->get() as $member) {
                        $username = $member->getFirstAttribute('samaccountname') ?: $member->getFirstAttribute('cn');
                        $name = $member->getFirstAttribute('displayname') ?: $username;
                        $rawEmail = $member->getFirstAttribute('mail')
                            ?: $member->getFirstAttribute('userprincipalname')
                            ?: ($username ? "{$username}@cmc.mhe.local" : null);

                        if ($rawEmail && $name) {
                            $supervisors[strtolower(trim($rawEmail))] = trim($name);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Failed to query AD BDS_Supervisor members: '.$e->getMessage());
            }
        }

        // 2. Combine with local database users with role 'supervisor'
        try {
            $localSupervisors = User::where('role', 'supervisor')
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'supervisor'))
                ->get();

            foreach ($localSupervisors as $user) {
                if (! empty($user->email) && ! empty($user->name)) {
                    $supervisors[strtolower(trim($user->email))] = trim($user->name);
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to query local supervisors: '.$e->getMessage());
        }

        // 3. Fallback defaults if empty
        if (empty($supervisors)) {
            $supervisors['supervisor@cmc.mhe.local'] = 'Supervisor BDS';
        }

        // Deduplicate by name if same supervisor has multiple email casings
        $uniqueByName = [];
        foreach ($supervisors as $email => $name) {
            $normalizedName = strtolower($name);
            if (! isset($uniqueByName[$normalizedName])) {
                $uniqueByName[$normalizedName] = [
                    'email' => $email,
                    'name' => $name,
                ];
            }
        }

        $results = [];
        foreach ($uniqueByName as $item) {
            $results[$item['email']] = $item['name'];
        }

        asort($results);

        return $results;
    }

    /**
     * Check if a user exists in Active Directory (OU=Users,OU=Tier3-Test-UAT).
     */
    public function userExists(string $username): bool
    {
        if ($this->mockMode) {
            return in_array($username, array_keys($this->getDirectoryUsers()));
        }

        if (! $this->isLdapReachable()) {
            return in_array($username, array_keys($this->getDirectoryUsers()));
        }

        try {
            return AdUser::in($this->userBaseDn)
                ->where('samaccountname', '=', $username)
                ->orWhere('cn', '=', $username)
                ->exists();
        } catch (\Throwable $e) {
            Log::warning("AD userExists check failed for '{$username}': ".$e->getMessage());

            return false;
        }
    }

    /**
     * Update an Active Directory user's password.
     */
    public function updateUserPassword(string $username, string $newPassword): bool
    {
        $user = null;
        if (! $this->mockMode) {
            $user = AdUser::in($this->userBaseDn)
                ->where('samaccountname', '=', $username)
                ->orWhere('cn', '=', $username)
                ->first();

            if (! $user) {
                throw new Exception("User '{$username}' was not found in Active Directory.");
            }
        }

        $displayName = $user ? ($user->getFirstAttribute('displayname') ?: $username) : $username;
        $this->validatePasswordCompliance($newPassword, $username, $displayName);

        if ($this->mockMode) {
            Log::info("[AD-MOCK] Updated password for AD user '{$username}'.");

            return true;
        }

        $user->unicodepwd = $newPassword;
        $user->save();

        Log::info("Successfully updated AD password for '{$username}'.");

        return true;
    }

    /**
     * Validate that a password meets complexity and does not contain user name or username.
     *
     * @throws Exception
     */
    public function validatePasswordCompliance(string $password, string $username, string $displayName): void
    {
        $rule = new ActiveDirectoryPasswordRule(username: $username, name: $displayName);
        $errorMessage = null;

        $rule->validate('password', $password, function (string $msg) use (&$errorMessage) {
            $errorMessage = $msg;
        });

        if ($errorMessage) {
            throw new Exception("Active Directory password policy violation: {$errorMessage}");
        }
    }
}

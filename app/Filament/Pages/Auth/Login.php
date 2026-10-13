<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\ActiveDirectoryService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LdapRecord\Connection;
use Spatie\Permission\Models\Role;
use Throwable;

class Login extends BaseLogin
{
    use WithRateLimiting;

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username')
            ->placeholder('Input Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'login' => trim($data['login'] ?? ''),
            'password' => $data['password'] ?? '',
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $loginInput = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';
        $remember = (bool) ($data['remember'] ?? false);

        if (empty($loginInput) || empty($password)) {
            $this->throwFailureValidationException();
        }

        $isMock = config('app.ad_mock_mode', env('AD_MOCK_MODE', false));

        // 1. If in Live AD Mode (AD_MOCK_MODE=false), authenticate directly against Domain Controller
        if (! $isMock && app(ActiveDirectoryService::class)->isLdapReachable()) {
            try {
                $host = config('ldap.connections.default.hosts', ['10.111.121.10', '10.111.121.20']);
                $port = (int) config('ldap.connections.default.port', 389);
                $baseDn = config('ldap.connections.default.base_dn', 'OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');

                // Format UPN if plain username provided (e.g. bds-syahputraa -> bds-syahputraa@cmc.mhe.local)
                $upn = str_contains($loginInput, '@') ? $loginInput : "{$loginInput}@cmc.mhe.local";
                $samAccount = str_contains($loginInput, '@') ? explode('@', $loginInput)[0] : $loginInput;

                $adConnection = new Connection([
                    'hosts' => is_array($host) ? $host : explode(',', $host),
                    'port' => $port,
                    'base_dn' => $baseDn,
                    'username' => $upn,
                    'password' => $password,
                    'timeout' => 4,
                    'use_tls' => false,
                    'use_starttls' => false,
                ]);

                $adConnection->connect();

                // Authentication succeeded with Active Directory!
                Log::info("[AD-AUTH] Direct AD authentication successful for user: {$upn}");

                // Query user display name from AD if possible
                $adUser = $adConnection->query()->where('samaccountname', '=', $samAccount)->orWhere('userprincipalname', '=', $upn)->first();
                $displayName = $adUser['displayname'][0] ?? $adUser['cn'][0] ?? $samAccount;
                $email = $adUser['mail'][0] ?? $upn;

                // Determine roles based on AD Groups
                $memberOf = (array) ($adUser['memberof'] ?? []);
                $isSupervisor = false;
                $isAdmin = false;
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

                // Strictly deny login if user is not in BDS_Supervisor, BDS_Admin, or BDS_Operator
                if (! $isSupervisor && ! $isAdmin && ! $isOperator) {
                    Log::warning("[AD-AUTH] Access Denied: User '{$upn}' is not in BDS_Supervisor, BDS_Admin, or BDS_Operator groups.");
                    throw ValidationException::withMessages([
                        'data.login' => 'Access Denied: Only Supervisor, Admin, and Operator roles are authorized to access the BDS Access Portal.',
                    ]);
                }

                // Provision or update local user record
                $roleName = $isSupervisor ? 'supervisor' : ($isAdmin ? 'admin' : 'operator');
                $user = User::updateOrCreate(
                    ['username' => strtolower($samAccount)],
                    [
                        'name' => $displayName,
                        'email' => strtolower($email),
                        'password' => Hash::make($password),
                        'role' => $roleName,
                        'is_admin' => $isAdmin,
                    ]
                );

                // Sync Spatie / Shield roles
                $spatieRole = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $user->syncRoles([$spatieRole]);

                // Log user into session
                Filament::auth()->login($user, $remember);
                session()->regenerate();

                return app(LoginResponse::class);
            } catch (ValidationException $valEx) {
                throw $valEx;
            } catch (Throwable $e) {
                Log::warning("[AD-AUTH] Direct AD bind failed for '{$loginInput}': ".$e->getMessage());
                // Fall through to check local database fallback
            }
        }

        // 2. Local Database Authentication Fallback (Supports 'admin', 'supervisor', 'operator', or email)
        $localUser = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->first();

        if ($localUser && Hash::check($password, $localUser->password)) {
            if (! $localUser->isSuperAdmin() && ! $localUser->isAdmin() && ! $localUser->isSupervisor() && ! $localUser->isOperator()) {
                throw ValidationException::withMessages([
                    'data.login' => 'Access Denied: Only Supervisor, Admin, Superadmin, and Operator accounts are authorized to access the BDS Access Portal.',
                ]);
            }

            Filament::auth()->login($localUser, $remember);
            session()->regenerate();

            return app(LoginResponse::class);
        }

        $this->throwFailureValidationException();

        return null;
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}

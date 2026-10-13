<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use LdapRecord\Connection;

class TestActiveDirectoryConnection extends Command
{
    protected $signature = 'ad:test-connection {--username= : LDAP Bind Username} {--password= : LDAP Bind Password}';

    protected $description = 'Test live LDAP / LDAPS binding and query against Active Directory Domain Controllers';

    public function handle(): int
    {
        $host = config('ldap.connections.default.hosts', ['10.111.121.10', '10.111.121.20']);
        $port = (int) config('ldap.connections.default.port', 389);
        $username = $this->option('username') ?: env('LDAP_USERNAME');
        $password = $this->option('password') ?: env('LDAP_PASSWORD');
        $baseDn = config('ldap.connections.default.base_dn', 'OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');
        $userBaseDn = env('LDAP_USER_BASE_DN', 'OU=Users,OU=Tier3-Test-UAT,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');
        $groupBaseDn = env('LDAP_GROUP_BASE_DN', 'OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local');

        $this->info('==================================================');
        $this->info('   ACTIVE DIRECTORY LIVE CONNECTION TEST         ');
        $this->info('==================================================');
        $this->line('Target Hosts   : '.(is_array($host) ? implode(', ', $host) : $host));
        $this->line('Port           : '.$port);
        $this->line('Bind User      : '.($username ?: '(None provided)'));
        $this->line('Base DN        : '.$baseDn);
        $this->line('User Base DN   : '.$userBaseDn);
        $this->line('Group Base DN  : '.$groupBaseDn);
        $this->newLine();

        if (empty($username) || empty($password)) {
            $this->warn('⚠️  LDAP_USERNAME or LDAP_PASSWORD is empty in .env.');
            $this->line('Run with credentials: php artisan ad:test-connection --username=YOUR_USER@cmc.mhe.local --password="YOUR_PASS"');

            return 1;
        }

        $this->info('Connecting & Binding to Active Directory...');

        try {
            $connection = new Connection([
                'hosts' => is_array($host) ? $host : explode(',', $host),
                'port' => $port,
                'base_dn' => $baseDn,
                'username' => $username,
                'password' => $password,
                'timeout' => 5,
                'use_tls' => false,
                'use_starttls' => false,
            ]);

            $connection->connect();
            $this->info('✅ Successfully connected and authenticated with Active Directory DC!');

            $this->newLine();
            $this->info("--- Querying Users in {$userBaseDn} ---");
            $users = $connection->query()->in($userBaseDn)->where('objectClass', '=', 'user')->get();
            $this->info('Found '.count($users).' user objects:');
            foreach ($users as $u) {
                $sam = $u['samaccountname'][0] ?? '(no samaccountname)';
                $cn = $u['cn'][0] ?? '';
                $this->line(" - {$cn} [sAMAccountName: {$sam}]");
            }

            $this->newLine();
            $this->info("--- Querying Groups in {$groupBaseDn} ---");
            $groups = $connection->query()->in($groupBaseDn)->where('objectClass', '=', 'group')->get();
            $this->info('Found '.count($groups).' group objects:');
            foreach ($groups as $g) {
                $cn = $g['cn'][0] ?? '';
                $this->line(" - {$cn}");
            }

            $this->newLine();
            $this->info('🎉 ALL ACTIVE DIRECTORY TESTS PASSED SUCCESSFULLY!');

            return 0;
        } catch (Exception $e) {
            $this->error('❌ Active Directory Connection Failed: '.$e->getMessage());

            return 1;
        }
    }
}

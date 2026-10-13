<?php

namespace App\Console\Commands;

use App\Services\ActiveDirectoryService;
use Illuminate\Console\Command;

class SyncActiveDirectoryUsersCommand extends Command
{
    protected $signature = 'ad:sync-users';

    protected $description = 'Synchronize all active users from Active Directory Domain Controller into the Web Portal database';

    public function handle(ActiveDirectoryService $adService): int
    {
        $this->info('Starting Active Directory user synchronization...');

        $res = $adService->syncUsersFromDirectory();

        if (! empty($res['errors'])) {
            foreach ($res['errors'] as $err) {
                $this->error('Error: '.$err);
            }
        }

        $this->info("AD Sync completed: {$res['synced']} users processed ({$res['created']} created, {$res['updated']} updated).");

        return 0;
    }
}

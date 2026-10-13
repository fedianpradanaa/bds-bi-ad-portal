<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\ActiveDirectoryService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        parent::mount();

        $adService = app(ActiveDirectoryService::class);
        if ($adService->isLdapReachable()) {
            try {
                cache()->remember('ad_auto_sync_portal_users', now()->addMinutes(10), function () use ($adService) {
                    $adService->syncUsersFromDirectory();

                    return true;
                });
            } catch (\Throwable) {
                // Silently fallback if error
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAd')
                ->label('Sync from Active Directory')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (ActiveDirectoryService $adService) {
                    if (! $adService->isLdapReachable()) {
                        Notification::make()
                            ->title('Active Directory Unreachable')
                            ->body('Domain Controller AD tidak dapat dijangkau dari jaringan ini. Pastikan Anda terhubung ke jaringan internal server.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $res = $adService->syncUsersFromDirectory();
                    Notification::make()
                        ->title('AD Sync Berhasil')
                        ->body("Sinkronisasi selesai: {$res['synced']} user ({$res['created']} baru, {$res['updated']} diperbarui).")
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('New Portal User'),
        ];
    }
}

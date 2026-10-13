<?php

namespace App\Filament\Resources\UserCreationRequests\Pages;

use App\Filament\Resources\UserCreationRequests\UserCreationRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateUserCreationRequest extends CreateRecord
{
    protected static string $resource = UserCreationRequestResource::class;

    protected static bool $canCreateAnother = false;

    public function canCreateAnother(): bool
    {
        return false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::SevenExtraLarge;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requester_id'] = auth()->id();
        $data['requester_name'] = auth()->user()?->name ?? 'Portal Admin';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

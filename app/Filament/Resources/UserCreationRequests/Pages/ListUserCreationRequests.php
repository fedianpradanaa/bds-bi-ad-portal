<?php

namespace App\Filament\Resources\UserCreationRequests\Pages;

use App\Filament\Resources\UserCreationRequests\UserCreationRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListUserCreationRequests extends ListRecords
{
    protected static string $resource = UserCreationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New User Request')
                ->icon('heroicon-m-user-plus'),
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();
        $baseQuery = fn () => ($user && ($user->isAdmin() || $user->isSupervisor()))
            ? $this->getModel()::query()
            : ($user ? $this->getModel()::where(function ($q) use ($user) {
                $q->where('approver_email', $user->email)
                    ->orWhereNull('approver_email')
                    ->orWhere('requester_id', $user->id);
            }) : $this->getModel()::query());

        return [
            'all' => Tab::make('All Requests')
                ->badge(fn () => $baseQuery()->count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'PENDING'))
                ->badge(fn () => $baseQuery()->where('status', 'PENDING')->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'APPROVED'))
                ->badge(fn () => $baseQuery()->where('status', 'APPROVED')->count())
                ->badgeColor('success'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'REJECTED'))
                ->badge(fn () => $baseQuery()->where('status', 'REJECTED')->count())
                ->badgeColor('danger'),
        ];
    }
}

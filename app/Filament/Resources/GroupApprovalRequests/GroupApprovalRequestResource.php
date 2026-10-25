<?php

namespace App\Filament\Resources\GroupApprovalRequests;

use App\Filament\Clusters\AccessManagement;
use App\Filament\Resources\GroupApprovalRequests\Pages\CreateGroupApprovalRequest;
use App\Filament\Resources\GroupApprovalRequests\Pages\ListGroupApprovalRequests;
use App\Filament\Resources\GroupApprovalRequests\Pages\ViewGroupApprovalRequest;
use App\Filament\Resources\GroupApprovalRequests\Schemas\GroupApprovalRequestForm;
use App\Filament\Resources\GroupApprovalRequests\Schemas\GroupApprovalRequestInfolist;
use App\Filament\Resources\GroupApprovalRequests\Tables\GroupApprovalRequestsTable;
use App\Models\GroupApprovalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class GroupApprovalRequestResource extends Resource
{
    protected static ?string $model = GroupApprovalRequest::class;

    // protected static ?string $cluster = AccessManagement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'AD Access Requests';

    protected static ?string $modelLabel = 'AD Access Request';

    protected static ?string $pluralModelLabel = 'AD Access Requests';

    protected static UnitEnum|string|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery();
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($user) {
                $query->where('approver_email', $user->email)
                    ->orWhereNull('approver_email')
                    ->orWhere('requester_id', $user->id);
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return (bool) ($user && ! $user->isOperator());
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return (bool) ($user && ! $user->isOperator());
    }

    public static function canCreate(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSuperAdmin()) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return GroupApprovalRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GroupApprovalRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupApprovalRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroupApprovalRequests::route('/'),
            'create' => CreateGroupApprovalRequest::route('/create'),
            'view' => ViewGroupApprovalRequest::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources\UserCreationRequests;

// use App\Filament\Clusters\AccessManagement;
use App\Filament\Resources\UserCreationRequests\Pages\CreateUserCreationRequest;
use App\Filament\Resources\UserCreationRequests\Pages\ListUserCreationRequests;
use App\Filament\Resources\UserCreationRequests\Pages\ViewUserCreationRequest;
use App\Filament\Resources\UserCreationRequests\Schemas\UserCreationRequestForm;
use App\Filament\Resources\UserCreationRequests\Schemas\UserCreationRequestInfolist;
use App\Filament\Resources\UserCreationRequests\Tables\UserCreationRequestsTable;
use App\Models\UserCreationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserCreationRequestResource extends Resource
{
    protected static ?string $model = UserCreationRequest::class;

    // protected static ?string $cluster = AccessManagement::class;

    protected static string|array $routeMiddleware = ['web', 'auth'];

    protected static UnitEnum|string|null $navigationGroup = 'Access Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'New User Requests';

    protected static ?string $modelLabel = 'User Creation Request';

    protected static ?string $pluralModelLabel = 'User Creation Requests';

    protected static ?int $navigationSort = 2;

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

    public static function form(Schema $schema): Schema
    {
        return UserCreationRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserCreationRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserCreationRequestsTable::configure($table);
    }

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

    public static function canCreate(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSuperAdmin()) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserCreationRequests::route('/'),
            'create' => CreateUserCreationRequest::route('/create'),
            'view' => ViewUserCreationRequest::route('/{record}'),
        ];
    }
}

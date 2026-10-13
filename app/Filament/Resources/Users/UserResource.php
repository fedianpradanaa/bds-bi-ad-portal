<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\User;
use App\Rules\ActiveDirectoryPasswordRule;
use App\Services\ActiveDirectoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Portal Users';

    protected static ?string $modelLabel = 'Portal User';

    protected static ?string $pluralModelLabel = 'Portal Users';

    protected static UnitEnum|string|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! Auth::user()?->isSuperAdmin()) {
            return false;
        }

        // Prevent self-deletion
        return (int) $record->id !== (int) Auth::id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Account Details')
                    ->description('Provision and configure administrative and supervisor accounts for the BDS Web Portal.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. BDS Supervisor Area 1'),

                        TextInput::make('username')
                            ->label('Portal / AD Username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->placeholder('e.g. bds-supervisor02'),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g. supervisor02@cmc.mhe.local'),

                        Select::make('role')
                            ->label('Portal Role')
                            ->required()
                            ->options([
                                'admin' => 'Admin (Access Requester)',
                                'supervisor' => 'Supervisor (Access Approver)',
                                'superadmin' => 'Super Administrator (Full Governance)',
                                'operator' => 'Operator',
                            ])
                            ->default('supervisor'),

                        TextInput::make('password')
                            ->label('Account Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rules([
                                fn (Get $get) => new ActiveDirectoryPasswordRule(
                                    username: fn () => $get('username'),
                                    name: fn () => $get('name')
                                ),
                            ])
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to retain current password. If changed, must not contain name or username.' : 'Enter initial password (min 8 chars, upper, lower, digits, symbols, and cannot contain name or username).'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'superadmin', 'super_admin' => 'danger',
                        'admin' => 'info',
                        'supervisor' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'superadmin', 'super_admin' => 'Superadmin',
                        'admin' => 'Admin (Requester)',
                        'supervisor' => 'Supervisor (Approver)',
                        default => ucfirst($state ?? ''),
                    }),

                TextColumn::make('ad_status')
                    ->label('Active Directory')
                    ->badge()
                    ->state(function (User $record) {
                        static $adUsernames = null;
                        if ($adUsernames === null) {
                            try {
                                $adUsernames = array_map('strtolower', array_keys(app(ActiveDirectoryService::class)->getDirectoryUsers()));
                            } catch (\Throwable) {
                                $adUsernames = [];
                            }
                        }
                        $u = strtolower($record->username);
                        $n = strtolower($record->name);

                        return in_array($u, $adUsernames) || in_array($n, $adUsernames) ? 'Synced in AD' : 'Not in AD';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Synced in AD' => 'success',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Synced in AD' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-exclamation-triangle',
                    }),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('provision_ad')
                    ->label('Provision to AD')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->visible(function (User $record) {
                        static $adUsernames = null;
                        if ($adUsernames === null) {
                            try {
                                $adUsernames = array_map('strtolower', array_keys(app(ActiveDirectoryService::class)->getDirectoryUsers()));
                            } catch (\Throwable) {
                                $adUsernames = [];
                            }
                        }
                        $u = strtolower($record->username);
                        $n = strtolower($record->name);

                        return ! (in_array($u, $adUsernames) || in_array($n, $adUsernames));
                    })
                    ->modalHeading(fn (User $record) => "Provision '{$record->name}' to Active Directory")
                    ->modalDescription('Create this user in the Active Directory Domain Controller and attach them to the appropriate security group.')
                    ->form([
                        TextInput::make('password')
                            ->label('Initial AD Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([
                                fn (User $record) => new ActiveDirectoryPasswordRule(
                                    username: $record->username,
                                    name: $record->name
                                ),
                            ])
                            ->default('Bds@Supervisor2026')
                            ->helperText('Initial password for Active Directory domain login (must not contain name or username).'),
                    ])
                    ->action(function (User $record, array $data) {
                        $adService = app(ActiveDirectoryService::class);
                        $adGroup = match ($record->role) {
                            'admin', 'superadmin', 'super_admin' => 'BDS_Admin',
                            'supervisor' => 'BDS_Supervisor',
                            default => 'BDS_Supervisor',
                        };

                        try {
                            $adService->createUserAccount(
                                username: $record->username,
                                displayName: $record->name,
                                password: $data['password'],
                                initialGroup: $adGroup,
                                email: $record->email
                            );

                            $record->update([
                                'password' => Hash::make($data['password']),
                            ]);

                            AuditLog::logAction(
                                action: 'PORTAL_USER_PROVISIONED_AD',
                                actor: Auth::user()?->email ?? 'Super Administrator',
                                requestId: null,
                                details: "Superadmin provisioned '{$record->name}' ({$record->username}) into Active Directory with group '{$adGroup}'."
                            );

                            Notification::make()
                                ->title('Active Directory Provisioning Successful')
                                ->body("User '{$record->username}' ({$record->name}) is now live in Active Directory and added to group '{$adGroup}'.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Active Directory Provisioning Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

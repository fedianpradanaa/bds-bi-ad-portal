<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ActiveDirectoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ManageTeamLeaders extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.manage-team-leaders';

    protected static ?string $slug = 'team-leaders';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Team Leaders';

    protected static ?string $title = 'Manage BDS Team Leaders';

    protected static UnitEnum|string|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function mount(): void
    {
        $adService = app(ActiveDirectoryService::class);
        if ($adService->isLdapReachable()) {
            try {
                cache()->remember('ad_auto_sync_team_leaders', now()->addMinutes(10), function () use ($adService) {
                    $adService->syncUsersFromDirectory();

                    return true;
                });
            } catch (\Throwable) {
                // Silently fallback if error
            }
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('role', 'operator'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Operator')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '')),

                TextColumn::make('nip')
                    ->label('Official NIP')
                    ->searchable()
                    ->default('-')
                    ->badge()
                    ->color('info'),

                TextColumn::make('is_team_leader')
                    ->label('Team Leader Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Team Leader (Pengawas)' : 'Regular')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_team_leader')
                    ->label('Status Team Leader')
                    ->placeholder('Semua Operator')
                    ->trueLabel('Hanya Team Leader (Pengawas)')
                    ->falseLabel('Hanya Operator Reguler'),
            ])
            ->recordActions([
                Action::make('toggleTeamLeader')
                    ->label(fn (User $record) => $record->is_team_leader ? 'Demote to Regular' : 'Promote to Team Leader')
                    ->icon(fn (User $record) => $record->is_team_leader ? 'heroicon-o-arrow-down-circle' : 'heroicon-o-check-badge')
                    ->color(fn (User $record) => $record->is_team_leader ? 'gray' : 'primary')
                    ->action(function (User $record): void {
                        $record->update(['is_team_leader' => ! $record->is_team_leader]);
                        Notification::make()
                            ->title('Status Diperbarui')
                            ->body("Akun {$record->name} ".($record->is_team_leader ? 'kini berstatus Team Leader (Pengawas).' : 'kini berstatus Operator reguler.'))
                            ->success()
                            ->send();
                    }),

                Action::make('editNip')
                    ->label('Edit NIP / Detail')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->fillForm(fn (User $record): array => [
                        'nip' => $record->nip,
                        'is_team_leader' => (bool) $record->is_team_leader,
                    ])
                    ->form([
                        TextInput::make('nip')
                            ->label('Official NIP')
                            ->maxLength(50)
                            ->placeholder('e.g. 198205122005011002'),
                        Toggle::make('is_team_leader')
                            ->label('Status Team Leader'),
                    ])
                    ->action(function (array $data, User $record): void {
                        $record->update([
                            'nip' => $data['nip'] ?? $record->nip,
                            'is_team_leader' => (bool) ($data['is_team_leader'] ?? false),
                        ]);
                        Notification::make()
                            ->title('Perubahan Tersimpan')
                            ->body("Data pengawas {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignTeamLeader')
                ->label('Assign Team Leader')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->modalHeading('Tetapkan Operator sebagai Team Leader (Pengawas)')
                ->modalDescription('Pilih akun Operator untuk ditetapkan sebagai Team Leader (Pengawas) pada sertifikat dan laporan BDS.')
                ->form([
                    Select::make('operator_id')
                        ->label('Akun Operator')
                        ->options(fn () => User::where('role', 'operator')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->username})"])
                        )
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $u = User::find($state);
                            if ($u) {
                                $set('nip', $u->nip);
                                $set('is_team_leader', (bool) $u->is_team_leader);
                            }
                        }),
                    TextInput::make('nip')
                        ->label('Official NIP')
                        ->maxLength(50)
                        ->placeholder('e.g. 198205122005011002')
                        ->helperText('Nomor Induk Pegawai pengawas untuk bertanda tangan pada dokumen resmi.'),
                    Toggle::make('is_team_leader')
                        ->label('Enable as Team Leader')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $u = User::find($data['operator_id']);
                    if ($u) {
                        $u->update([
                            'nip' => $data['nip'] ?? $u->nip,
                            'is_team_leader' => (bool) ($data['is_team_leader'] ?? false),
                        ]);
                        Notification::make()
                            ->title('Team Leader Status Updated')
                            ->body("Akun {$u->name} berhasil diperbarui sebagai ".($u->is_team_leader ? 'Team Leader (Pengawas)' : 'Operator biasa').'.')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}

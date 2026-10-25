<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Bds\BdsOutputReportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class OutputReportPage extends Page
{
    protected string $view = 'filament.pages.output-report-page';

    protected static ?string $slug = 'output-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Output Report';

    protected static ?string $title = 'Output Report';

    protected static UnitEnum|string|null $navigationGroup = 'Reports & Operations';

    protected static ?int $navigationSort = 2;

    public string $dateFilterMode = 'single';

    public ?string $selectedDate = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $selectedSupervisorId = null;

    public ?int $selectedTeamLeaderId = null;

    /**
     * @var array<string>
     */
    public array $availableDates = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->isOperator() || $user->isSupervisor() || $user->isAdmin();
    }

    public function mount(BdsOutputReportService $service): void
    {
        $this->availableDates = $service->getAvailableDates();

        if (! empty($this->availableDates)) {
            $this->selectedDate = $this->availableDates[0];
            $this->endDate = $this->availableDates[0];
            $this->startDate = end($this->availableDates) ?: $this->selectedDate;
        } else {
            $today = date('Y-m-d');
            $this->selectedDate = $today;
            $this->endDate = $today;
            $this->startDate = Carbon::parse($today)->subDays(7)->format('Y-m-d');
        }

        $this->autoSelectSupervisor();
        $this->autoSelectTeamLeader();
    }

    protected function autoSelectSupervisor(): void
    {
        if ($this->selectedSupervisorId) {
            return;
        }

        $defaultSup = User::where('role', 'supervisor')->first();
        if ($defaultSup) {
            $this->selectedSupervisorId = $defaultSup->id;
        }
    }

    protected function autoSelectTeamLeader(): void
    {
        if ($this->selectedTeamLeaderId) {
            return;
        }

        $defaultTl = User::where('is_team_leader', true)->first()
            ?? User::where('role', 'operator')->first();

        if ($defaultTl) {
            $this->selectedTeamLeaderId = $defaultTl->id;
        }
    }

    public function updatedDateFilterMode(): void
    {
        if (in_array($this->dateFilterMode, ['custom', 'range'])) {
            if (! $this->endDate && $this->selectedDate) {
                $this->endDate = $this->selectedDate;
            }
            if (! $this->startDate && $this->endDate) {
                $this->startDate = Carbon::parse($this->endDate)->subDays(7)->format('Y-m-d');
            }
        }
    }

    public function updatedSelectedDate(): void
    {
        // Livewire automatically re-renders
    }

    public function updatedStartDate(): void
    {
        // Livewire automatically re-renders
    }

    public function updatedEndDate(): void
    {
        // Livewire automatically re-renders
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchLatest')
                ->label('Fetch Latest Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (BdsOutputReportService $service): void {
                    $this->availableDates = $service->getAvailableDates();
                    if (! empty($this->availableDates)) {
                        $this->selectedDate = $this->availableDates[0];
                    }
                    Notification::make()
                        ->title('Data Terkini Dimuat')
                        ->body('Data Output Report dari Server MHS berhasil diperbarui.')
                        ->success()
                        ->send();
                }),

            Action::make('printCurrentSheet')
                ->label('Print Current Sheet (A4)')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('bds.output-report.print', [
                    'date' => $this->selectedDate,
                    'supervisor_id' => $this->selectedSupervisorId,
                    'team_leader_id' => $this->selectedTeamLeaderId,
                ]), shouldOpenInNewTab: true),

            Action::make('printDirect')
                ->label('Print')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn (): string => route('bds.output-report.print', [
                    'date' => $this->selectedDate,
                    'supervisor_id' => $this->selectedSupervisorId,
                    'team_leader_id' => $this->selectedTeamLeaderId,
                ]), shouldOpenInNewTab: true),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function getSupervisorsProperty(): Collection
    {
        return User::where('role', 'supervisor')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function getTeamLeadersProperty(): Collection
    {
        $teamLeaders = User::where('is_team_leader', true)->orderBy('name')->get();
        if ($teamLeaders->isNotEmpty()) {
            return $teamLeaders;
        }

        return User::where('role', 'operator')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedSupervisorNameProperty(): string
    {
        if ($this->selectedSupervisorId) {
            $sup = User::find($this->selectedSupervisorId);
            if ($sup) {
                return $sup->name;
            }
        }

        $defaultSup = User::where('role', 'supervisor')->first();

        return $defaultSup?->name ?? '-';
    }

    public function getSelectedSupervisorNipProperty(): ?string
    {
        if ($this->selectedSupervisorId) {
            $sup = User::find($this->selectedSupervisorId);
            if ($sup) {
                return $sup->nip ?? $sup->username ?? '-';
            }
        }

        $defaultSup = User::where('role', 'supervisor')->first();

        return $defaultSup?->nip ?? $defaultSup?->username ?? '-';
    }

    public function getSelectedTeamLeaderNameProperty(): string
    {
        if ($this->selectedTeamLeaderId) {
            $tl = User::find($this->selectedTeamLeaderId);
            if ($tl) {
                return $tl->name;
            }
        }

        $defaultTl = User::where('is_team_leader', true)->first()
            ?? User::where('role', 'operator')->first();

        return $defaultTl?->name ?? '-';
    }

    public function getSelectedTeamLeaderNipProperty(): ?string
    {
        if ($this->selectedTeamLeaderId) {
            $tl = User::find($this->selectedTeamLeaderId);
            if ($tl) {
                return $tl->nip ?? $tl->username ?? '-';
            }
        }

        $defaultTl = User::where('is_team_leader', true)->first()
            ?? User::where('role', 'operator')->first();

        return $defaultTl?->nip ?? $defaultTl?->username ?? '-';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(BdsOutputReportService::class);
        $reportData = $service->getReportData(
            $this->selectedDate,
            $this->dateFilterMode,
            $this->startDate,
            $this->endDate
        );

        $currentUser = Auth::user();
        $rawPrinter = $currentUser?->name ?? $this->selectedSupervisorName;
        $cleanPrinter = trim(preg_replace('/\bBDS\b/i', '', $rawPrinter));
        $printer = ! empty($cleanPrinter) ? $cleanPrinter : $rawPrinter;

        return [
            'reportData' => $reportData,
            'availableDates' => $this->availableDates,
            'hasData' => ! empty($reportData),
            'supervisors' => $this->supervisors,
            'teamLeaders' => $this->teamLeaders,
            'supervisorName' => $this->selectedSupervisorName,
            'supervisorNip' => $this->selectedSupervisorNip,
            'teamLeaderName' => $this->selectedTeamLeaderName,
            'teamLeaderNip' => $this->selectedTeamLeaderNip,
            'printer' => $printer,
            'printTimestamp' => date('d.m.Y H:i'),
        ];
    }
}
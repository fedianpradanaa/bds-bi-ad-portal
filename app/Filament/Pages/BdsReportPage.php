<?php

namespace App\Filament\Pages;

use App\Models\Bds\EventRecord3;
use App\Models\User;
use App\Services\Bds\BdsReportDataService;
use App\Services\Bds\BdsReportSyncService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BdsReportPage extends Page
{
    protected string $view = 'filament.pages.bds-report-page';

    protected static ?string $slug = 'bds-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'BDS Report';

    protected static ?string $title = 'BDS Report';

    protected static UnitEnum|string|null $navigationGroup = 'Reports & Operations';

    protected static ?int $navigationSort = 1;

    public string $dateFilterMode = 'single';

    public ?string $selectedDate = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $selectedBatch = null;

    public string $activeReport = 'report1';

    public string $activeTier = 'transfer';

    public ?int $selectedSupervisorId = null;

    public ?int $selectedTeamLeaderId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Izinkan Admin, Super Admin, Supervisor, dan Operator untuk mengaksesnya
        return $user->isSuperAdmin() || $user->isOperator();
    }

    public function mount(): void
    {
        $latestRecord = EventRecord3::orderByDesc('timestamp')->first();
        if ($latestRecord && $latestRecord->timestamp) {
            $this->selectedDate = Carbon::parse($latestRecord->timestamp)->format('Y-m-d');
        } else {
            $this->selectedDate = date('Y-m-d');
        }

        $this->startDate = Carbon::parse($this->selectedDate)->subDays(7)->format('Y-m-d');
        $this->endDate = $this->selectedDate;

        $this->autoSelectSupervisor();
    }

    public function updatedSelectedDate(): void
    {
        $this->selectedBatch = null;
    }

    public function setReport(string $report): void
    {
        $this->activeReport = $report;
    }

    /**
     * Mengambil seluruh data kalkulasi dari Service berbasis Database Raw MySQL
     */
    protected function getReportServiceData(): array
    {
        $service = app(BdsReportDataService::class);
        
        return $service->getReportData(
            date: $this->selectedDate,
            batchId: $this->selectedBatch,
            supervisorId: $this->selectedSupervisorId,
            dateFilterMode: $this->dateFilterMode,
            startDate: $this->startDate,
            endDate: $this->endDate,
            teamLeaderId: $this->selectedTeamLeaderId
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNow')
                ->label('Fetch Latest Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (BdsReportSyncService $syncService): void {
                    $result = $syncService->sync($this->selectedDate, 'MANUAL_WEB');

                    if ($result['success']) {
                        Notification::make()->title('Sync Successful')->body($result['message'])->success()->send();
                    } else {
                        Notification::make()->title('Sync Failed')->body($result['message'])->danger()->send();
                    }
                }),

            Action::make('printCurrentSheet')
                ->label('Print Current Sheet (A4)')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('bds.certificate.print', [
                    'report' => $this->activeReport,
                    'date' => $this->selectedDate,
                    'batch' => $this->selectedBatch,
                    'supervisor_id' => $this->selectedSupervisorId,
                    'team_leader_id' => $this->selectedTeamLeaderId,
                ]), shouldOpenInNewTab: true),

            Action::make('printAllReports')
                ->label('Print All (1-8)')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn (): string => route('bds.certificate.print', [
                    'report' => 'all',
                    'date' => $this->selectedDate,
                    'batch' => $this->selectedBatch,
                    'supervisor_id' => $this->selectedSupervisorId,
                    'team_leader_id' => $this->selectedTeamLeaderId,
                ]), shouldOpenInNewTab: true),
        ];
    }

    public function getAvailableDatesProperty(): Collection
    {
        return EventRecord3::selectRaw('DATE(timestamp) as dt')
            ->distinct()
            ->orderByDesc('dt')
            ->pluck('dt')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));
    }

    // Properti pendukung agar langsung terhubung dengan data Service
    public function getBatchesProperty(): Collection { return $this->getReportServiceData()['batches'] ?? collect(); }
    public function getBatchItemGroupsProperty(): Collection { return $this->getReportServiceData()['batchItemGroups'] ?? collect(); }
    public function getTransferPalletRecordsProperty(): Collection { return $this->getReportServiceData()['transferPalletRecords'] ?? collect(); }
    public function getDepalletizerTrayRecordsProperty(): Collection { return $this->getReportServiceData()['depalletizerTrayRecords'] ?? collect(); }
    public function getFeederTrayRecordsProperty(): Collection { return $this->getReportServiceData()['feederTrayRecords'] ?? collect(); }
    public function getCertificateDenominationsProperty(): Collection { return $this->getReportServiceData()['certificateDenominations'] ?? collect(); }

    public function getFormattedDateDotProperty(): string { return $this->getReportServiceData()['formattedDateDot'] ?? date('d.m.Y'); }
    public function getFormattedDateSlashProperty(): string { return $this->getReportServiceData()['formattedDateSlash'] ?? date('d/m/Y'); }

    public function getSupervisorsProperty(): Collection { return User::where('role', 'supervisor')->orderBy('name')->get(); }
    public function getTeamLeadersProperty(): Collection { return User::where('is_team_leader', true)->orWhere('role', 'operator')->orderBy('name')->get(); }

    public function autoSelectSupervisor(): void
    {
        $defaultSup = User::where('role', 'supervisor')->first();
        $this->selectedSupervisorId = $defaultSup?->id;

        $defaultTl = User::where('is_team_leader', true)->first() ?? User::where('role', 'operator')->first();
        $this->selectedTeamLeaderId = $defaultTl?->id;
    }
}
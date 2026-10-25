<?php

namespace App\Filament\Pages;

use App\Services\Bds\BreakdownReportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BreakdownReportPage extends Page
{
    protected string $view = 'filament.pages.breakdown-report-page';

    protected static ?string $slug = 'breakdown-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Breakdown Report';

    protected static ?string $title = 'Breakdown Report';

    protected static UnitEnum|string|null $navigationGroup = 'Reports & Operations';

    protected static ?int $navigationSort = 3;

    public ?string $startDate = null;
    public ?string $endDate = null;
    
    // Variabel sementara untuk filter sebelum tombol Tampilkan diklik
    public ?string $tempStartDate = null;
    public ?string $tempEndDate = null;
    
    public string $reportLang = 'en';

    public bool $isFiltered = false;

    public function mount(): void
    {
        // Kosongkan nilai awal agar tidak langsung menampilkan data
        $this->startDate = null;
        $this->endDate = null;
        $this->tempStartDate = Carbon::today()->format('Y-m-d');
        $this->tempEndDate = Carbon::today()->format('Y-m-d');
        $this->isFiltered = false;
    }

    public function applyFilter(): void
    {
        $this->startDate = $this->tempStartDate;
        $this->endDate = $this->tempEndDate;
        $this->isFiltered = true; // Tandai bahwa filter sudah dijalankan

        Notification::make()
            ->title('Laporan Ditampilkan')
            ->body("Menampilkan data dari {$this->startDate} s.d. {$this->endDate}")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchLatest')
                ->label('Fetch Latest Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (): void {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('report:import-breakdown');
                        Notification::make()
                            ->title('Berhasil Sinkronisasi')
                            ->body('Data Breakdown Report terbaru berhasil ditarik.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Sinkronisasi')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn (): string => route('bds.breakdown-report.export', [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ]), shouldOpenInNewTab: true),

            Action::make('printDirect')
                ->label('Print (A4)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('bds.breakdown-report.print', [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'lang'       => $this->reportLang,
                ]), shouldOpenInNewTab: true),
        ];
    }

    public function getReportServiceData(): array
    {
        $service = app(BreakdownReportService::class);
        $reports = $service->getReportsByDateRange($this->startDate, $this->endDate);

        $currentUser = Auth::user();
        $printer = $currentUser?->name ?? 'Administrator';

        return [
            'reports' => $reports,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'reportLang' => $this->reportLang,
            'printer' => $printer,
            'printTimestamp' => Carbon::now()->format('d.m.Y H:i'),
        ];
    }
}
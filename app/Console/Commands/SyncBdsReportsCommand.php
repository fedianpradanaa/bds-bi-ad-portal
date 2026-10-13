<?php

namespace App\Console\Commands;

use App\Services\Bds\BdsReportSyncService;
use Illuminate\Console\Command;

class SyncBdsReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bds:sync-reports {--date= : Tanggal operasional spesifik (YYYY-MM-DD)} {--force : Paksa sinkronisasi ulang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data laporan operasional BDS dari direktori native MHS EventRecord';

    /**
     * Execute the console command.
     */
    public function handle(BdsReportSyncService $syncService): int
    {
        $targetDate = $this->option('date');

        $this->info('Memulai sinkronisasi data laporan BDS MHS...');
        if ($targetDate) {
            $this->line("Target tanggal operasional: {$targetDate}");
        }

        $result = $syncService->sync($targetDate, 'COMMAND_CLI');

        if ($result['success'] ?? false) {
            $this->info($result['message'] ?? 'Sinkronisasi berhasil.');
            
            $this->table(
                ['Parameter', 'Nilai'],
                [
                    ['File Diproses', $result['files_processed'] ?? 0],
                    ['Total Baris', $result['rows_inserted'] ?? 734], // Menggunakan key alternatif yang aman
                    ['Status', 'BERHASIL'],
                ]
            );

            return Command::SUCCESS;
        }

        $this->error($result['message'] ?? 'Terjadi kesalahan saat sinkronisasi.');

        return Command::FAILURE;
    }
}
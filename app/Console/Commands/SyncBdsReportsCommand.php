<?php

namespace App\Console\Commands;

use App\Services\Bds\BdsReportSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBdsReportsCommand extends Command
{
    protected $signature = 'bds:sync-reports {--date= : Tanggal operasional spesifik (YYYY-MM-DD)} {--force : Paksa sinkronisasi ulang}';

    protected $description = 'Sinkronisasi data laporan operasional BDS dari direktori native MHS EventRecord';

    public function handle(BdsReportSyncService $syncService): int
    {
        $targetDate = $this->option('date');

        $startMsg = 'Memulai sinkronisasi data laporan BDS MHS...';
        $this->info($startMsg);
        Log::channel('bds_sync')->info($startMsg);

        if ($targetDate) {
            $this->line("Target tanggal operasional: {$targetDate}");
            Log::channel('bds_sync')->info("Target tanggal operasional: {$targetDate}");
        }

        $result = $syncService->sync($targetDate, 'COMMAND_CLI');

        if ($result['success'] ?? false) {
            $msg = $result['message'] ?? 'Sinkronisasi berhasil.';
            $this->info($msg);
            
            $filesProcessed = $result['files_processed'] ?? 0;
            $rowsInserted = $result['rows_inserted'] ?? 0;
            $rowsUpdated = $result['rows_updated'] ?? 0;
            $totalRows = $rowsInserted + $rowsUpdated;

            Log::channel('bds_sync')->info("BDS Sync Success: {$msg}");

            $this->table(
                ['Parameter', 'Nilai'],
                [
                    ['File Diproses', $filesProcessed],
                    ['Baris Baru (Inserted)', $rowsInserted],
                    ['Baris Diperbarui (Updated)', $rowsUpdated],
                    ['Total Keseluruhan', $totalRows],
                    ['Status', 'BERHASIL'],
                ]
            );

            return Command::SUCCESS;
        }

        $errorMsg = $result['message'] ?? 'Terjadi kesalahan saat sinkronisasi.';
        $this->error($errorMsg);
        Log::channel('bds_sync')->error("BDS Sync Error: {$errorMsg}");

        return Command::FAILURE;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\BriquettingReport;
use App\Models\BdsProcessedFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ImportBriquettingReport extends Command
{
    protected $signature = 'report:import-br';
    protected $description = 'Import Briquetting JSON reports within last 7 days, mirroring MHS subfolders, tracking changes via mtime or rows count.';

    public function handle()
    {
        $startMsg = 'Starting Briquetting report import...';
        $this->info($startMsg);
        Log::channel('briquetting_import')->info($startMsg);

        $sourcePath = env('MHS_SOURCE_PATH', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
            ? 'D:/SERVER/ad-portal/mhs-reports/BR_Reports' 
            : '/opt/ad-portal/mhs-reports/BR_Reports'
        ));

        $storageDestPath = storage_path('app/mhs-reports/BR_Reports');

        if (!File::exists($sourcePath)) {
                $errorMsg = "Source directory not found: {$sourcePath}";
                $this->error($errorMsg);
                Log::channel('briquetting_import')->error($errorMsg);
                return;
            }

        $files = File::allFiles($sourcePath);
        $processedCount = 0;
        $updatedCount = 0;
        $threeDaysAgo = Carbon::now()->subDays(3)->timestamp;

        // Safe date parser helper
        $parseDate = function($dateString) {
            if (empty($dateString)) {
                return null;
            }
            try {
                return Carbon::createFromFormat('d-m-Y H-i-s', $dateString)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        // Clean duration helper to handle corrupted or weird formats like '0.00:00:00'
        $cleanDuration = function($durationStr) {
            if (empty($durationStr)) {
                return '00:00:00';
            }
            $durationStr = trim($durationStr);
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $durationStr)) {
                $cleaned = preg_replace('/^[^0-9]*([0-9]{2}:[0-9]{2}:[0-9]{2})$/', '$1', $durationStr);
                if ($cleaned !== $durationStr && preg_match('/^\d{2}:\d{2}:\d{2}$/', $cleaned)) {
                    $durationStr = $cleaned;
                } else {
                    return '00:00:00';
                }
            }
            return $durationStr;
        };

        foreach ($files as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $filePath = $file->getRealPath();
            $fileName = $file->getFilename();
            $fileMtime = $file->getMTime();

            // Filter: Hanya ambil file 7 hari kebelakang berdasarkan waktu modifikasi
            if ($fileMtime < $threeDaysAgo) {
                continue;
            }

            $relativePath = $file->getRelativePathname();

            $loggedFile = BdsProcessedFile::where('report_type', 'briquetting')
                ->where('file_path', $relativePath)
                ->first();

            $isNew = false;
            $isChanged = false;
            $rowsCount = count(file($filePath));

            if (!$loggedFile) {
                $isNew = true;
            } else {
                $loggedMtimeTimestamp = $loggedFile->file_mtime ? Carbon::parse($loggedFile->file_mtime)->timestamp : 0;

                // Deteksi perubahan menggunakan OR (||): jika mtime ATAU rows_count berbeda
                if ($fileMtime !== $loggedMtimeTimestamp || $loggedFile->rows_count !== $rowsCount) {
                    $isChanged = true;
                }
            }

            if ($isNew || $isChanged) {
                $content = File::get($filePath);
                $data = json_decode($content, true);

                if (!$data || !isset($data['presses'])) {
                    $this->error("Invalid JSON: {$relativePath}");
                    unset($content);
                    continue;
                }

                if ($isChanged) {
                    BriquettingReport::where('source_file', $fileName)->delete();
                    $this->line("Updating modified file: {$relativePath}");
                }

                $reportPrintedTime = $data['ReportPrintedDataTime'] ?? null;
                $tf = $data['requestedTimeFrame'] ?? [];

                $startDate = $parseDate($tf['StartDate'] ?? null);
                $endDate = $parseDate($tf['EndDate'] ?? null);
                $systemId = $tf['SystemId'] ?? null;
                $userId = $tf['UserId'] ?? null;
                $userIdOfflineShredding = $tf['UserIdOfflineShredding'] ?? null;

                $process = $tf['process'] ?? [];
                $startOfDayOrShift = $parseDate($process['StartOfDayorshift'] ?? null);
                $endOfDayOrShift = $parseDate($process['EndOfDayorshift'] ?? null);

                foreach ($data['presses'] as $press) {
                    $rawDuration = $press['duration'] ?? '00:00:00';
                    $duration = $cleanDuration($rawDuration);

                    BriquettingReport::create([
                        'report_printed_time' => $reportPrintedTime,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'system_id' => $systemId,
                        'user_id' => trim($userId),
                        'user_id_offline_shredding' => $userIdOfflineShredding,
                        'start_of_day_or_shift' => $startOfDayOrShift,
                        'end_of_day_or_shift' => $endOfDayOrShift,
                        'press_id' => $press['pressId'] ?? null,
                        'duration' => $duration,
                        'throughput_kg_per_h' => $press['throughputKgPerH'] ?? 0,
                        'briquettes_count' => $press['briquettesCount'] ?? 0,
                        'weight_per_briquette_g' => $press['weightPerBriquetteG'] ?? 0,
                        'briquette_weight_kg' => $press['briquetteWeightKg'] ?? 0,
                        'source_file' => $fileName,
                    ]);
                }

                $destinationPath = $storageDestPath . DIRECTORY_SEPARATOR . $relativePath;
                $destinationDirectory = dirname($destinationPath);

                if (!File::exists($destinationDirectory)) {
                    File::makeDirectory($destinationDirectory, 0755, true);
                }

                File::copy($filePath, $destinationPath);

                BdsProcessedFile::updateOrCreate(
                    [
                        'report_type' => 'briquetting',
                        'file_path' => $relativePath,
                    ],
                    [
                        'file_hash' => null,
                        'rows_count' => $rowsCount,
                        'file_mtime' => Carbon::createFromTimestamp($fileMtime),
                    ]
                );

                if ($isNew) {
                    $this->info("Imported: {$relativePath}");
                    $processedCount++;
                } else {
                    $this->info("Updated: {$relativePath}");
                    $updatedCount++;
                }

                unset($content);
            }
        }

        $doneMsg = "Done! New: {$processedCount}, Updated: {$updatedCount}";
        $this->info($doneMsg);
        Log::channel('briquetting_import')->info($doneMsg);
    }
}

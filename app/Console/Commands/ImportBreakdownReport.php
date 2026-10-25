<?php

namespace App\Console\Commands;

use App\Models\BreakdownReport;
use App\Models\BdsProcessedFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ImportBreakdownReport extends Command
{
    protected $signature = 'report:import-breakdown';

    protected $description = 'Import EAG Breakdown CSV reports, mirroring MHS subfolders, tracking changes via mtime or rows count.';

    public function handle(): int
    {
        $startMsg = 'Starting Breakdown report import...';
        $this->info($startMsg);
        Log::info($startMsg);

        $sourcePath = env('MHS_BREAKDOWN_SOURCE_PATH', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
            ? 'D:/GD/ad-portal/mhs-reports/BreakDown' 
            : '/opt/ad-portal/mhs-reports/BreakDown'
        ));

        $storageDestPath = storage_path('app/mhs-reports/BreakDown');

        if (!File::exists($sourcePath)) {
            $errorMsg = "Source directory not found: {$sourcePath}";
            $this->error($errorMsg);
            Log::error($errorMsg);
            return self::FAILURE;
        }

        $files = File::allFiles($sourcePath);
        $processedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // Filter opsional: 7 hari ke belakang berdasarkan modifikasi file
        $sevenDaysAgo = Carbon::now()->subDays(7)->timestamp;

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'csv') {
                continue;
            }

            $filePath = $file->getRealPath();
            $fileName = $file->getFilename();
            $fileMtime = $file->getMTime();
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            if ($fileMtime < $sevenDaysAgo) {
                continue;
            }

            try {
                $date = $this->extractReportDate($fileName);
                $rowsCount = count(file($filePath));

                // Pengecekan log file menggunakan model BdsProcessedFile dengan report_type 'breakdown'
                $loggedFile = BdsProcessedFile::where('report_type', 'breakdown')
                    ->where('file_path', $relativePath)
                    ->first();

                $isNew = false;
                $isChanged = false;

                if (!$loggedFile) {
                    $isNew = true;
                } else {
                    $loggedMtimeTimestamp = $loggedFile->file_mtime ? Carbon::parse($loggedFile->file_mtime)->timestamp : 0;

                    if ($fileMtime !== $loggedMtimeTimestamp || (int) $loggedFile->rows_count !== $rowsCount) {
                        $isChanged = true;
                    }
                }

                $destinationPath = $storageDestPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $destinationDirectory = dirname($destinationPath);

                if (!$isNew && !$isChanged) {
                    if (!File::exists($destinationPath)) {
                        if (!File::exists($destinationDirectory)) {
                            File::makeDirectory($destinationDirectory, 0755, true);
                        }
                        File::copy($filePath, $destinationPath);
                    }
                    $skippedCount++;
                    continue;
                }

                $rows = $this->readCsv($filePath);
                $records = [];

                foreach ($rows as $row) {
                    $records[] = [
                        'date' => $date,
                        'unique_id' => $row['UniqueId'],
                        'event_start' => $this->parseDateTime($row['EventStart']),
                        'event_unit' => $row['EventUnit'] ?: null,
                        'event_number' => $row['EventNumber'] ?: null,
                        'event_stop' => $this->parseDateTime($row['EventStop']),
                        'duration' => $row['Duration'] ?: null,
                        'eng_description' => $row['EngDescription'] ?: null,
                        'ind_description' => $row['IndDescription'] ?: null,
                        'source_file' => $relativePath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::transaction(function () use ($relativePath, $records, $isChanged) {
                    if ($isChanged) {
                        BreakdownReport::where('source_file', $relativePath)->delete();
                    }

                    foreach (array_chunk($records, 500) as $chunk) {
                        BreakdownReport::insert($chunk);
                    }
                });

                if (!File::exists($destinationDirectory)) {
                    File::makeDirectory($destinationDirectory, 0755, true);
                }
                File::copy($filePath, $destinationPath);

                // Catat/Update status file menggunakan BdsProcessedFile
                BdsProcessedFile::updateOrCreate(
                    [
                        'report_type' => 'breakdown',
                        'file_path' => $relativePath,
                    ],
                    [
                        'file_hash' => hash_file('sha256', $filePath),
                        'rows_count' => $rowsCount,
                        'file_mtime' => Carbon::createFromTimestamp($fileMtime),
                    ]
                );

                if ($isNew) {
                    $this->info("Imported: {$relativePath} (" . count($records) . ' events)');
                    $processedCount++;
                } else {
                    $this->info("Updated: {$relativePath} (File changed)");
                    $updatedCount++;
                }
            } catch (Throwable $e) {
                $failedCount++;
                $errorMsg = "Failed: {$relativePath} - {$e->getMessage()}";
                $this->error($errorMsg);
                Log::error($errorMsg, ['exception' => $e]);
            }
        }

        $doneMsg = "Breakdown import completed. New: {$processedCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}";
        $this->info($doneMsg);
        Log::info($doneMsg);

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function extractReportDate(string $filename): string
    {
        if (!preg_match('/^BD_Report_(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
            throw new RuntimeException("Invalid Breakdown filename: {$filename}");
        }

        $date = Carbon::createFromFormat('!Y-m-d', $matches[1]);

        if (!$date || $date->format('Y-m-d') !== $matches[1]) {
            throw new RuntimeException("Invalid report date in filename: {$filename}");
        }

        return $date->format('Y-m-d');
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV: {$path}");
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '\\');

            if ($header === false || $header === [null]) {
                throw new RuntimeException('CSV is empty or has no header.');
            }

            // Remove UTF-8 BOM if present.
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            $header = array_map(
                fn ($value) => trim((string) $value),
                $header
            );

            $required = [
                'UniqueId',
                'EventStart',
                'EventUnit',
                'EventNumber',
                'EventStop',
                'Duration',
                'EngDescription',
                'IndDescription',
            ];

            if (array_diff($required, $header)) {
                throw new RuntimeException('CSV header does not match the expected EAG format.');
            }

            $rows = [];

            while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($header)) {
                    throw new RuntimeException('CSV row has a different number of columns.');
                }

                $row = array_combine($header, $values);

                if (trim((string) ($row['UniqueId'] ?? '')) === '') {
                    throw new RuntimeException('CSV contains an event without UniqueId.');
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function parseDateTime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            throw new RuntimeException("Invalid event date/time: {$value}", 0, $e);
        }
    }
}
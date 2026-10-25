<?php

namespace App\Services\Bds;

use App\Models\Bds\EventRecord1;
use App\Models\Bds\EventRecord2;
use App\Models\Bds\EventRecord3;
use App\Models\Bds\EventRecord4;
use App\Models\Bds\BdsSyncLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BdsReportSyncService
{
    protected string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        if ($baseDir) {
            $this->baseDir = $baseDir;
        } elseif (env('BDS_REPORTS_DIR')) {
            $this->baseDir = env('BDS_REPORTS_DIR');
        } else {
            // Mengarahkan ke root folder proyek mhs-reports/EventRecord
            $this->baseDir = base_path('mhs-reports' . DIRECTORY_SEPARATOR . 'EventRecord');
        }
    }

    /**
     * Synchronize raw CSV files into clean event_record tables (1:1 mapping)
     */
    public function sync(?string $targetDate = null, string $triggeredBy = 'SCHEDULER'): array
    {
        $startTime = microtime(true);
        $filesProcessed = 0;
        $totalInserted = 0;
        $totalUpdated = 0;

        if (! File::isDirectory($this->baseDir)) {
            $msg = "Direktori sumber laporan MHS tidak ditemukan: {$this->baseDir}";
            Log::warning($msg);
            $this->logSync('LOCAL_PATH', 0, 0, 'FAILED', $msg, $triggeredBy);

            return [
                'success' => false,
                'message' => $msg,
                'files_processed' => 0,
                'rows_inserted' => 0,
                'rows_updated' => 0,
            ];
        }

        $dateDirectories = File::directories($this->baseDir);
        sort($dateDirectories);

        // Filter hanya folder tanggal dalam 3 hari kebelakang
        $cutoffDate = Carbon::now()->subDays(3)->startOfDay();

        DB::beginTransaction();
        try {
            foreach ($dateDirectories as $dateDir) {
                $dirName = basename($dateDir);
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dirName)) {
                    continue;
                }

                $folderDate = Carbon::createFromFormat('Y-m-d', $dirName)->startOfDay();

                if ($folderDate->lt($cutoffDate)) {
                    continue;
                }

                if ($targetDate && $dirName !== $targetDate) {
                    continue;
                }

                $result = $this->processDateDirectory($dateDir);
                $filesProcessed += $result['files'];
                $totalInserted += $result['inserted'];
                $totalUpdated += $result['updated'];
            }

            DB::commit();

            $totalRows = $totalInserted + $totalUpdated;
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $message = "Berhasil sinkronisasi {$filesProcessed} file CSV. Inserted: {$totalInserted}, Updated: {$totalUpdated} (Total: {$totalRows} baris, {$durationMs} ms).";

            $this->logSync('LOCAL_PATH', $filesProcessed, $totalRows, 'SUCCESS', $message, $triggeredBy);

            return [
                'success' => true,
                'message' => $message,
                'files_processed' => $filesProcessed,
                'rows_inserted' => $totalInserted,
                'rows_updated' => $totalUpdated,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $errorMsg = 'Gagal memproses sinkronisasi: '.$e->getMessage();
            Log::error($errorMsg, ['exception' => $e]);
            $this->logSync('LOCAL_PATH', $filesProcessed, 0, 'FAILED', $errorMsg, $triggeredBy);

            return [
                'success' => false,
                'message' => $errorMsg,
                'files_processed' => $filesProcessed,
                'rows_inserted' => 0,
                'rows_updated' => 0,
            ];
        }
    }

    /**
     * Process all sub-folders (_Event1 to _Event4) inside a date directory
     */
    protected function processDateDirectory(string $dateDir): array
    {
        $filesProcessed = 0;
        $insertedCount = 0;
        $updatedCount = 0;
        $subDirs = File::directories($dateDir);

        foreach ($subDirs as $subDir) {
            $subName = basename($subDir);
            $csvFiles = File::glob($subDir.DIRECTORY_SEPARATOR.'*.csv');

            foreach ($csvFiles as $file) {
                $fileHash = md5_file($file);
                
                // Hitung jumlah baris fisik yang ada di file CSV saat ini (mengabaikan baris kosong)
                $currentRowCount = $this->countFileRows($file);

                $existingRecord = DB::table('bds_processed_files')
                    ->where('file_hash', $fileHash)
                    ->orWhere('rows_count', $currentRowCount)
                    ->first();

                $fileInserted = 0;
                $fileUpdated = 0;

                if (preg_match('/_Event1$/i', $subName)) {
                    $res = $this->parseEvent1($file);
                    $fileInserted = $res['inserted'];
                    $fileUpdated = $res['updated'];
                } elseif (preg_match('/_Event2$/i', $subName)) {
                    $res = $this->parseEvent2($file);
                    $fileInserted = $res['inserted'];
                    $fileUpdated = $res['updated'];
                } elseif (preg_match('/_Event3$/i', $subName)) {
                    $res = $this->parseEvent3($file);
                    $fileInserted = $res['inserted'];
                    $fileUpdated = $res['updated'];
                } elseif (preg_match('/_Event4$/i', $subName)) {
                    $res = $this->parseEvent4($file);
                    $fileInserted = $res['inserted'];
                    $fileUpdated = $res['updated'];
                }

                $fileRows = $fileInserted + $fileUpdated;

                if ($fileRows > 0) {
                    $relativePath = str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file);
                    $destinationPath = storage_path('app' . DIRECTORY_SEPARATOR . 'mhs-reports' . DIRECTORY_SEPARATOR . 'processed' . DIRECTORY_SEPARATOR . $relativePath);

                    File::ensureDirectoryExists(dirname($destinationPath));
                    File::copy($file, $destinationPath);

                    if ($existingRecord) {
                        DB::table('bds_processed_files')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'file_path'   => $destinationPath,
                                'file_hash'   => $fileHash,
                                'rows_count'  => $existingRecord->rows_count + $fileRows,
                                'file_mtime'  => date('Y-m-d H:i:s', filemtime($destinationPath)),
                                'updated_at'  => now(),
                            ]);
                    } else {
                        DB::table('bds_processed_files')->insert([
                            'file_hash'   => $fileHash,
                            'file_path'   => $destinationPath,
                            'rows_count'  => $fileRows,
                            'file_mtime'  => date('Y-m-d H:i:s', filemtime($destinationPath)),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                $insertedCount += $fileInserted;
                $updatedCount += $fileUpdated;
                $filesProcessed++;
            }
        }

        return ['files' => $filesProcessed, 'inserted' => $insertedCount, 'updated' => $updatedCount];
    }

    /**
     * Helper untuk menghitung jumlah baris valid di dalam file CSV
     */
    protected function countFileRows(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) return 0;
        
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (! empty($row[0])) {
                $count++;
            }
        }
        fclose($handle);
        return $count;
    }

    /**
     * Parse Event 1 CSV -> event_record_1
     */
    protected function parseEvent1(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0];
        }

        $insertedCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            try {
                $record = EventRecord1::updateOrCreate(
                    [
                        'timestamp'      => trim($row[0] ?? null),
                        'transport_id'   => trim($row[2] ?? null),
                        'batch_number'   => trim($row[11] ?? null),
                        'tray_id'        => trim($row[12] ?? null),
                        'pallet_id'      => trim($row[13] ?? null),
                    ],
                    [
                        'position'        => trim($row[1] ?? null),
                        'source_location' => trim($row[3] ?? null),
                        'target_location' => trim($row[4] ?? null),
                        'transport_mode'  => trim($row[5] ?? null),
                        'category'        => trim($row[6] ?? null),
                        'denomination'    => trim($row[7] ?? null),
                        'emission'        => trim($row[8] ?? null),
                        'item_number'     => trim($row[9] ?? null),
                        'quantity'        => trim($row[10] ?? null),
                        'weight'          => trim($row[14] ?? null),
                        'source_client'   => trim($row[15] ?? null),
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                Log::error("Gagal simpan updateOrCreate Event 1 pada baris timestamp {$row[0]}: " . $e->getMessage());
            }
        }

        fclose($handle);
        return ['inserted' => $insertedCount, 'updated' => $updatedCount];
    }

    /**
     * Parse Event 2 CSV -> event_record_2
     */
    protected function parseEvent2(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0];
        }

        $insertedCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            try {
                $record = EventRecord2::updateOrCreate(
                    [
                        'timestamp'      => trim($row[0] ?? null),
                        'transport_id'   => trim($row[2] ?? null),
                        'batch_number'   => trim($row[11] ?? null),
                        'tray_id'        => trim($row[12] ?? null),
                        'pallet_id'      => trim($row[13] ?? null),
                    ],
                    [
                        'position'        => trim($row[1] ?? null),
                        'source_location' => trim($row[3] ?? null),
                        'target_location' => trim($row[4] ?? null),
                        'transport_mode'  => trim($row[5] ?? null),
                        'category'        => trim($row[6] ?? null),
                        'denomination'    => trim($row[7] ?? null),
                        'emission'        => trim($row[8] ?? null),
                        'item_number'     => trim($row[9] ?? null),
                        'quantity'        => trim($row[10] ?? null),
                        'weight'          => trim($row[14] ?? null),
                        'source_client'   => trim($row[15] ?? null),
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                Log::error("Gagal simpan updateOrCreate Event 2 pada baris timestamp {$row[0]}: " . $e->getMessage());
            }
        }

        fclose($handle);
        return ['inserted' => $insertedCount, 'updated' => $updatedCount];
    }

    /**
     * Parse Event 3 CSV -> event_record_3
     */
    protected function parseEvent3(string $filePath): array
    {
        if (! file_exists($filePath)) {
            Log::warning("Event 3 File tidak ditemukan: {$filePath}");
            return ['inserted' => 0, 'updated' => 0];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            Log::warning("Gagal membuka file Event 3: {$filePath}");
            return ['inserted' => 0, 'updated' => 0];
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            Log::warning("Header Event 3 kosong atau gagal dibaca: {$filePath}");
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0];
        }

        $insertedCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            try {
                $record = EventRecord3::updateOrCreate(
                    [
                        'timestamp'     => trim($row[0] ?? null),
                        'batch_number'  => trim($row[3] ?? null),
                        'hu_id'         => trim($row[6] ?? null),
                    ],
                    [
                        'sender'          => trim($row[1] ?? null),
                        'receiver'        => trim($row[2] ?? null),
                        'master_hu_id'    => trim($row[4] ?? null),
                        'master_hu_type'  => trim($row[5] ?? null),
                        'hu_type'         => trim($row[7] ?? null),
                        'transport_mode'  => trim($row[8] ?? null),
                        'weight'          => trim($row[9] ?? null),
                        'item_number'     => trim($row[10] ?? null),
                        'quantity'        => isset($row[11]) ? trim($row[11]) : null,
                        'first_operator'  => trim($row[12] ?? null),
                        'second_operator' => trim($row[13] ?? null),
                        'third_operator'  => trim($row[14] ?? null),
                        'teamleader'      => trim($row[15] ?? null),
                        'shift_name'      => trim($row[16] ?? null),
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                Log::error("Gagal simpan updateOrCreate Event 3 pada baris timestamp {$row[0]}: " . $e->getMessage());
            }
        }

        fclose($handle);
        return ['inserted' => $insertedCount, 'updated' => $updatedCount];
    }

    /**
     * Parse Event 4 CSV -> event_record_4
     */
    protected function parseEvent4(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0];
        }

        $insertedCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            try {
                $record = EventRecord4::updateOrCreate(
                    [
                        'timestamp' => trim($row[0] ?? null),
                    ],
                    [
                        'shredder'       => trim($row[1] ?? null),
                        'idle_state'     => trim($row[2] ?? null),
                        'time'           => trim($row[3] ?? null),
                        'shredder_event' => trim($row[4] ?? null),
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                Log::error("Gagal simpan updateOrCreate Event 4 pada baris timestamp {$row[0]}: " . $e->getMessage());
            }
        }

        fclose($handle);
        return ['inserted' => $insertedCount, 'updated' => $updatedCount];
    }

    /**
     * Write sync log entry
     */
    protected function logSync(
        string $sourceType,
        int $filesProcessed,
        int $rowsInserted,
        string $status,
        string $message,
        string $triggeredBy
    ): void {
        try {
            BdsSyncLog::create([
                'source_type'     => $sourceType,
                'files_processed' => $filesProcessed,
                'rows_inserted'   => $rowsInserted,
                'status'          => $status,
                'message'         => $message,
                'triggered_by'    => $triggeredBy,
            ]);
        } catch (Exception $e) {
            Log::error('Gagal menulis log sinkronisasi BDS: '.$e->getMessage());
        }
    }
}
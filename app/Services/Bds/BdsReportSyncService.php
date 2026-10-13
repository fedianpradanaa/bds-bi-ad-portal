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
            // Mengarahkan langsung ke folder storage/mhs-reports/EventRecord
            $this->baseDir = storage_path('app'.DIRECTORY_SEPARATOR.'mhs-reports'.DIRECTORY_SEPARATOR.'EventRecord');
            // Catatan: Jika folder mhs-reports berada di luar folder 'app' di dalam storage, 
            // gunakan: storage_path('mhs-reports'.DIRECTORY_SEPARATOR.'EventRecord')
        }
    }

    /**
     * Synchronize raw CSV files into clean event_record tables (1:1 mapping)
     */
    public function sync(?string $targetDate = null, string $triggeredBy = 'SCHEDULER'): array
    {
        $startTime = microtime(true);
        $filesProcessed = 0;
        $totalRowsInserted = 0;

        if (! File::isDirectory($this->baseDir)) {
            $msg = "Direktori sumber laporan MHS tidak ditemukan: {$this->baseDir}";
            Log::warning($msg);
            $this->logSync('LOCAL_PATH', 0, 0, 'FAILED', $msg, $triggeredBy);

            return [
                'success' => false,
                'message' => $msg,
                'files_processed' => 0,
                'rows_inserted' => 0,
            ];
        }

        $dateDirectories = File::directories($this->baseDir);
        sort($dateDirectories);

        DB::beginTransaction();
        try {
            foreach ($dateDirectories as $dateDir) {
                $dirName = basename($dateDir);
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dirName)) {
                    continue;
                }

                if ($targetDate && $dirName !== $targetDate) {
                    continue;
                }

                $result = $this->processDateDirectory($dateDir);
                $filesProcessed += $result['files'];
                $totalRowsInserted += $result['rows'];
            }

            DB::commit();

            $durationMs = round((microtime(true) - $startTime) * 1000);
            $message = "Berhasil sinkronisasi {$filesProcessed} file CSV dan memasukkan {$totalRowsInserted} baris data mentah ({$durationMs} ms).";

            $this->logSync('LOCAL_PATH', $filesProcessed, $totalRowsInserted, 'SUCCESS', $message, $triggeredBy);

            return [
                'success' => true,
                'message' => $message,
                'files_processed' => $filesProcessed,
                'rows_inserted' => $totalRowsInserted,
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
            ];
        }
    }

    /**
     * Process all sub-folders (_Event1 to _Event4) inside a date directory
     */
    protected function processDateDirectory(string $dateDir): array
    {
        $filesProcessed = 0;
        $rowsInserted = 0;
        $subDirs = File::directories($dateDir);

        foreach ($subDirs as $subDir) {
            $subName = basename($subDir);
            $csvFiles = File::glob($subDir.DIRECTORY_SEPARATOR.'*.csv');

            foreach ($csvFiles as $file) {
                // Cek apakah file ini sudah pernah diproses sebelumnya
                $isProcessed = DB::table('bds_processed_files')->where('file_path', $file)->exists();
                if ($isProcessed) {
                    continue; // Lewati file yang sudah diproses agar cepat!
                }

                $fileRows = 0;
                if (preg_match('/_Event1$/i', $subName)) {
                    $fileRows = $this->parseEvent1($file);
                } elseif (preg_match('/_Event2$/i', $subName)) {
                    $fileRows = $this->parseEvent2($file);
                } elseif (preg_match('/_Event3$/i', $subName)) {
                    $fileRows = $this->parseEvent3($file);
                } elseif (preg_match('/_Event4$/i', $subName)) {
                    $fileRows = $this->parseEvent4($file);
                }

                if ($fileRows > 0) {
                    // Catat ke database bahwa file ini sudah sukses diproses
                    DB::table('bds_processed_files')->insert([
                        'file_path' => $file,
                        'rows_count' => $fileRows,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $rowsInserted += $fileRows;
                $filesProcessed++;
            }
        }

        return ['files' => $filesProcessed, 'rows' => $rowsInserted];
    }

    /**
     * Parse Event 1 CSV -> event_record_1
     */
    protected function parseEvent1(string $filePath): int
    {
        if (! file_exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            EventRecord1::updateOrCreate(
                [
                    'timestamp'      => trim($row[0] ?? null),
                    'transport_id'   => trim($row[2] ?? null),
                    'transport_mode' => trim($row[5] ?? null),
                    'batch_number'   => trim($row[11] ?? null),
                ],
                [
                    'position'        => trim($row[1] ?? null),
                    'source_location' => trim($row[3] ?? null),
                    'target_location' => trim($row[4] ?? null),
                    'category'        => trim($row[6] ?? null),
                    'denomination'    => trim($row[7] ?? null),
                    'emission'        => trim($row[8] ?? null),
                    'item_number'     => trim($row[9] ?? null),
                    'quantity'        => trim($row[10] ?? null),
                    'tray_id'         => trim($row[12] ?? null),
                    'pallet_id'       => trim($row[13] ?? null),
                    'weight'          => trim($row[14] ?? null),
                    'source_client'   => trim($row[15] ?? null),
                ]
            );
            $count++;
        }

        fclose($handle);
        return $count;
    }

    /**
     * Parse Event 2 CSV -> event_record_2
     */
    protected function parseEvent2(string $filePath): int
    {
        if (! file_exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            EventRecord2::updateOrCreate(
                [
                    'timestamp'      => trim($row[0] ?? null),
                    'transport_id'   => trim($row[2] ?? null),
                    'transport_mode' => trim($row[5] ?? null),
                    'batch_number'   => trim($row[11] ?? null),
                ],
                [
                    'position'        => trim($row[1] ?? null),
                    'source_location' => trim($row[3] ?? null),
                    'target_location' => trim($row[4] ?? null),
                    'category'        => trim($row[6] ?? null),
                    'denomination'    => trim($row[7] ?? null),
                    'emission'        => trim($row[8] ?? null),
                    'item_number'     => trim($row[9] ?? null),
                    'quantity'        => trim($row[10] ?? null),
                    'tray_id'         => trim($row[12] ?? null),
                    'pallet_id'       => trim($row[13] ?? null),
                    'weight'          => trim($row[14] ?? null),
                    'source_client'   => trim($row[15] ?? null),
                ]
            );
            $count++;
        }

        fclose($handle);
        return $count;
    }

    /**
     * Parse Event 3 CSV -> event_record_3
     */
    protected function parseEvent3(string $filePath): int
    {
        if (! file_exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            EventRecord3::updateOrCreate(
                [
                    'timestamp'      => trim($row[0] ?? null),
                    'batch_number'   => trim($row[3] ?? null),
                    'transport_mode' => trim($row[8] ?? null),
                ],
                [
                    'sender'         => trim($row[1] ?? null),
                    'receiver'       => trim($row[2] ?? null),
                    'master_hu_id'   => trim($row[4] ?? null),
                    'master_hu_type' => trim($row[5] ?? null),
                    'hu_id'          => trim($row[6] ?? null),
                    'hu_type'        => trim($row[7] ?? null),
                    'weight'         => trim($row[9] ?? null),
                    'item_number'    => trim($row[10] ?? null),
                    'quantity'       => isset($row[11]) ? trim($row[11]) : null,
                ]
            );
            $count++;
        }

        fclose($handle);
        return $count;
    }

    /**
     * Parse Event 4 CSV -> event_record_4
     */
    protected function parseEvent4(string $filePath): int
    {
        if (! file_exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle); // Skip header
        if (! $header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            EventRecord4::updateOrCreate(
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
            $count++;
        }

        fclose($handle);
        return $count;
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
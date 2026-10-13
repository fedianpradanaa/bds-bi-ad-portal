<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\BriquettingReport;
use Carbon\Carbon;

class ImportBriquettingReport extends Command
{
    protected $signature = 'report:import-br';
    protected $description = 'Import all BR JSON report fields from storage, insert to SQLite, and move to processed folder';

    public function handle()
    {
        $this->info('Mulai memeriksa file JSON...');

        // Path direktori penyimpanan di dalam storage/app
        $basePath = storage_path('app/mhs-reports/BR_Reports');
        $processedPath = $basePath . '/processed';

        // Pastikan folder processed otomatis terbuat jika belum ada
        if (!File::exists($processedPath)) {
            File::makeDirectory($processedPath, 0755, true);
        }

        // Ambil semua file di dalam folder BR_Reports
        $files = File::allFiles($basePath);
        $count = 0;

        foreach ($files as $file) {
            // Abaikan file yang berada di dalam subfolder processed
            if (str_contains($file->getPath(), 'processed')) {
                continue;
            }

            if ($file->getExtension() === 'json') {
                $filePath = $file->getRealPath();
                $fileName = $file->getFilename();

                // Cek duplikasi: jika nama file sudah tercatat di database, lewati
                if (BriquettingReport::where('source_file', $fileName)->exists()) {
                    $this->line("File sudah pernah diproses: {$fileName}");
                    continue;
                }

                $content = File::get($filePath);
                $data = json_decode($content, true);

                if (!$data || !isset($data['presses'])) {
                    $this->error("Format JSON tidak valid: {$fileName}");
                    continue;
                }

                // Ambil data header & konversi format tanggal (format input: d-m-Y H-i-s)
                $reportPrintedTime = $data['ReportPrintedDataTime'] ?? null;
                $tf = $data['requestedTimeFrame'] ?? [];

                $startDate = isset($tf['StartDate']) ? Carbon::createFromFormat('d-m-Y H-i-s', $tf['StartDate'])->format('Y-m-d H:i:s') : null;
                $endDate = isset($tf['EndDate']) ? Carbon::createFromFormat('d-m-Y H-i-s', $tf['EndDate'])->format('Y-m-d H:i:s') : null;
                $systemId = $tf['SystemId'] ?? null;
                $userId = $tf['UserId'] ?? null;
                $userIdOfflineShredding = $tf['UserIdOfflineShredding'] ?? null;

                $process = $tf['process'] ?? [];
                $startOfDayOrShift = isset($process['StartOfDayorshift']) ? Carbon::createFromFormat('d-m-Y H-i-s', $process['StartOfDayorshift'])->format('Y-m-d H:i:s') : null;
                $endOfDayOrShift = isset($process['EndOfDayorshift']) ? Carbon::createFromFormat('d-m-Y H-i-s', $process['EndOfDayorshift'])->format('Y-m-d H:i:s') : null;

                // Loop array presses (Press_1, Press_2, dst) agar semua data per press tersimpan
                foreach ($data['presses'] as $press) {
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
                        'duration' => $press['duration'] ?? '00:00:00',
                        'throughput_kg_per_h' => $press['throughputKgPerH'] ?? 0,
                        'briquettes_count' => $press['briquettesCount'] ?? 0,
                        'weight_per_briquette_g' => $press['weightPerBriquetteG'] ?? 0,
                        'briquette_weight_kg' => $press['briquetteWeightKg'] ?? 0,
                        'source_file' => $fileName,
                    ]);
                }

                // Pindahkan file fisik ke folder processed setelah sukses diimport
                $destination = $processedPath . '/' . $fileName;
                File::move($filePath, $destination);

                $this->info("Berhasil diimport & dipindah ke processed: {$fileName}");
                $count++;
            }
        }

        $this->info("Selesai! Total file baru diproses: {$count}");
    }
}

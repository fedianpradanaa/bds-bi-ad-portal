<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bds\BdsReportSyncService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BdsReportUploadController extends Controller
{
    /**
     * Verify API Key from headers or request
     */
    protected function authorizeRequest(Request $request): bool
    {
        $headerKey = $request->header('X-BDS-API-KEY')
            ?? $request->header('X-API-KEY')
            ?? $request->header('X-Api-Key')
            ?? $request->header('x-api-key');
        $bearerKey = $request->bearerToken();
        $inputKey = $request->input('api_key');
        $providedKey = $headerKey ?? $bearerKey ?? $inputKey;

        $expectedKey = config('services.bds.api_key', env('BDS_API_KEY', 'BdsMhsSecret2026!'));

        return ! empty($providedKey) && hash_equals($expectedKey, (string) $providedKey);
    }

    /**
     * Test connection & authentication
     */
    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->authorizeRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid BDS API Key.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'BDS Portal API is online and authenticated.',
            'timestamp' => now()->toIso8601String(),
            'server' => 'BDS-WEB-GD01',
        ]);
    }

    /**
     * Get inventory of currently synced files on server with MD5 checksums
     */
    public function syncedState(Request $request): JsonResponse
    {
        if (! $this->authorizeRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid BDS API Key.',
            ], 401);
        }

        $directoriesToScan = [
            base_path('mhs-reports'),
            base_path('Report Data Strukture'),
            base_path('BDS Output Report'),
        ];

        $filesInventory = [];

        foreach ($directoriesToScan as $scanDir) {
            if (! File::isDirectory($scanDir)) {
                continue;
            }

            $allFiles = File::allFiles($scanDir);
            foreach ($allFiles as $file) {
                $filename = $file->getFilename();
                if (str_starts_with($filename, '.') || str_starts_with($filename, '~') || str_ends_with(strtolower($filename), '.tmp')) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['csv', 'json'])) {
                    continue;
                }

                $fullPath = $file->getPathname();
                $normPath = str_replace('\\', '/', $fullPath);

                // Determine uniform key: EventRecord/... or BR_Reports/...
                $relKey = '';
                if (($pos = stripos($normPath, '/eventrecord/')) !== false) {
                    $relKey = substr($normPath, $pos + 1);
                } elseif (($pos = stripos($normPath, '/br_reports/')) !== false) {
                    $relKey = substr($normPath, $pos + 1);
                } elseif (preg_match('/BR_Report\s+(\d{2})\.(\d{2})\.(\d{4})\.json$/i', $filename, $m)) {
                    $relKey = "BR_Reports/{$m[3]}-{$m[2]}-{$m[1]}/{$filename}";
                } else {
                    $relKey = str_replace('\\', '/', $file->getRelativePathname());
                }

                if (! empty($relKey) && ! isset($filesInventory[$relKey])) {
                    $filesInventory[$relKey] = [
                        'size' => $file->getSize(),
                        'md5' => md5_file($fullPath) ?: '',
                        'modified' => $file->getMTime(),
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'total_files' => count($filesInventory),
            'files' => $filesInventory,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle reports upload (ZIP or individual CSV)
     */
    public function upload(Request $request, BdsReportSyncService $syncService): JsonResponse
    {
        if (! $this->authorizeRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid BDS API Key.',
            ], 401);
        }

        if (! $request->hasFile('file') && empty($request->getContent())) {
            return response()->json([
                'success' => false,
                'message' => 'No file provided in request. Expected multipart field "file" or raw payload.',
            ], 422);
        }

        $uploadedFile = $request->file('file');
        $targetDir = env('BDS_REPORTS_DIR', base_path('mhs-reports'.DIRECTORY_SEPARATOR.'EventRecord'));

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0777, true, true);
        }

        // Always ensure syncService uses the directory where files are written
        $syncService = new BdsReportSyncService($targetDir);

        // Check if JSON batch upload
        if ($request->isJson() && $request->has('files')) {
            return $this->handleJsonUpload($request, $targetDir, $syncService);
        }

        // Check if ZIP upload
        $isZip = false;
        if ($uploadedFile) {
            $ext = strtolower($uploadedFile->getClientOriginalExtension());
            $isZip = in_array($ext, ['zip', 'gz']);
        }

        if ($isZip && $uploadedFile) {
            return $this->handleZipUpload($uploadedFile->getPathname(), $targetDir, $syncService);
        }

        // Handle single CSV upload
        if ($uploadedFile && strtolower($uploadedFile->getClientOriginalExtension()) === 'csv') {
            $relativePath = $request->input('relative_path', $uploadedFile->getClientOriginalName());

            // Security check: Prevent path traversal
            if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid relative path: directory traversal is forbidden.',
                ], 400);
            }

            $destination = $targetDir.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
            $destinationDir = dirname($destination);

            if (! File::isDirectory($destinationDir)) {
                File::makeDirectory($destinationDir, 0777, true, true);
            }

            $uploadedFile->move($destinationDir, basename($destination));

            // Extract date if possible
            $targetDate = null;
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $relativePath, $m)) {
                $targetDate = $m[1];
            }

            $syncResult = null;
            if ($request->boolean('sync', true)) {
                $syncResult = $syncService->sync($targetDate, 'API_CSV_UPLOAD');
            }

            return response()->json([
                'success' => true,
                'message' => 'File CSV berhasil diunggah.',
                'file' => $relativePath,
                'sync_result' => $syncResult,
            ]);
        }

        // Handle single JSON upload (Output Report / BR_Reports)
        if ($uploadedFile && strtolower($uploadedFile->getClientOriginalExtension()) === 'json') {
            $relativePath = $request->input('relative_path', $uploadedFile->getClientOriginalName());

            // Security check: Prevent path traversal
            if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid relative path: directory traversal is forbidden.',
                ], 400);
            }

            $cleanRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
            if (! str_starts_with($cleanRelativePath, 'BR_Reports'.DIRECTORY_SEPARATOR) && ! str_starts_with($cleanRelativePath, 'BR_Reports')) {
                $cleanRelativePath = 'BR_Reports'.DIRECTORY_SEPARATOR.$cleanRelativePath;
            }

            $destination = base_path('mhs-reports'.DIRECTORY_SEPARATOR.$cleanRelativePath);
            $destinationDir = dirname($destination);

            if (! File::isDirectory($destinationDir)) {
                File::makeDirectory($destinationDir, 0777, true, true);
            }

            $uploadedFile->move($destinationDir, basename($destination));

            return response()->json([
                'success' => true,
                'message' => 'File JSON Output Report berhasil diunggah.',
                'file' => $cleanRelativePath,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unsupported file format. Please upload a .zip archive, .csv file, or .json report.',
        ], 422);
    }

    /**
     * Process ZIP archive and maintain MHS EventRecord folder structure
     */
    protected function handleZipUpload(string $zipPath, string $targetDir, BdsReportSyncService $syncService): JsonResponse
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuka file ZIP.',
            ], 422);
        }

        $extractedFiles = 0;
        $detectedDates = [];

        try {
            // First pass: verify safety & detect root structure
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $entryName = $stat['name'];

                if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                    $zip->close();

                    return response()->json([
                        'success' => false,
                        'message' => "Security check failed: entry '{$entryName}' contains invalid path traversal.",
                    ], 400);
                }

                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $entryName, $m)) {
                    $detectedDates[$m[1]] = true;
                }
            }

            // Extract each entry
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $entryName = $stat['name'];

                // Skip pure directory entries
                if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                    continue;
                }

                // Only allow CSV
                if (! str_ends_with(strtolower($entryName), '.csv')) {
                    continue;
                }

                $cleanRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $entryName);

                // If entry does not start with YYYY-MM-DD but starts with YYYY-MM-DD_EventX, prepend YYYY-MM-DD
                if (preg_match('/^(\d{4}-\d{2}-\d{2})_Event\d+/i', $cleanRelativePath, $matchDate)) {
                    $cleanRelativePath = $matchDate[1].DIRECTORY_SEPARATOR.$cleanRelativePath;
                }

                $destination = $targetDir.DIRECTORY_SEPARATOR.$cleanRelativePath;
                $dir = dirname($destination);

                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0777, true, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    File::put($destination, $content);
                    $extractedFiles++;
                }
            }

            $zip->close();

            // Run sync service for detected dates
            $dates = array_keys($detectedDates);
            $syncResults = [];

            if (! empty($dates)) {
                foreach ($dates as $d) {
                    $syncResults[$d] = $syncService->sync($d, 'API_ZIP_UPLOAD');
                }
            } else {
                $syncResults['all'] = $syncService->sync(null, 'API_ZIP_UPLOAD');
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengekstrak {$extractedFiles} file CSV dari arsip MHS.",
                'extracted_files' => $extractedFiles,
                'dates_processed' => $dates,
                'sync_results' => $syncResults,
            ]);

        } catch (Exception $e) {
            $zip->close();
            Log::error('MHS ZIP processing error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengekstrak ZIP: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process JSON batch upload of MHS report files
     */
    protected function handleJsonUpload(Request $request, string $targetDir, BdsReportSyncService $syncService): JsonResponse
    {
        $files = $request->input('files', []);
        if (! is_array($files) || empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'Format JSON tidak valid: field "files" kosong atau bukan array.',
            ], 422);
        }

        $savedCount = 0;
        $detectedDates = [];

        foreach ($files as $item) {
            $relPath = $item['relative_path'] ?? null;
            $content = $item['content'] ?? null;

            if (empty($relPath) || $content === null) {
                continue;
            }

            // Security check: Prevent path traversal
            if (str_contains($relPath, '..') || str_starts_with($relPath, '/') || str_starts_with($relPath, '\\')) {
                continue;
            }

            $cleanRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);

            // If entry starts with YYYY-MM-DD_EventX, prepend YYYY-MM-DD
            if (preg_match('/^(\d{4}-\d{2}-\d{2})_Event\d+/i', $cleanRelativePath, $matchDate)) {
                $cleanRelativePath = $matchDate[1].DIRECTORY_SEPARATOR.$cleanRelativePath;
            }

            $isJsonReport = str_ends_with(strtolower($cleanRelativePath), '.json') || str_starts_with($cleanRelativePath, 'BR_Reports');

            if ($isJsonReport) {
                if (! str_starts_with($cleanRelativePath, 'BR_Reports'.DIRECTORY_SEPARATOR) && ! str_starts_with($cleanRelativePath, 'BR_Reports')) {
                    $cleanRelativePath = 'BR_Reports'.DIRECTORY_SEPARATOR.$cleanRelativePath;
                }
                $destination = base_path('mhs-reports'.DIRECTORY_SEPARATOR.$cleanRelativePath);
            } else {
                $destination = $targetDir.DIRECTORY_SEPARATOR.$cleanRelativePath;
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $cleanRelativePath, $m)) {
                    $detectedDates[$m[1]] = true;
                }
            }

            $dir = dirname($destination);

            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0777, true, true);
            }

            File::put($destination, $content);
            $savedCount++;
        }

        $dates = array_keys($detectedDates);
        $syncResults = [];

        if (! empty($dates)) {
            foreach ($dates as $d) {
                $syncResults[$d] = $syncService->sync($d, 'API_JSON_UPLOAD');
            }
        } elseif ($savedCount > 0 && empty($detectedDates)) {
            // Only JSON reports uploaded, no CSV sync needed
            $syncResults = ['status' => 'JSON reports stored successfully'];
        } else {
            $syncResults['all'] = $syncService->sync(null, 'API_JSON_UPLOAD');
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima {$savedCount} file laporan MHS via API JSON.",
            'files_received' => $savedCount,
            'dates_processed' => $dates,
            'sync_results' => $syncResults,
        ]);
    }
}

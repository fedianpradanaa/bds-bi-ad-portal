<?php

namespace App\Http\Controllers;

use App\Services\Bds\BreakdownReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BreakdownReportExportController extends Controller
{
    public function __invoke(Request $request, BreakdownReportService $service): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $reports = $service->getReportsByDateRange($startDate, $endDate);

        $fileName = 'Breakdown_Report_' . ($startDate ?? 'all') . '_to_' . ($endDate ?? 'all') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($reports, $service) {
            $file = fopen('php://output', 'w');
            // Tambahkan BOM agar Excel membaca UTF-8 dengan benar
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom Excel / CSV
            fputcsv($file, [
                'Date', 
                'Unique ID', 
                'Event Start', 
                'Event Unit', 
                'Event Number', 
                'Event Stop', 
                'Duration', 
                'Eng Details', 
                'Eng Follow Up', 
                'Ind Details', 
                'Ind Follow Up',
                'Source File'
            ]);

            foreach ($reports as $report) {
                $engParsed = $service->parseDescription($report->eng_description ?? '', 'eng');
                $indParsed = $service->parseDescription($report->ind_description ?? '', 'ind');

                // Fungsi pembantu untuk mencegah Excel membaca teks berawalan '-' sebagai rumus
                $sanitizeText = function($text) {
                    $cleaned = trim((string) ($text ?? ''));
                    // Jika teks diawali dengan tanda minus (-), tambahkan spasi atau petik tunggal di depannya
                    if (str_starts_with($cleaned, '-')) {
                        return ' ' . $cleaned; // Menambahkan spasi aman di awal
                    }
                    return $cleaned;
                };

                fputcsv($file, [
                    (string) $report->date,
                    (string) $report->unique_id,
                    (string) $report->event_start,
                    (string) $report->event_unit,
                    (string) $report->event_number,
                    (string) $report->event_stop,
                    (string) $report->duration,
                    
                    // Terapkan sanitasi pada kolom deskripsi & follow up
                    $sanitizeText($engParsed['details'] ?? ''),
                    $sanitizeText($engParsed['follow_up'] ?? ''),
                    $sanitizeText($indParsed['details'] ?? ''),
                    $sanitizeText($indParsed['follow_up'] ?? ''),
                    
                    (string) $report->source_file,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
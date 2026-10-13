<?php

namespace App\Services\Bds;

use App\Models\BriquettingReport;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class BdsOutputReportService
{
    /**
     * Helper untuk menulis log khusus report ke file log terpisah (storage/logs/briquetting_report.log)
     */
    protected function logReport(string $message, array $context = []): void
    {
        Log::channel('single')->info("[BDS Report] " . $message, $context);
        
        $customLogger = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/briquetting_report.log'),
        ]);
        
        $customLogger->info("[BDS Report] " . $message, $context);
    }

    public function getAvailableDates(): array
    {
        return BriquettingReport::selectRaw('DATE(start_date) as report_date')
            ->distinct()
            ->orderBy('report_date', 'desc')
            ->pluck('report_date')
            ->toArray();
    }

    public function getReportData(
        ?string $date = null,
        string $dateFilterMode = 'single',
        ?string $startDate = null,
        ?string $endDate = null
    ): ?array {
        $availableDates = $this->getAvailableDates();

        if (empty($availableDates)) {
            $this->logReport("Peringatan: Tidak ada available dates yang ditemukan.");
            return null;
        }

        $refDate = $date ?? ($availableDates[0] ?? date('Y-m-d'));

        switch ($dateFilterMode) {
            case '1_week':
                $calcStart = Carbon::parse($refDate)->subDays(7)->format('Y-m-d');
                return $this->getAggregatedReportData($calcStart, $refDate, $availableDates);
            case '1_month':
                $calcStart = Carbon::parse($refDate)->subDays(30)->format('Y-m-d');
                return $this->getAggregatedReportData($calcStart, $refDate, $availableDates);
            case '1_year':
                $calcStart = Carbon::parse($refDate)->subDays(365)->format('Y-m-d');
                return $this->getAggregatedReportData($calcStart, $refDate, $availableDates);
            case 'all':
                $earliest = end($availableDates) ?: $refDate;
                $latest = $availableDates[0] ?? $refDate;
                return $this->getAggregatedReportData($earliest, $latest, $availableDates);
            case 'custom':
            case 'range':
                return $this->getAggregatedReportData($startDate, $endDate, $availableDates);
            case 'single':
            default:
                return $this->getSingleDateReportData($refDate);
        }
    }

    protected function getSingleDateReportData(string $date): ?array
    {
        $reports = BriquettingReport::whereDate('start_date', $date)->get();

        if ($reports->isEmpty()) {
            return null;
        }

        $first = $reports->first();
        $presses = [];

        foreach ($reports as $rep) {
            $presses[] = [
                'pressId' => $rep->press_id,
                'duration' => $rep->duration,
                'throughputKgPerH' => (float) $rep->throughput_kg_per_h,
                'briquettesCount' => (int) $rep->briquettes_count,
                'weightPerBriquetteG' => (int) $rep->weight_per_briquette_g,
                'briquetteWeightKg' => (float) $rep->briquette_weight_kg,
            ];
        }

        $json = [
            'ReportPrintedDataTime' => $first->report_printed_time,
            'requestedTimeFrame' => [
                'StartDate' => $first->start_date ? Carbon::parse($first->start_date)->format('d-m-Y H-i-s') : '',
                'EndDate' => $first->end_date ? Carbon::parse($first->end_date)->format('d-m-Y H-i-s') : '',
                'SystemId' => $first->system_id,
                'UserId' => $first->user_id,
                'UserIdOfflineShredding' => $first->user_id_offline_shredding,
                'process' => [
                    'StartOfDayorshift' => $first->start_of_day_or_shift ? Carbon::parse($first->start_of_day_or_shift)->format('d-m-Y H-i-s') : '',
                    'EndOfDayorshift' => $first->end_of_day_or_shift ? Carbon::parse($first->end_of_day_or_shift)->format('d-m-Y H-i-s') : '',
                ],
            ],
            'presses' => $presses,
        ];

        return $this->formatReportData($json, $date);
    }

    protected function getAggregatedReportData(?string $startDate, ?string $endDate, array $availableDates): ?array
    {
        if (! $startDate) {
            $startDate = end($availableDates) ?: date('Y-m-d');
        }
        if (! $endDate) {
            $endDate = $availableDates[0] ?? date('Y-m-d');
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $matchingDates = array_filter($availableDates, function ($d) use ($startDate, $endDate) {
            return $d >= $startDate && $d <= $endDate;
        });

        if (empty($matchingDates)) {
            return null;
        }

        $reports = [];
        foreach ($matchingDates as $d) {
            $single = $this->getSingleDateReportData($d);
            if ($single) {
                $reports[] = $single;
            }
        }

        if (empty($reports)) {
            return null;
        }

        $latest = $reports[0];
        $earliest = end($reports);

        $aggregated = $latest;
        $aggregated['timeframe_display'] = "{$startDate} - {$endDate}";
        $aggregated['press_started'] = $earliest['press_started'] ?? $latest['press_started'];
        $aggregated['press_ended'] = $latest['press_ended'] ?? $latest['press_ended'];

        $totalOpSeconds = 0;
        foreach ($reports as $r) {
            $totalOpSeconds += $this->durationToSeconds($r['press_operational_time'] ?? '00:00:00');
        }
        $aggregated['press_operational_time'] = $this->secondsToDuration($totalOpSeconds);

        foreach (['press_1', 'press_2'] as $pressKey) {
            $sumWeight = 0;
            $sumSeconds = 0;

            foreach ($reports as $r) {
                $sumWeight += $r[$pressKey]['weight_kg'] ?? 0;
                $sumSeconds += $this->durationToSeconds($r[$pressKey]['duration'] ?? '00:00:00');
            }

            $aggregated[$pressKey]['weight_kg'] = round($sumWeight, 2);
            $aggregated[$pressKey]['duration'] = $this->secondsToDuration($sumSeconds);
            $aggregated[$pressKey]['throughput_kg_h'] = $sumSeconds > 0
                ? round($sumWeight / ($sumSeconds / 3600), 2)
                : 0.00;
        }

        foreach (['press_1', 'press_2'] as $pressKey) {
            $sumTotalWeight = 0;
            $sumTotalCount = 0;

            foreach ($reports as $r) {
                $sumTotalWeight += $r['total'][$pressKey]['weight_kg'] ?? 0;
                $sumTotalCount += $r['total'][$pressKey]['count'] ?? 0;
            }

            $aggregated['total'][$pressKey]['weight_kg'] = round($sumTotalWeight, 2);
            $aggregated['total'][$pressKey]['count'] = $sumTotalCount;
        }

        return $aggregated;
    }

    protected function formatReportData(array $json, string $date): array
    {
        $timeframe = $json['requestedTimeFrame'] ?? [];
        $process = $timeframe['process'] ?? [];
        $presses = $json['presses'] ?? [];

        $startDateRaw = $timeframe['StartDate'] ?? null;
        $endDateRaw = $timeframe['EndDate'] ?? null;
        $startCarbon = $startDateRaw ? $this->parseReportDateTime($startDateRaw) : Carbon::parse($date);
        $endCarbon = $endDateRaw ? $this->parseReportDateTime($endDateRaw) : Carbon::parse($date);

        $timeframeDisplay = ($startCarbon && $endCarbon)
            ? $startCarbon->format('Y-m-d').' - '.$endCarbon->format('Y-m-d')
            : $date.' - '.$date;

        $systemId = $timeframe['SystemId'] ?? 'BDS';
        if (empty(trim($systemId))) {
            $systemId = 'BDS';
        }

        $startedRaw = $process['StartOfDayorshift'] ?? '';
        $endedRaw = $process['EndOfDayorshift'] ?? '';

        $startedCarbon = $this->parseReportDateTime($startedRaw);
        $endedCarbon = $this->parseReportDateTime($endedRaw);

        $pressStarted = $startedCarbon ? $startedCarbon->format('Y-m-d H:i:s') : (! empty($startedRaw) ? $startedRaw : '-');
        $pressEnded = $endedCarbon ? $endedCarbon->format('Y-m-d H:i:s') : (! empty($endedRaw) ? $endedRaw : '-');

        $opTime = '-';
        if ($startedCarbon && $endedCarbon) {
            $diffSeconds = abs($endedCarbon->diffInSeconds($startedCarbon));
            if ($diffSeconds > 0) {
                $opTime = $this->secondsToDuration($diffSeconds);
            }
        }

        $printedRaw = $json['ReportPrintedDataTime'] ?? '';
        $printedCarbon = $this->parseReportDateTime($printedRaw);
        $printed = $printedCarbon ? $printedCarbon->format('Y-m-d H:i:s') : (! empty($printedRaw) ? $printedRaw : date('Y-m-d H:i:s'));

        $press1Data = $this->findPressData($presses, 'Press_1', 'Press 1');
        $press2Data = $this->findPressData($presses, 'Press_2', 'Press 2');

        $p1 = $this->formatPressMetrics($press1Data);
        $p2 = $this->formatPressMetrics($press2Data);

        if ($opTime === '-' || $opTime === '00:00:00') {
            $combinedSecs = $this->durationToSeconds($p1['duration']) + $this->durationToSeconds($p2['duration']);
            if ($combinedSecs > 0) {
                $opTime = $this->secondsToDuration($combinedSecs);
            }
        }

        $cumulativeTotals = $this->calculateCumulativeTotalsUpToDate($date);

        return [
            'raw_date' => $date,
            'timeframe_display' => $timeframeDisplay,
            'system_id' => $systemId,
            'press_started' => $pressStarted,
            'press_ended' => $pressEnded,
            'press_operational_time' => $opTime,
            'printed' => $printed,
            'press_1' => $p1,
            'press_2' => $p2,
            'total' => [
                'press_1' => [
                    'weight_kg' => $cumulativeTotals['press_1']['weight_kg'] > 0
                        ? round($cumulativeTotals['press_1']['weight_kg'], 2)
                        : $p1['weight_kg'],
                    'count' => $cumulativeTotals['press_1']['count'] > 0
                        ? $cumulativeTotals['press_1']['count']
                        : $p1['briquettes_count'],
                    'weight_per_briquette_g' => $cumulativeTotals['press_1']['weight_per_briquette_g'] > 0
                        ? $cumulativeTotals['press_1']['weight_per_briquette_g']
                        : ($p1['weight_per_briquette_g'] > 0 ? $p1['weight_per_briquette_g'] : 1550),
                ],
                'press_2' => [
                    'weight_kg' => $cumulativeTotals['press_2']['weight_kg'] > 0
                        ? round($cumulativeTotals['press_2']['weight_kg'], 2)
                        : $p2['weight_kg'],
                    'count' => $cumulativeTotals['press_2']['count'] > 0
                        ? $cumulativeTotals['press_2']['count']
                        : $p2['briquettes_count'],
                    'weight_per_briquette_g' => $cumulativeTotals['press_2']['weight_per_briquette_g'] > 0
                        ? $cumulativeTotals['press_2']['weight_per_briquette_g']
                        : ($p2['weight_per_briquette_g'] > 0 ? $p2['weight_per_briquette_g'] : 1550),
                ],
            ],
        ];
    }

    protected function calculateCumulativeTotalsUpToDate(string $targetDate): array
    {
        $totals = [
            'press_1' => ['weight_kg' => 0.0, 'count' => 0, 'weight_per_briquette_g' => 1550],
            'press_2' => ['weight_kg' => 0.0, 'count' => 0, 'weight_per_briquette_g' => 1550],
        ];

        $reports = BriquettingReport::whereDate('start_date', '<=', $targetDate)
            ->orderBy('start_date', 'asc')
            ->get();

        $this->logReport("Memulai kalkulasi akumulasi total s.d. tanggal {$targetDate}. Jumlah baris data: " . $reports->count());

        $maxGrams = [
            'press_1' => 0,
            'press_2' => 0,
        ];

        $totalGram = [
            'press_1' => 0,
            'press_2' => 0,
        ];

        foreach ($reports as $rep) {
            $pressId = strtolower(str_replace(['_', ' '], '', $rep->press_id));
            $pressKey = null;

            if (str_contains($pressId, 'press1')) {
                $pressKey = 'press_1';
            } elseif (str_contains($pressId, 'press2')) {
                $pressKey = 'press_2';
            }

            if (! $pressKey) {
                continue;
            }

            $count = (int) $rep->briquettes_count;
            $gram = (int) ($rep->weight_per_briquette_g > 0 ? $rep->weight_per_briquette_g : 1550);

            $totals[$pressKey]['count'] += $count;
            $totalGram[$pressKey] += ($count * $gram);

            if ($gram > $maxGrams[$pressKey]) {
                $maxGrams[$pressKey] = $gram;
            }
        }

        $totals['press_1']['weight_kg'] = round($totalGram['press_1'] / 1000, 2);
        $totals['press_2']['weight_kg'] = round($totalGram['press_2'] / 1000, 2);

        $totals['press_1']['weight_per_briquette_g'] = $maxGrams['press_1'] > 0 ? $maxGrams['press_1'] : 1550;
        $totals['press_2']['weight_per_briquette_g'] = $maxGrams['press_2'] > 0 ? $maxGrams['press_2'] : 1550;

        $this->logReport("Hasil Akhir Akumulasi Total (s.d. {$targetDate}):", [
            'Press_1' => $totals['press_1'],
            'Press_2' => $totals['press_2']
        ]);

        return $totals;
    }

    protected function findPressData(array $presses, string ...$identifiers): array
    {
        foreach ($presses as $p) {
            $id = strtolower(str_replace(['_', ' '], '', $p['pressId'] ?? ''));
            foreach ($identifiers as $target) {
                if ($id === strtolower(str_replace(['_', ' '], '', $target))) {
                    return $p;
                }
            }
        }

        if ($identifiers[0] === 'Press_1' && isset($presses[0])) {
            return $presses[0];
        }
        if ($identifiers[0] === 'Press_2' && isset($presses[1])) {
            return $presses[1];
        }

        return [];
    }

    protected function formatPressMetrics(array $press): array
    {
        $durationRaw = $press['duration'] ?? '00:00:00';
        $formattedDuration = $this->formatDuration($durationRaw);

        $count = (int) ($press['briquettesCount'] ?? 0);
        $gramPerBriquette = (int) ($press['weightPerBriquetteG'] > 0 ? $press['weightPerBriquetteG'] : 1550);
        $throughput = (float) ($press['throughputKgPerH'] ?? 0);

        $weightKg = ($count * $gramPerBriquette) / 1000;

        return [
            'weight_kg' => round($weightKg, 2),
            'duration' => $formattedDuration,
            'throughput_kg_h' => round($throughput, 2),
            'briquettes_count' => $count,
            'weight_per_briquette_g' => $gramPerBriquette,
        ];
    }

    public function formatDuration(?string $duration): string
    {
        if (empty($duration)) {
            return '00:00:00';
        }

        $clean = trim($duration);

        if (str_contains($clean, '.')) {
            $parts = explode('.', $clean);
            if (count($parts) === 2 && str_contains($parts[0], ':')) {
                $clean = $parts[0];
            } elseif (count($parts) === 2 && ! str_contains($parts[0], ':') && str_contains($parts[1], ':')) {
                $days = (int) $parts[0];
                $timeParts = explode(':', $parts[1]);
                $hours = ($days * 24) + (int) ($timeParts[0] ?? 0);
                $clean = sprintf('%02d:%02d:%02d', $hours, (int) ($timeParts[1] ?? 0), (int) ($timeParts[2] ?? 0));
            }
        }

        $segments = explode(':', $clean);
        if (count($segments) === 3) {
            return sprintf('%02d:%02d:%02d', (int) $segments[0], (int) $segments[1], (int) $segments[2]);
        }
        if (count($segments) === 2) {
            return sprintf('00:%02d:%02d', (int) $segments[0], (int) $segments[1]);
        }

        return '00:00:00';
    }

    public function durationToSeconds(string $duration): int
    {
        $parts = explode(':', $this->formatDuration($duration));
        return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
    }

    public function secondsToDuration(int $seconds): string
    {
        $seconds = abs($seconds);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public function parseReportDateTime(?string $dateStr): ?Carbon
    {
        if (empty($dateStr) || trim($dateStr) === '') {
            return null;
        }

        $trimmed = trim($dateStr);

        $formats = [
            'd-m-Y H-i-s',
            'd-m-Y H:i:s',
            'Y-m-d H:i:s',
            'd.m.Y H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y',
            'Y-m-d',
        ];

        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $trimmed);
            } catch (Exception) {
                // Try next
            }
        }

        try {
            return Carbon::parse($trimmed);
        } catch (Exception) {
            return null;
        }
    }
}
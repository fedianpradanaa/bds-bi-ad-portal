<?php

namespace App\Services\Bds;

use App\Models\Bds\EventRecord1;
use App\Models\Bds\EventRecord2;
use App\Models\Bds\EventRecord3;
use App\Models\Bds\EventRecord4;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BdsDatabaseReportService
{
    protected function isManualMode(?string $mode): bool
    {
        return str_starts_with(strtoupper(trim($mode ?? '')), 'MANUAL');
    }

    protected function safeFloat($value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }
        $clean = str_replace(',', '', trim($value));
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function parseTs(?string $ts): ?Carbon
    {
        if (!$ts) {
            return null;
        }
        try {
            return Carbon::parse($ts);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function fmtDatetime(?Carbon $d): string
    {
        if (!$d) return '-';
        $hour12 = $d->format('g');
        $ampm = $d->format('A');
        return "{$d->month}/{$d->day}/{$d->year} {$hour12}:{$d->format('i:s')} {$ampm}";
    }

    protected function fmtDuration(float $seconds): string
    {
        $seconds = (int) round($seconds);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    protected function fmtNumber(float $n, int $decimals = 0): string
    {
        return number_format($n, $decimals, '.', ',');
    }

    /**
     * De-duplicate ER1/ER2 by BatchNumber + TrayID, keeping the latest Timestamp.
     */
    protected function dedupLatestByBatchTray(Collection $rows): Collection
    {
        $latest = [];
        foreach ($rows as $r) {
            $key = $r->batch_number.'|'.$r->tray_id;
            $ts = $this->parseTs($r->timestamp);

            if (!isset($latest[$key]) || ($ts && $ts->greaterThan($this->parseTs($latest[$key]->timestamp)))) {
                $latest[$key] = $r;
            }
        }
        return collect(array_values($latest));
    }

    /**
     * Shared Virtual View Tray equivalent for Reports 5, 6, 7, 8
     */
    protected function buildVwRoboticArmTray(Collection $er2, Collection $er3): Collection
    {
        $event2max = [];
        foreach ($er2 as $r) {
            $key = $r->pallet_id.'|'.$r->batch_number.'|'.$r->tray_id;
            $ts = $this->parseTs($r->timestamp);
            if ($ts && (!isset($event2max[$key]) || $ts->greaterThan($event2max[$key]))) {
                $event2max[$key] = $ts;
            }
        }

        // Group ER3 by BatchNumber + MasterHuId (PalletID)
        $groups = $er3->groupBy(function ($r) {
            return $r->batch_number.'|'.$r->master_hu_id;
        });

        $vwRows = collect();
        foreach ($groups as $gRows) {
            $timed = $gRows->map(function ($r) {
                return ['row' => $r, 'ts' => $this->parseTs($r->timestamp)];
            })->filter(fn($item) => $item['ts'] !== null)
              ->sortBy('ts')->values();

            $uniqueTimes = $timed->pluck('ts')->unique()->sort()->values();

            foreach ($timed as $idx => $item) {
                $r = $item['row'];
                $ts = $item['ts'];
                
                // Find previous distinct timestamp
                $prevTs = $idx > 0 ? $uniqueTimes[$idx - 1] : null;

                if ($prevTs !== null) {
                    $startTime = $prevTs;
                } else {
                    $e2key = $r->master_hu_id.'|'.$r->batch_number.'|'.$r->hu_id;
                    $startTime = $event2max[$e2key] ?? $ts;
                }

                $vwRows->push((object) [
                    'BatchNumber' => $r->batch_number,
                    'MasterHuId' => $r->master_hu_id,
                    'HuId' => $r->hu_id,
                    'ItemNumber' => $r->item_number,
                    'TransportMode' => $r->transport_mode,
                    'Weight' => $r->weight,
                    'Quantity' => $r->quantity ?? null,
                    'StartTime' => $startTime,
                    'EndTime' => $ts,
                ]);
            }
        }
        return $vwRows;
    }

    /**
     * REPORT 1: Transfer Unit Per Batch
     */
    public function computeReport1(string $date): array
    {
        $er1 = $this->dedupLatestByBatchTray(EventRecord1::whereDate('timestamp', $date)->get());
        $er2 = $this->dedupLatestByBatchTray(EventRecord2::whereDate('timestamp', $date)->get());
        $er3 = EventRecord3::whereDate('timestamp', $date)->get();

        $er1ByBatch = $er1->groupBy('batch_number');
        $er2ByBatch = $er2->groupBy('batch_number');
        $er3ByBatch = $er3->groupBy('batch_number');

        $batches = $er1ByBatch->keys()->sort(function ($a, $b) use ($er1ByBatch) {
            $minA = $er1ByBatch[$a]->min(fn($r) => $this->parseTs($r->timestamp)?->timestamp ?? 0);
            $minB = $er1ByBatch[$b]->min(fn($r) => $this->parseTs($r->timestamp)?->timestamp ?? 0);
            return $minA <=> $minB;
        });

        $rows = [];
        $i = 1;
        foreach ($batches as $batch) {
            $bEr1 = $er1ByBatch[$batch];
            $bEr2 = $er2ByBatch[$batch] ?? collect();
            $bEr3 = $er3ByBatch[$batch] ?? collect();

            $mulai = $this->parseTs($bEr1->min('timestamp'));
            $selesai = $bEr2->isNotEmpty() ? $this->parseTs($bEr2->max('timestamp')) : $mulai;
            $durasiS = ($mulai && $selesai) ? max(0, $selesai->diffInSeconds($mulai)) : 0.0;

            $er3BatchWeight = $bEr3->sum(fn($r) => $this->safeFloat($r->weight));
            $throughput = ($durasiS > 0) ? (($er3BatchWeight / 1000) * 3600 / $durasiS) : 0.0;

            $byItem = $bEr1->groupBy('item_number');
            $items = [];
            $batchTotalBerat = 0.0;

            foreach ($byItem as $itemNumber => $itemRows) {
                $first = $itemRows->first();
                $trayIds = $itemRows->pluck('tray_id')->toArray();

                $matchedWeight = $bEr3->filter(fn($r) => in_array($r->hu_id, $trayIds))
                                      ->sum(fn($r) => $this->safeFloat($r->weight));
                $berat = $matchedWeight / 1000;
                $batchTotalBerat += $berat;

                $items[] = [
                    'item' => $itemNumber,
                    'mode' => $first->transport_mode,
                    'operator' => $first->source_client,
                    'beratTotal' => $this->fmtNumber($berat, 2).' Kg',
                ];
            }

            $rows[] = [
                'no' => $i++,
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2).' Kantong/J',
                'noBatch' => $batch,
                'items' => $items,
                'batchTotal' => $this->fmtNumber($batchTotalBerat, 2).' Kg',
            ];
        }

        return ['date' => $date, 'rows' => $rows];
    }

    /**
     * REPORT 2: Transfer Unit Per Pallet
     */
    public function computeReport2(string $date): array
    {
        $er1 = $this->dedupLatestByBatchTray(EventRecord1::whereDate('timestamp', $date)->get());
        $er2 = $this->dedupLatestByBatchTray(EventRecord2::whereDate('timestamp', $date)->get());
        $er3 = EventRecord3::whereDate('timestamp', $date)->get();

        $er1ByKey = $er1->groupBy(fn($r) => $r->batch_number.'|'.$r->pallet_id);
        $er2ByKey = $er2->groupBy(fn($r) => $r->batch_number.'|'.$r->pallet_id);
        $er3ByBatch = $er3->groupBy('batch_number');

        $rows = [];
        $totalTray = 0.0;
        $totalDurS = 0.0;
        $throughputs = [];

        foreach ($er1ByKey as $key => $bEr1) {
            [$batch, $pallet] = explode('|', $key);
            $bEr2 = $er2ByKey[$key] ?? collect();

            $mulai = $this->parseTs($bEr1->min('timestamp'));
            $selesai = $bEr2->isNotEmpty() ? $this->parseTs($bEr2->max('timestamp')) : $mulai;
            $durasiS = ($mulai && $selesai) ? max(0, $selesai->diffInSeconds($mulai)) : 0.0;

            $first = $bEr1->first();
            $trayKantong = $bEr1->sum(fn($r) => $this->safeFloat($r->quantity));

            $matchedWeight = ($er3ByBatch[$batch] ?? collect())
                ->filter(fn($r) => $r->master_hu_id == $pallet)
                ->sum(fn($r) => $this->safeFloat($r->weight));

            $throughput = ($durasiS > 0) ? (($matchedWeight / 1000) * 3600 / $durasiS) : 0.0;

            $totalTray += $trayKantong;
            $totalDurS += $durasiS;
            $throughputs[] = $throughput;

            $rows[] = [
                'noBatch' => $batch,
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'transportId' => $first->transport_id,
                'palletId' => $pallet,
                'item' => $first->item_number,
                'mode' => $first->transport_mode,
                'trayKantong' => $this->fmtNumber($trayKantong),
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2).' Kantong/J',
            ];
        }

        $jumlah = [
            'trayKantong' => $this->fmtNumber($totalTray),
            'durasi' => $this->fmtDuration($totalDurS),
            'throughput' => $this->fmtNumber(count($throughputs) > 0 ? array_sum($throughputs) / count($throughputs) : 0, 2).' Kantong/J',
        ];

        return ['date' => $date, 'rows' => $rows, 'jumlah' => $jumlah];
    }
}
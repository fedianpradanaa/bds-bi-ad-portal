<?php

namespace App\Services\Bds;

use App\Models\Bds\EventRecord1;
use App\Models\Bds\EventRecord2;
use App\Models\Bds\EventRecord3;
use App\Models\Bds\EventRecord4;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BdsReportDataService
{
    protected function isManualMode(?string $mode): bool
    {
        return str_starts_with(strtoupper(trim($mode ?? '')), 'MANUAL');
    }

    protected function safeFloat($value): float
    {
        if ($value === null || trim((string)$value) === '') {
            return 0.0;
        }
        $clean = str_replace(',', '', trim((string)$value));
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

    protected function getField($r, string $snake, string $pascal)
    {
        if (!$r) return null;
        if (is_object($r)) {
            if (isset($r->{$snake})) return $r->{$snake};
            if (isset($r->{$pascal})) return $r->{$pascal};
            try {
                $val = $r->getAttribute($snake);
                if ($val !== null) return $val;
                $val = $r->getAttribute($pascal);
                if ($val !== null) return $val;
            } catch (\Throwable $e) {}
        }
        return null;
    }

    protected function dedupLatestByBatchTray(Collection $rows): Collection
    {
        $latestTs = [];
        $latestRow = [];

        foreach ($rows as $r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $tray = $this->getField($r, 'tray_id', 'TrayID');
            $key = $batch.'|'.$tray;
            $ts = $this->parseTs($this->getField($r, 'timestamp', 'Timestamp'));
            
            if (!$ts) {
                $latestRow[$key] = $r;
                continue;
            }

            if (!isset($latestTs[$key]) || $ts->greaterThan($latestTs[$key])) {
                $latestTs[$key] = $ts;
                $latestRow[$key] = $r;
            }
        }
        return collect(array_values($latestRow));
    }

    protected function queryRecordsForDate($modelClass, string $dateInput): Collection
    {
        try {
            $carbonDate = str_contains($dateInput, '.') 
                ? Carbon::createFromFormat('d.m.Y', $dateInput) 
                : (str_contains($dateInput, '-') && strlen(explode('-', $dateInput)[0]) === 4 
                    ? Carbon::createFromFormat('Y-m-d', $dateInput) 
                    : Carbon::parse($dateInput));
        } catch (\Exception $e) {
            $carbonDate = Carbon::parse($dateInput);
        }

        $y = $carbonDate->format('Y');
        $m = $carbonDate->format('m');
        $d = $carbonDate->format('d');

        return $modelClass::all()->filter(function($r) use ($y, $m, $d) {
            $ts = $this->parseTs($this->getField($r, 'timestamp', 'Timestamp'));
            if (!$ts) return false;
            return $ts->year == $y && $ts->month == $m && $ts->day == $d;
        });
    }

    protected function parseItemDenoFields(string $itemNumber): array
    {
        $denoCode = substr($itemNumber, 3, 2);
        $denoType = substr($itemNumber, 5, 1);
        $emissionCode = substr($itemNumber, 6, 2);
        return [$denoCode, $denoType, $emissionCode];
    }

    protected function buildVwRoboticArmTray(Collection $er2, Collection $er3): Collection
    {
        $event2max = [];
        foreach ($er2 as $r) {
            $palletId = $this->getField($r, 'pallet_id', 'PalletID');
            $batchNum = $this->getField($r, 'batch_number', 'BatchNumber');
            $trayId = $this->getField($r, 'tray_id', 'TrayID');
            $key = $palletId . '|' . $batchNum . '|' . $trayId;
            $ts = $this->parseTs($this->getField($r, 'timestamp', 'Timestamp'));
            
            if ($ts && (!isset($event2max[$key]) || $ts->greaterThan($event2max[$key]))) {
                $event2max[$key] = $ts;
            }
        }

        $groups = $er3->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber') . '|' . $this->getField($r, 'master_hu_id', 'MasterHuId'));
        $vwRows = collect();

        foreach ($groups as $gRows) {
            $timed = $gRows->map(function($r) {
                return [
                    'row' => $r,
                    'ts' => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp'))
                ];
            })->filter(fn($item) => $item['ts'] instanceof Carbon)->sortBy(fn($item) => $item['ts']->timestamp)->values();

            $uniqueTimes = $timed->pluck('ts')->map(fn($ts) => $ts->timestamp)->unique()->sort()->values();
            
            $prevOf = [];
            foreach ($uniqueTimes as $i => $t) {
                $prevOf[$t] = $i > 0 ? Carbon::createFromTimestamp($uniqueTimes[$i - 1]) : null;
            }

            foreach ($timed as $item) {
                $r = $item['row'];
                $ts = $item['ts'];
                $prevTs = $prevOf[$ts->timestamp] ?? null;

                if ($prevTs !== null) {
                    $startTime = $prevTs;
                } else {
                    $masterHuId = $this->getField($r, 'master_hu_id', 'MasterHuId');
                    $batchNum = $this->getField($r, 'batch_number', 'BatchNumber');
                    $huId = $this->getField($r, 'hu_id', 'HuId');
                    
                    $e2key = $masterHuId . '|' . $batchNum . '|' . $huId;
                    $startTime = $event2max[$e2key] ?? $ts;
                }

                $arr = is_object($r) ? clone $r : (object)$r;
                if (is_object($arr)) {
                    $arr->StartTime = $startTime;
                    $arr->EndTime = $ts;
                }
                $vwRows->push($arr);
            }
        }

        return $vwRows;
    }

    protected function buildEr2TransportLookup(Collection $er2): array
    {
        $lookup = [];
        foreach ($er2 as $r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $pallet = $this->getField($r, 'pallet_id', 'PalletID');
            $tray = $this->getField($r, 'tray_id', 'TrayID');
            $key = $batch . '|' . $pallet . '|' . $tray;
            if (!isset($lookup[$key])) {
                $lookup[$key] = $this->getField($r, 'transport_id', 'TransportID') ?? '';
            }
        }
        return $lookup;
    }

    protected function buildEr2TransportLookup2Key(Collection $er2): array
    {
        $lookup = [];
        foreach ($er2 as $r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $tray = $this->getField($r, 'tray_id', 'TrayID');
            $key = $batch . '|' . $tray;
            if (!isset($lookup[$key])) {
                $lookup[$key] = $this->getField($r, 'transport_id', 'TransportID') ?? '';
            }
        }
        return $lookup;
    }

    /**
     * Report 1: Transfer Unit Per Batch
     */
    public function computeReport1(string $date): array
    {
        $er1Raw = $this->queryRecordsForDate(EventRecord1::class, $date);
        $er2Raw = $this->queryRecordsForDate(EventRecord2::class, $date);
        $er3Raw = $this->queryRecordsForDate(EventRecord3::class, $date);

        $er1 = $er1Raw->filter(function($r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $ts = $this->getField($r, 'timestamp', 'Timestamp');
            $item = $this->getField($r, 'item_number', 'ItemNumber');
            $tray = $this->getField($r, 'tray_id', 'TrayID');
            return !empty(trim((string)$batch)) 
                && !empty(trim((string)$ts)) 
                && !empty(trim((string)$item)) 
                && !empty(trim((string)$tray));
        });
        $er1 = $this->dedupLatestByBatchTray($er1);

        $er2 = $er2Raw->filter(function($r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $ts = $this->getField($r, 'timestamp', 'Timestamp');
            $tray = $this->getField($r, 'tray_id', 'TrayID');
            return !empty(trim((string)$batch)) 
                && !empty(trim((string)$ts)) 
                && !empty(trim((string)$tray));
        });
        $er2 = $this->dedupLatestByBatchTray($er2);

        $er3 = $er3Raw->filter(function($r) {
            $batch = $this->getField($r, 'batch_number', 'BatchNumber');
            $hu = $this->getField($r, 'hu_id', 'HuId');
            return !empty(trim((string)$batch)) 
                && !empty(trim((string)$hu));
        });

        $er1ByBatch = $er1->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));
        $er2ByBatch = $er2->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));
        $er3ByBatch = $er3->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));

        $rows = [];
        $batchNumbers = $er1ByBatch->keys()->sort(function ($a, $b) use ($er1ByBatch) {
            $tsA = $er1ByBatch[$a]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            $tsB = $er1ByBatch[$b]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            return $tsA <=> $tsB;
        });

        $i = 1;
        foreach ($batchNumbers as $batch) {
            $bRowsEr1 = $er1ByBatch[$batch];
            $bRowsEr2 = $er2ByBatch[$batch] ?? collect();
            $bRowsEr3 = $er3ByBatch[$batch] ?? collect();

            $mulai = $bRowsEr1->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first();
            $selesai = $bRowsEr2->isNotEmpty() 
                ? $bRowsEr2->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->last() 
                : $mulai;
            
            $durasiS = 0.0;
            if ($mulai instanceof Carbon && $selesai instanceof Carbon) {
                $durasiS = (float) max(0.0, $selesai->getTimestamp() - $mulai->getTimestamp());
            }

            $er3BatchWeight = $bRowsEr3->sum(fn($r) => $this->safeFloat($this->getField($r, 'weight', 'Weight')));
            $throughput = ($durasiS > 0) ? (($er3BatchWeight / 1000) * 3600 / $durasiS) : 0.0;

            $byItem = $bRowsEr1->groupBy(fn($r) => $this->getField($r, 'item_number', 'ItemNumber'));
            $items = [];
            $batchTotalBerat = 0.0;

            foreach ($byItem as $itemNumber => $itemRows) {
                if (empty(trim((string)$itemNumber))) continue;
                $first = $itemRows->first();
                $mode = $this->getField($first, 'transport_mode', 'TransportMode') ?? '';
                $operator = $this->getField($first, 'source_client', 'SourceClient') ?? '';
                
                $trayIds = $itemRows->map(function($r) {
                    $t = $this->getField($r, 'tray_id', 'TrayID');
                    return strtoupper(trim((string)$t));
                })->unique()->filter()->toArray();

                $matchedWeight = $bRowsEr3->filter(function($r) use ($trayIds) {
                    $hu = $this->getField($r, 'hu_id', 'HuId');
                    $huId = strtoupper(trim((string)$hu));
                    return in_array($huId, $trayIds, true);
                })->sum(function($r) {
                    $w = $this->getField($r, 'weight', 'Weight');
                    return $this->safeFloat($w);
                });
                
                $berat = $matchedWeight / 1000.0;
                $batchTotalBerat += $berat;

                $items[] = [
                    'item' => $itemNumber,
                    'mode' => $mode,
                    'operator' => $operator,
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
     * Report 2: Transfer Unit Per Pallet
     */
    public function computeReport2(string $date): array
    {
        $er1Raw = $this->queryRecordsForDate(EventRecord1::class, $date);
        $er2Raw = $this->queryRecordsForDate(EventRecord2::class, $date);
        $er3Raw = $this->queryRecordsForDate(EventRecord3::class, $date);

        $er1 = $this->dedupLatestByBatchTray($er1Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'pallet_id', 'PalletID')))));
        $er2 = $this->dedupLatestByBatchTray($er2Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'pallet_id', 'PalletID')))));
        $er3 = $er3Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'master_hu_id', 'MasterHuId'))));

        $er1ByKey = $er1->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber') . '|' . $this->getField($r, 'pallet_id', 'PalletID'));
        $er2ByKey = $er2->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber') . '|' . $this->getField($r, 'pallet_id', 'PalletID'));
        $er3ByBatch = $er3->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));

        $rows = [];
        $totalTray = 0.0;
        $totalDurationS = 0.0;
        $throughputs = [];

        $keys = $er1ByKey->keys()->sort(function ($a, $b) use ($er1ByKey) {
            $tsA = $er1ByKey[$a]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            $tsB = $er1ByKey[$b]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            return $tsA <=> $tsB;
        });

        foreach ($keys as $key) {
            [$batch, $pallet] = explode('|', $key);
            $bEr1 = $er1ByKey[$key];
            $bEr2 = $er2ByKey[$key] ?? collect();

            $mulai = $bEr1->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first();
            $selesai = $bEr2->isNotEmpty() 
                ? $bEr2->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->last() 
                : $mulai;

            $durasiS = 0.0;
            if ($mulai instanceof Carbon && $selesai instanceof Carbon) {
                $durasiS = (float) max(0.0, $selesai->getTimestamp() - $mulai->getTimestamp());
            }

            $first = $bEr1->first();
            $transportId = $this->getField($first, 'transport_id', 'TransportID') ?? '';
            $item = $this->getField($first, 'item_number', 'ItemNumber') ?? '';
            $mode = $this->getField($first, 'transport_mode', 'TransportMode') ?? '';

            $trayKantong = $bEr1->sum(fn($r) => $this->safeFloat($this->getField($r, 'quantity', 'Quantity')));

            $matchedWeight = ($er3ByBatch[$batch] ?? collect())
                ->filter(fn($r) => $this->getField($r, 'master_hu_id', 'MasterHuId') == $pallet)
                ->sum(fn($r) => $this->safeFloat($this->getField($r, 'weight', 'Weight')));

            $throughput = ($durasiS > 0) ? (($matchedWeight / 1000) * 3600 / $durasiS) : 0.0;

            $totalTray += $trayKantong;
            $totalDurationS += $durasiS;
            $throughputs[] = $throughput;

            $rows[] = [
                'noBatch' => $batch,
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'transportId' => $transportId,
                'palletId' => $pallet,
                'item' => $item,
                'mode' => $mode,
                'trayKantong' => $this->fmtNumber($trayKantong),
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2) . ' Kantong/J',
            ];
        }

        $jumlah = [
            'trayKantong' => $this->fmtNumber($totalTray),
            'durasi' => $this->fmtDuration($totalDurationS),
            'throughput' => $this->fmtNumber(count($throughputs) > 0 ? array_sum($throughputs) / count($throughputs) : 0, 2) . ' Kantong/J',
        ];

        return ['date' => $date, 'rows' => $rows, 'jumlah' => $jumlah];
    }

    /**
     * Report 3: Robotic Arm Per Batch
     */
    public function computeReport3(string $date): array
    {
        $er2Raw = $this->queryRecordsForDate(EventRecord2::class, $date);
        $er3Raw = $this->queryRecordsForDate(EventRecord3::class, $date);

        $er2 = $this->dedupLatestByBatchTray($er2Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'batch_number', 'BatchNumber')))));
        $er3 = $er3Raw->filter(function($r) {
            $b = trim((string)$this->getField($r, 'batch_number', 'BatchNumber'));
            $mode = $this->getField($r, 'transport_mode', 'TransportMode') ?? '';
            return $b !== '' && !$this->isManualMode($mode);
        });

        $er2ByBatch = $er2->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));
        $er3ByBatch = $er3->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));

        $rows = [];
        $batchNumbers = $er3ByBatch->keys()->filter(fn($k) => !empty(trim((string)$k)))->sort(function ($a, $b) use ($er3ByBatch) {
            $tsA = $er3ByBatch[$a]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            $tsB = $er3ByBatch[$b]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            return $tsA <=> $tsB;
        });

        $i = 1;
        foreach ($batchNumbers as $batch) {
            $bEr2 = $er2ByBatch[$batch] ?? collect();
            $bEr3 = $er3ByBatch[$batch];

            $mulai = $bEr2->isNotEmpty() 
                ? $bEr2->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first() 
                : $bEr3->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first();

            $selesai = $bEr3->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->last();

            $durasiS = 0.0;
            if ($mulai instanceof Carbon && $selesai instanceof Carbon) {
                $durasiS = (float) max(0.0, $selesai->getTimestamp() - $mulai->getTimestamp());
            }

            $batchWeight = $bEr3->sum(fn($r) => $this->safeFloat($this->getField($r, 'weight', 'Weight')));
            $throughput = ($durasiS > 0) ? (($batchWeight / 1000) * 3600 / $durasiS) : 0.0;

            $byItem = $bEr3->groupBy(fn($r) => $this->getField($r, 'item_number', 'ItemNumber'));
            $items = [];
            $batchTotalBerat = 0.0;

            foreach ($byItem as $itemNumber => $itemRows) {
                if (empty(trim((string)$itemNumber))) continue;
                $first = $itemRows->first();
                $mode = $this->getField($first, 'transport_mode', 'TransportMode') ?? '';
                $operator = $this->getField($first, 'sender', 'Sender') ?? '';

                $berat = $itemRows->sum(fn($r) => $this->safeFloat($this->getField($r, 'weight', 'Weight'))) / 1000.0;
                $batchTotalBerat += $berat;

                $items[] = [
                    'item' => $itemNumber,
                    'mode' => $mode,
                    'operator' => $operator,
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
     * Report 4: Robotic Arm Per Pallet
     */
    public function computeReport4(string $date): array
    {
        $er2Raw = $this->queryRecordsForDate(EventRecord2::class, $date);
        $er3Raw = $this->queryRecordsForDate(EventRecord3::class, $date);

        $er2 = $this->dedupLatestByBatchTray($er2Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'pallet_id', 'PalletID')))));
        $er3 = $er3Raw->filter(function($r) {
            $b = trim((string)$this->getField($r, 'batch_number', 'BatchNumber'));
            $mode = $this->getField($r, 'transport_mode', 'TransportMode') ?? '';
            $masterHu = $this->getField($r, 'master_hu_id', 'MasterHuId');
            return $b !== '' && !empty(trim((string)$masterHu)) && !$this->isManualMode($mode);
        });

        $er2ByKey = $er2->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber') . '|' . $this->getField($r, 'pallet_id', 'PalletID'));
        $er3ByKey = $er3->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber') . '|' . $this->getField($r, 'master_hu_id', 'MasterHuId'));

        $rows = [];
        $totalTray = 0.0;
        $totalDurationS = 0.0;
        $throughputs = [];

        $keys = $er3ByKey->keys()->sort(function ($a, $b) use ($er3ByKey) {
            $tsA = $er3ByKey[$a]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            $tsB = $er3ByKey[$b]->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first()?->timestamp ?? 0;
            return $tsA <=> $tsB;
        });

        foreach ($keys as $key) {
            [$batch, $pallet] = explode('|', $key);
            $bEr3 = $er3ByKey[$key];
            $bEr2 = $er2ByKey[$key] ?? collect();

            $mulai = $bEr2->isNotEmpty() 
                ? $bEr2->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first() 
                : $bEr3->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->first();

            $selesai = $bEr3->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort()->last();

            $durasiS = 0.0;
            if ($mulai instanceof Carbon && $selesai instanceof Carbon) {
                $durasiS = (float) max(0.0, $selesai->getTimestamp() - $mulai->getTimestamp());
            }

            $firstEr3 = $bEr3->first();
            $item = $this->getField($firstEr3, 'item_number', 'ItemNumber') ?? '';
            $mode = $this->getField($firstEr3, 'transport_mode', 'TransportMode') ?? '';

            $matchingEr2 = $bEr2->filter(fn($r) => $this->getField($r, 'item_number', 'ItemNumber') == $item);
            $transportId = $matchingEr2->isNotEmpty() ? ($this->getField($matchingEr2->first(), 'transport_id', 'TransportID') ?? '') : ($this->getField($bEr2->first(), 'transport_id', 'TransportID') ?? '');

            $trayKantong = $bEr2->sum(fn($r) => $this->safeFloat($this->getField($r, 'quantity', 'Quantity')));
            $weightSum = $bEr3->sum(fn($r) => $this->safeFloat($this->getField($r, 'weight', 'Weight')));
            $throughput = ($durasiS > 0) ? (($weightSum / 1000) * 3600 / $durasiS) : 0.0;

            $totalTray += $trayKantong;
            $totalDurationS += $durasiS;
            $throughputs[] = $throughput;

            $rows[] = [
                'noBatch' => $batch,
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'transportId' => $transportId,
                'palletId' => $pallet,
                'item' => $item,
                'mode' => $mode,
                'trayKantong' => $this->fmtNumber($trayKantong),
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2) . ' Kantong/J',
            ];
        }

        $jumlah = [
            'trayKantong' => $this->fmtNumber($totalTray),
            'durasi' => $this->fmtDuration($totalDurationS),
            'throughput' => $this->fmtNumber(count($throughputs) > 0 ? array_sum($throughputs) / count($throughputs) : 0, 2) . ' Kantong/J',
        ];

        return ['date' => $date, 'rows' => $rows, 'jumlah' => $jumlah];
    }

    /**
     * Report 5: Robotic Arm Per Tray
     */
    public function computeReport5(Collection $vwRows, array $er2TransportLookup): array
    {
        $filtered = $vwRows->filter(fn($r) => !$this->isManualMode($this->getField($r, 'transport_mode', 'TransportMode')))->sortBy(fn($r) => $r->StartTime->timestamp);

        $rows = [];
        $totalTray = 0.0;
        $totalDurationS = 0.0;
        $throughputs = [];

        foreach ($filtered as $r) {
            $start = $r->StartTime;
            $end = $r->EndTime;
            $durasiS = (float) max(0.0, $end->getTimestamp() - $start->getTimestamp());
            
            $trayKantong = $this->safeFloat($this->getField($r, 'weight', 'Weight')) / 1000.0;
            $throughput = ($durasiS > 0) ? (($trayKantong * 3600) / $durasiS) : 0.0;

            $batchNum = $this->getField($r, 'batch_number', 'BatchNumber');
            $palletId = $this->getField($r, 'master_hu_id', 'MasterHuId');
            $huId = $this->getField($r, 'hu_id', 'HuId');
            $transportId = $er2TransportLookup[$batchNum . '|' . $palletId . '|' . $huId] ?? '';

            $totalTray += $trayKantong;
            $totalDurationS += $durasiS;
            $throughputs[] = $throughput;

            $rows[] = [
                'noBatch' => $batchNum,
                'palletId' => $palletId,
                'mulai' => $this->fmtDatetime($start),
                'selesai' => $this->fmtDatetime($end),
                'transportId' => $transportId,
                'trayId' => $huId,
                'item' => $this->getField($r, 'item_number', 'ItemNumber') ?? '',
                'mode' => $this->getField($r, 'transport_mode', 'TransportMode') ?? '',
                'trayKantong' => $this->fmtNumber($trayKantong, 2) . ' Kg',
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2) . ' Kantong/J',
            ];
        }

        $jumlah = [
            'trayKantong' => $this->fmtNumber($totalTray, 2) . ' Kg',
            'durasi' => $this->fmtDuration($totalDurationS),
            'throughput' => $this->fmtNumber(count($throughputs) > 0 ? array_sum($throughputs) / count($throughputs) : 0, 2) . ' Kantong/J',
        ];

        return ['rows' => $rows, 'jumlah' => $jumlah];
    }

    /**
     * Report 6: Feeder Unit Per Tray
     */
    public function computeReport6(Collection $vwRows, array $er2TransportLookup2Key, array $denoLookup): array
    {
        $ordered = $vwRows->sortBy(fn($r) => $r->EndTime->timestamp);
        $rows = [];
        $totalTray = 0.0;
        $totalDurationS = 0.0;
        $totalSejumlah = 0.0;
        $throughputs = [];
        $minSelesaiByBatch = [];

        foreach ($ordered as $r) {
            $mulai = $r->EndTime;
            $selesai = (clone $mulai)->addSeconds(2);
            $durasiS = 2.0;
            
            $trayKantong = $this->safeFloat($this->getField($r, 'weight', 'Weight')) / 1000.0;
            $throughput = $trayKantong * 3600 / $durasiS;

            $batchNum = $this->getField($r, 'batch_number', 'BatchNumber');
            $huId = $this->getField($r, 'hu_id', 'HuId');
            $transportId = $er2TransportLookup2Key[$batchNum . '|' . $huId] ?? '';

            $itemNumber = $this->getField($r, 'item_number', 'ItemNumber') ?? '';
            [$denoCode, $denoType, $_emis] = $this->parseItemDenoFields($itemNumber);
            $denoRow = $denoLookup[$denoCode . '|' . $denoType] ?? null;

            $sejumlahStr = '';
            if ($denoRow && isset($denoRow['Quantity'])) {
                $multiplier = (float) $denoRow['Quantity'];
                $qty = $this->safeFloat($this->getField($r, 'quantity', 'Quantity'));
                $sejumlahVal = $qty * $multiplier;
                $sejumlahStr = $this->fmtNumber($sejumlahVal) . ' bilyet';
                $totalSejumlah += $sejumlahVal;
            }

            if (!isset($minSelesaiByBatch[$batchNum]) || $selesai->lessThan($minSelesaiByBatch[$batchNum])) {
                $minSelesaiByBatch[$batchNum] = $selesai;
            }

            $totalTray += $trayKantong;
            $totalDurationS += $durasiS;
            $throughputs[] = $throughput;

            $rows[] = [
                'noBatch' => $batchNum,
                'palletId' => $this->getField($r, 'master_hu_id', 'MasterHuId'),
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'transportId' => $transportId,
                'trayId' => $huId,
                'item' => $itemNumber,
                'mode' => $this->getField($r, 'transport_mode', 'TransportMode') ?? '',
                'trayKantong' => $this->fmtNumber($trayKantong, 2) . ' Kg',
                'sejumlah' => $sejumlahStr,
                'durasi' => $this->fmtDuration($durasiS),
                'throughput' => $this->fmtNumber($throughput, 2) . ' Kantong/J',
            ];
        }

        $jumlah = [
            'trayKantong' => $this->fmtNumber($totalTray, 2) . ' Kg',
            'sejumlah' => $this->fmtNumber($totalSejumlah) . ' bilyet',
            'durasi' => $this->fmtDuration($totalDurationS),
            'throughput' => $this->fmtNumber(count($throughputs) > 0 ? array_sum($throughputs) / count($throughputs) : 0, 2) . ' Kantong/J',
        ];

        return ['rows' => $rows, 'jumlah' => $jumlah, 'min_selesai_by_batch' => $minSelesaiByBatch];
    }

    /**
     * Report 7: Shredder Unit Per Batch
     */
    protected function computeIdleSecondsSum(Carbon $mulai, Carbon $selesai, array $er4Events): float
    {
        $inRange = collect($er4Events)->filter(fn($e) => $e['ts']->between($mulai, $selesai))->sortBy(fn($e) => $e['ts']->timestamp)->values();
        $activeIdle = 0.0;
        $inactiveGap = 0.0;

        for ($i = 0; $i < $inRange->count() - 1; $i++) {
            $prev = $inRange[$i];
            $cur = $inRange[$i + 1];

            if ($prev['state'] === 'IDLE_START' && $cur['state'] === 'IDLE_STOP') {
                $activeIdle += (float) abs($cur['ts']->getTimestamp() - $prev['ts']->getTimestamp());
            } elseif ($prev['state'] === 'IDLE_STOP' && $cur['state'] === 'IDLE_START') {
                if ($prev['ts']->hour !== $cur['ts']->hour) {
                    $inactiveGap += (float) abs($cur['ts']->getTimestamp() - $prev['ts']->getTimestamp());
                }
            }
        }
        return $activeIdle + $inactiveGap;
    }

    public function computeReport7(Collection $vwRows, array $er4Events, array $minSelesaiByBatch): array
    {
        $vwByBatch = $vwRows->groupBy(fn($r) => $this->getField($r, 'batch_number', 'BatchNumber'));
        $batches = collect(array_keys($minSelesaiByBatch))->sort(fn($a, $b) => $minSelesaiByBatch[$a]->timestamp <=> $minSelesaiByBatch[$b]->timestamp);

        $rows = [];
        $rawByBatch = [];

        $er4Sorted = collect($er4Events)->sortBy(fn($e) => $e['ts']->timestamp)->values();
        $machineIntervals = [];
        $i = 0;
        while ($i < $er4Sorted->count() - 1) {
            $startEv = $er4Sorted[$i];
            if (strtoupper(trim($startEv['shredder'] ?? '')) === 'ONLINE') {
                for ($j = $i + 1; $j < $er4Sorted->count(); $j++) {
                    $stopEv = $er4Sorted[$j];
                    if (strtoupper(trim($stopEv['shredder'] ?? '')) === 'OFFLINE') {
                        $machineIntervals[] = [$startEv['ts'], $stopEv['ts']];
                        $i = $j;
                        break;
                    }
                }
            }
            $i++;
        }

        $idxB = 1;
        foreach ($batches as $batch) {
            $bRows = $vwByBatch[$batch] ?? collect();
            $r6MinSelesai = $minSelesaiByBatch[$batch];

            $er3Timestamps = $bRows->map(fn($r) => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')))->filter()->sort();
            $batchMinTs = $er3Timestamps->first() ?? $r6MinSelesai;
            $batchMaxTs = $er3Timestamps->last() ?? $r6MinSelesai;

            $matchedInterval = null;
            foreach ($machineIntervals as [$startDt, $stopDt]) {
                if ($startDt->lessThanOrEqualTo($batchMaxTs) && $stopDt->greaterThanOrEqualTo($batchMinTs)) {
                    $matchedInterval = [$startDt, $stopDt];
                    break;
                }
            }

            if ($matchedInterval) {
                [$mulai, $selesai] = $matchedInterval;
            } else {
                $futureStarts = collect($machineIntervals)->map(fn($pair) => $pair[0])->filter(fn($dt) => $dt->greaterThan($r6MinSelesai));
                if ($futureStarts->isNotEmpty()) {
                    $mulai = $futureStarts->sort()->first();
                    $matchingStops = collect($machineIntervals)->filter(fn($pair) => $pair[0]->equalTo($mulai))->map(fn($pair) => $pair[1]);
                    $selesai = $matchingStops->isNotEmpty() ? $matchingStops->first() : $batchMaxTs;
                } else {
                    $mulai = $r6MinSelesai;
                    $selesai = $batchMaxTs;
                }
            }

            $rawSpanS = (float) max(0.0, $selesai->getTimestamp() - $mulai->getTimestamp());
            $idleS = $this->computeIdleSecondsSum($mulai, $selesai, $er4Events);
            $prosesS = (float) max(0.0, $rawSpanS - $idleS);

            $batchWeight = $bRows->sum(fn($r) => abs($this->safeFloat($this->getField($r, 'weight', 'Weight'))));
            $throughput = ($prosesS > 0) ? (($batchWeight / 1000) * 3600 / $prosesS) : 0.0;

            $first = $bRows->first();
            $mode = $this->getField($first, 'transport_mode', 'TransportMode') ?? '';
            $operator = $this->getField($first, 'sender', 'Sender') ?? '';

            $byItem = $bRows->groupBy(fn($r) => $this->getField($r, 'item_number', 'ItemNumber'));
            $items = [];
            $batchTotalBerat = 0.0;
            $itemRawWeights = [];

            foreach ($byItem as $itemNumber => $itemRows) {
                if (empty(trim((string)$itemNumber))) continue;
                $rawW = $itemRows->sum(fn($r) => abs($this->safeFloat($this->getField($r, 'weight', 'Weight'))));
                $itemRawWeights[$itemNumber] = $rawW;
                $berat = $rawW / 1000.0;
                $batchTotalBerat += $berat;

                $items[] = [
                    'item' => $itemNumber,
                    'mode' => $mode,
                    'operator' => $operator,
                    'beratTotal' => $this->fmtNumber($berat, 2) . ' Kg',
                ];
            }

            $rows[] = [
                'no' => $idxB++,
                'mulai' => $this->fmtDatetime($mulai),
                'selesai' => $this->fmtDatetime($selesai),
                'idle' => $this->fmtDuration($idleS),
                'durasi' => $this->fmtDuration($prosesS),
                'throughput' => $this->fmtNumber($throughput, 2) . ' Kg/J',
                'noBatch' => $batch,
                'items' => $items,
                'batchTotal' => $this->fmtNumber($batchTotalBerat, 2) . ' Kg',
            ];

            $rawKey = $batch;
            if (isset($rawByBatch[$rawKey])) {
                $rawKey = $batch . '||' . strtoupper(trim($mode));
            }

            $rawByBatch[$rawKey] = [
                'mulai_dt' => $mulai,
                'selesai_dt' => $selesai,
                'idle_s' => $idleS,
                'proses_s' => $prosesS,
                'throughput' => $throughput,
                'mode' => $mode,
                'operator' => $operator,
                'item_raw_weights' => $itemRawWeights,
                'batch_total_berat' => $batchTotalBerat,
            ];
        }

        return ['rows' => $rows, 'raw_by_batch' => $rawByBatch];
    }

    /**
     * Report 8: Shredder Unit Certificate
     */
    public function computeReport8Certificates(string $dateDisp, Collection $vwRows, array $report7RawByBatch, array $denoLookup, array $emisLookup): array
    {
        $certs = [];

        foreach ($report7RawByBatch as $rawKey => $raw) {
            $cleanBatch = str_contains($rawKey, '||') ? explode('||', $rawKey)[0] : $rawKey;
            $mode = $raw['mode'];
            $normMode = strtoupper(trim($mode));
            $normBatch = strtoupper(trim($cleanBatch));

            $bRows = $vwRows->filter(function($r) use ($normBatch, $normMode) {
                $bNum = strtoupper(trim((string)$this->getField($r, 'batch_number', 'BatchNumber')));
                $tMode = strtoupper(trim((string)$this->getField($r, 'transport_mode', 'TransportMode')));
                return $bNum === $normBatch && $tMode === $normMode;
            });

            if ($bRows->isEmpty()) {
                $bRows = $vwRows->filter(fn($r) => strtoupper(trim((string)$this->getField($r, 'batch_number', 'BatchNumber'))) === $normBatch);
            }

            $page1 = ['defaultOperatorSender' => $raw['operator'] ?? ''];

            $mulaiDt = $raw['mulai_dt'];
            $selesaiDt = $raw['selesai_dt'];

            $page2 = [
                'berat' => $this->fmtNumber($raw['batch_total_berat'], 2) . ' Kg',
                'throughput' => $this->fmtNumber($raw['throughput'], 2) . ' Kg/J',
                'mulaiDate' => $mulaiDt instanceof Carbon ? $mulaiDt->format('d.m.Y') : '',
                'mulaiTime' => $mulaiDt instanceof Carbon ? $mulaiDt->format('H:i:s') : '',
                'selesaiDate' => $selesaiDt instanceof Carbon ? $selesaiDt->format('d.m.Y') : '',
                'selesaiTime' => $selesaiDt instanceof Carbon ? $selesaiDt->format('H:i:s') : '',
                'idle' => $this->fmtDuration($raw['idle_s']),
                'proses' => $this->fmtDuration($raw['proses_s']),
            ];

            $byItem = $bRows->groupBy(fn($r) => $this->getField($r, 'item_number', 'ItemNumber'));
            $page3Rows = [];
            $sejumlahTotal = 0.0;
            $beratTotal = 0.0;
            $no = 1;

            foreach ($byItem as $itemNumber => $itemRows) {
                [$denoCode, $denoType, $emissionCode] = $this->parseItemDenoFields($itemNumber);
                $denoRow = $denoLookup[$denoCode . '|' . $denoType] ?? null;
                $emisRow = $emisLookup[$emissionCode] ?? null;

                $rawW = $raw['item_raw_weights'][$itemNumber] ?? $itemRows->sum(fn($r) => abs($this->safeFloat($this->getField($r, 'weight', 'Weight'))));
                $beratItem = $rawW / 1000.0;
                $beratTotal += $beratItem;

                $qtySum = $itemRows->sum(fn($r) => $this->safeFloat($this->getField($r, 'quantity', 'Quantity')));
                $multiplier = isset($denoRow['Quantity']) ? (float) $denoRow['Quantity'] : 1.0;

                $sejumlahVal = $qtySum * $multiplier;
                $sejumlahTotal += $sejumlahVal;

                $uangKertas = '';
                if ($denoRow && isset($denoRow['DenoValue']) && $emisRow && isset($emisRow['EmissionYear'])) {
                    $denoValue = round((float) $denoRow['DenoValue']);
                    $uangKertas = number_format($denoValue, 0, '.', ',') . " Rupiah ({$emisRow['EmissionYear']})";
                } else {
                    $uangKertas = str_contains($itemNumber, 'TIDRKT22') ? "2,000 Rupiah (2022)" : $itemNumber;
                }

                $page3Rows[] = [
                    'no' => $no++,
                    'item' => $itemNumber,
                    'uangKertas' => $uangKertas,
                    'sejumlah' => $this->fmtNumber($sejumlahVal) . ' bilyet',
                    'berat' => $this->fmtNumber($beratItem, 2) . ' Kg',
                ];
            }

            $page3Totals = [
                'sejumlah' => $this->fmtNumber($sejumlahTotal) . ' bilyet',
                'berat' => $this->fmtNumber($beratTotal, 2) . ' Kg',
            ];

            $certs[] = [
                'date' => $dateDisp,
                'noBatch' => $cleanBatch,
                'mode' => $mode,
                'certId' => "{$dateDisp}__{$cleanBatch}__{$mode}",
                'page1' => $page1,
                'page2' => $page2,
                'page3' => ['rows' => $page3Rows, 'totals' => $page3Totals],
            ];
        }

        return $certs;
    }

    /**
     * Orchestrator utama untuk merangkum seluruh Report 1 sampai 8 dan dikirim ke Filament Page.
     */
    public function getReportData(
        ?string $date = null,
        ?string $batchId = null,
        ?int $supervisorId = null,
        string $dateFilterMode = 'single',
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $teamLeaderId = null
    ): array {
        if (!$date) {
            $latest = EventRecord3::orderByDesc('timestamp')->first();
            $date = $latest && $latest->timestamp ? Carbon::parse($latest->timestamp)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        }

        Log::info("BDS Report Debug - Searching data for date: {$date}");

        $formattedDateDot = Carbon::parse($date)->format('d.m.Y');
        $formattedDateSlash = Carbon::parse($date)->format('d/m/Y');

        // 1. Report 1 & 2
        $report1Data = $this->computeReport1($date);
        $report2Data = $this->computeReport2($date);

        $batches = collect();
        $batchItemGroups = collect();

        foreach ($report1Data['rows'] as $r) {
            $cleanBatchTotal = (float) str_replace([' Kg', ','], ['', ''], $r['batchTotal']);

            $batches->push((object) [
                'batch_id' => $r['noBatch'],
                'transport_mode' => $r['items'][0]['mode'] ?? 'AUTOMATIC_STAND_ALONE',
                'operator_name' => $r['items'][0]['operator'] ?? 'CCS_BDS',
                'start_time' => $r['mulai'],
                'end_time' => $r['selesai'],
                'duration_seconds' => 0,
                'throughput_ktg_j' => 0,
                'total_weight_kg' => $cleanBatchTotal,
                'total_bags' => 0,
                'idle_duration_seconds' => 0,
            ]);

            $itemsColl = collect();
            foreach ($r['items'] as $item) {
                $cleanItemWeight = (float) str_replace([' Kg', ','], ['', ''], $item['beratTotal']);
                $itemsColl->push((object) [
                    'item_code' => $item['item'],
                    'mode' => $item['mode'],
                    'operator' => $item['operator'],
                    'weight_kg' => $cleanItemWeight,
                ]);
            }

            $batchItemGroups->push((object) [
                'no' => $r['no'],
                'batch_id' => $r['noBatch'],
                'start_time' => $r['mulai'],
                'end_time' => $r['selesai'],
                'duration_formatted' => $r['durasi'],
                'throughput_formatted' => $r['throughput'],
                'mode' => $r['items'][0]['mode'] ?? 'AUTOMATIC_STAND_ALONE',
                'operator' => $r['items'][0]['operator'] ?? 'CCS_BDS',
                'items' => $itemsColl,
                'subtotal_weight' => $cleanBatchTotal,
            ]);
        }

        $transferPalletRecords = collect($report2Data['rows'])->map(fn($row) => (object) [
            'batch_id' => $row['noBatch'],
            'start_time' => $row['mulai'],
            'end_time' => $row['selesai'],
            'transport_id' => $row['transportId'],
            'pallet_id' => $row['palletId'],
            'item_code' => $row['item'],
            'transport_mode' => $row['mode'],
            'quantity' => (float) str_replace(',', '', $row['trayKantong']),
            'duration_seconds' => 0,
            'throughput' => 0,
        ]);

        // 2. Report 3 & 4
        $report3Data = $this->computeReport3($date);
        $report4Data = $this->computeReport4($date);

        // 3. Report 5, 6, 7, 8 (Menggunakan Virtual View & ER4)
        $er2Raw = $this->queryRecordsForDate(EventRecord2::class, $date);
        $er3Raw = $this->queryRecordsForDate(EventRecord3::class, $date);
        
        $er2 = $this->dedupLatestByBatchTray($er2Raw);
        $er3 = $er3Raw->filter(fn($r) => !empty(trim((string)$this->getField($r, 'batch_number', 'BatchNumber'))));

        $vwRows = $this->buildVwRoboticArmTray($er2, $er3);
        $er2TransportLookup = $this->buildEr2TransportLookup($er2);
        $er2TransportLookup2Key = $this->buildEr2TransportLookup2Key($er2);

        $report5Data = $this->computeReport5($vwRows, $er2TransportLookup);
        
        $denoLookup = []; 
        $emisLookup = []; 
        
        $report6Result = $this->computeReport6($vwRows, $er2TransportLookup2Key, $denoLookup);
        $minSelesaiByBatch = $report6Result['min_selesai_by_batch'];

        $er4Raw = $this->queryRecordsForDate(EventRecord4::class, $date);
        $er4Events = $er4Raw->map(function($r) {
            return [
                'ts' => $this->parseTs($this->getField($r, 'timestamp', 'Timestamp')),
                'state' => strtoupper(trim((string)$this->getField($r, 'idle_state', 'IdleState'))),
                'shredder' => strtoupper(trim((string)$this->getField($r, 'shredder', 'Shredder'))),
                'event' => strtoupper(trim((string)$this->getField($r, 'shredder_event', 'ShredderEvent'))),
            ];
        })->filter(fn($e) => $e['ts'] instanceof Carbon)->toArray();

        $report7Result = $this->computeReport7($vwRows, $er4Events, $minSelesaiByBatch);
        $report8Certs = $this->computeReport8Certificates($formattedDateDot, $vwRows, $report7Result['raw_by_batch'], $denoLookup, $emisLookup);

        $supervisor = User::where('role', 'supervisor')->first();
        $teamLeader = User::where('is_team_leader', true)->first() ?? User::where('role', 'operator')->first();

        return [
            'date' => $date,
            'batchId' => $batchId,
            'supervisor' => $supervisor,
            'supervisorName' => $supervisor?->name ?? 'Pemimpin Pelaksana',
            'supervisorNip' => $supervisor?->nip ?? '-',
            'teamLeader' => $teamLeader,
            'teamLeaderName' => $teamLeader?->name ?? 'Pengawas',
            'teamLeaderNip' => $teamLeader?->nip ?? '-',
            'batches' => $batches,
            'batchItemGroups' => $batchItemGroups,
            'transferPalletRecords' => $transferPalletRecords,
            'report3' => $report3Data,
            'report4' => $report4Data,
            'report5' => $report5Data,
            'report6' => $report6Result,
            'report7' => $report7Result['rows'],
            'report8Certificates' => $report8Certs,
            'formattedDateDot' => $formattedDateDot,
            'formattedDateSlash' => $formattedDateSlash,
        ];
    }
}
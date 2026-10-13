@php
    $fmtDur = function (?int $s): string {
        if (! $s || $s <= 0) {
            return '00:00:00';
        }
        $h = floor($s / 3600);
        $m = floor(($s % 3600) / 60);
        $sec = $s % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    };

    $hasThis = isset($this);
    $supName = $supervisorName ?? ($hasThis ? $this->selectedSupervisorName : null) ?? '-';
    $supNip = $supervisorNip ?? ($hasThis ? $this->selectedSupervisorNip : null) ?? '-';
    $tlName = $teamLeaderName ?? ($hasThis ? $this->selectedTeamLeaderName : null) ?? '-';
    $tlNip = $teamLeaderNip ?? ($hasThis ? $this->selectedTeamLeaderNip : null) ?? '-';
    
    // Strip BDS from printer name
    $rawPrinter = $printedBy ?? Auth::user()?->name ?? ($hasThis ? $this->selectedSupervisorName : null) ?? $supName;
    $cleanPrinter = trim(preg_replace('/\bBDS\b/i', '', $rawPrinter));
    $cleanPrinter = trim(str_ireplace('BDS', '', $cleanPrinter));
    $cleanPrinter = preg_replace('/\s+/', ' ', $cleanPrinter);
    $printer = !empty($cleanPrinter) ? $cleanPrinter : $rawPrinter;

    $dtDot = $formattedDateDot ?? ($hasThis ? $this->formattedDateDot : null) ?? date('d.m.Y');
    $dtSlash = $formattedDateSlash ?? ($hasThis ? $this->formattedDateSlash : null) ?? date('d/m/Y');
    $actReport = $activeReport ?? ($hasThis ? $this->activeReport : null) ?? 'all';
    $prtTimestamp = $printTimestamp ?? date('d.m.Y H:i');

    $groups = $batchItemGroups ?? ($hasThis ? $this->batchItemGroups : null) ?? collect();
    $transPallets = $transferPalletRecords ?? ($hasThis ? $this->transferPalletRecords : null) ?? collect();
    $depalTrays = $depalletizerTrayRecords ?? ($hasThis ? $this->depalletizerTrayRecords : null) ?? collect();
    $feedTrays = $feederTrayRecords ?? ($hasThis ? $this->feederTrayRecords : null) ?? collect();
    $batchList = $batches ?? ($hasThis ? $this->batches : null) ?? collect();
    $denoms = $certificateDenominations ?? ($hasThis ? $this->certificateDenominations : null) ?? collect();
@endphp

{{-- ============================================================ --}}
{{-- REPORT 1: Transfer Unit Per Batch                            --}}
{{-- ============================================================ --}}
@if($actReport === 'report1' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Operasional Unit Transfer</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Batch</div>
            </div>
            <div class="text-right">
                <div>90 Kantong/ J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th style="width: 145px;">Mulai</th>
                        <th style="width: 145px;">Selesai</th>
                        <th style="width: 75px;">Durasi</th>
                        <th style="width: 90px;">Throughput</th>
                        <th style="width: 80px;">No Batch</th>
                        <th>Item</th>
                        <th style="width: 95px;">Mode</th>
                        <th style="width: 85px;">Operator</th>
                        <th style="width: 85px;">Berat Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $grp)
                        @php $firstItem = $grp->items->first(); @endphp
                        <tr>
                            <td rowspan="{{ $grp->items->count() }}" class="text-center">{{ $grp->no }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->start_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->end_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->duration_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->throughput_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->batch_id }}</td>
                            <td>{{ $firstItem->item_code ?? '-' }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->mode }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->operator }}</td>
                            <td>{{ number_format($firstItem->weight_kg ?? 0, 2) }} Kg</td>
                        </tr>
                        @foreach($grp->items->skip(1) as $item)
                            <tr>
                                <td>{{ $item->item_code }}</td>
                                <td>{{ number_format($item->weight_kg, 2) }} Kg</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-bold">Total</td>
                            <td class="text-bold">{{ number_format($grp->subtotal_weight, 2) }} Kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data batch untuk tanggal operasional ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- REPORT 2: Transfer Unit Per Pallet                           --}}
{{-- ============================================================ --}}
@if($actReport === 'report2' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Operasional Unit Transfer</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Pallet</div>
            </div>
            <div class="text-right">
                <div>90 Kantong/ J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">No. Batch</th>
                        <th style="width: 95px;">Mulai</th>
                        <th style="width: 95px;">Selesai</th>
                        <th style="width: 75px;">Transport ID</th>
                        <th style="width: 95px;">Pallet ID</th>
                        <th>Item</th>
                        <th style="width: 75px;">Mode</th>
                        <th style="width: 60px;">Tray / Kantong</th>
                        <th style="width: 70px;">Durasi</th>
                        <th style="width: 90px;">Throughput</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totBags2 = 0; $totDur2 = 0; @endphp
                    @forelse($transPallets as $pal)
                        @php
                            $totBags2 += $pal->quantity;
                            $totDur2 += $pal->duration_seconds;
                        @endphp
                        <tr>
                            <td>{{ $pal->batch_id }}</td>
                            <td>{{ $pal->start_time ? \Carbon\Carbon::parse($pal->start_time)->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $pal->end_time ? \Carbon\Carbon::parse($pal->end_time)->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $pal->transport_id }}</td>
                            <td>{{ $pal->pallet_id }}</td>
                            <td>{{ $pal->item_code }}</td>
                            <td>{{ $pal->transport_mode }}</td>
                            <td>{{ $pal->quantity }}</td>
                            <td>{{ $fmtDur($pal->duration_seconds) }}</td>
                            <td>{{ number_format($pal->throughput, 2) }} Kantong/J</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data pallet transfer untuk tanggal operasional ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transPallets->isNotEmpty())
                    <tfoot>
                        <tr class="summary-total-row">
                            <td class="text-bold">Jumlah</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right text-bold">{{ $totBags2 }}</td>
                            <td class="text-center text-bold">{{ $fmtDur($totDur2) }}</td>
                            <td class="text-right text-bold">{{ $totDur2 > 0 ? number_format(($totBags2 / ($totDur2 / 3600)), 2) : '0.00' }} Kantong/J</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- REPORT 3: Robotic Arm Per Batch                              --}}
{{-- ============================================================ --}}
@if($actReport === 'report3' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Operasional Unit Depalletisasi</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Batch</div>
            </div>
            <div class="text-right">
                <div>90 Kantong/ J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th style="width: 80px;">No Batch</th>
                        <th style="width: 145px;">Mulai</th>
                        <th style="width: 145px;">Selesai</th>
                        <th style="width: 75px;">Durasi</th>
                        <th style="width: 90px;">Throughput</th>
                        <th style="width: 95px;">Mode</th>
                        <th style="width: 85px;">Operator</th>
                        <th>Item</th>
                        <th style="width: 85px;">Berat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $grp)
                        @php $firstItem = $grp->items->first(); @endphp
                        <tr>
                            <td rowspan="{{ $grp->items->count() }}" class="text-center">{{ $grp->no }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->batch_id }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->start_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->end_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->duration_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->throughput_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->mode }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->operator }}</td>
                            <td>{{ $firstItem->item_code ?? '-' }}</td>
                            <td>{{ number_format($firstItem->weight_kg ?? 0, 2) }} Kg</td>
                        </tr>
                        @foreach($grp->items->skip(1) as $item)
                            <tr>
                                <td>{{ $item->item_code }}</td>
                                <td>{{ number_format($item->weight_kg, 2) }} Kg</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-bold">Total</td>
                            <td class="text-bold">{{ number_format($grp->subtotal_weight, 2) }} Kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data batch robotic arm untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- REPORT 4: Robotic Arm Per Pallet                             --}}
{{-- ============================================================ --}}
@if($actReport === 'report4' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Operasional Unit Depalletisasi</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Operasional</div>
            </div>
            <div class="text-right">
                <div>90 Kantong/ J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">No. Batch</th>
                        <th style="width: 95px;">Mulai</th>
                        <th style="width: 95px;">Selesai</th>
                        <th style="width: 75px;">Transport ID</th>
                        <th style="width: 95px;">Pallet ID</th>
                        <th>Item</th>
                        <th style="width: 75px;">Mode</th>
                        <th style="width: 85px;">Tray / Kantong</th>
                        <th style="width: 70px;">Durasi</th>
                        <th style="width: 90px;">Throughput</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totBags4 = 0; $totDur4 = 0; @endphp
                    @forelse($transPallets as $pal)
                        @php
                            $totBags4 += $pal->quantity;
                            $totDur4 += $pal->duration_seconds;
                        @endphp
                        <tr>
                            <td>{{ $pal->batch_id }}</td>
                            <td>{{ $pal->start_time ? \Carbon\Carbon::parse($pal->start_time)->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $pal->end_time ? \Carbon\Carbon::parse($pal->end_time)->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $pal->transport_id }}</td>
                            <td>{{ $pal->pallet_id }}</td>
                            <td>{{ $pal->item_code }}</td>
                            <td>{{ $pal->transport_mode }}</td>
                            <td>{{ $pal->quantity }}</td>
                            <td>{{ $fmtDur($pal->duration_seconds) }}</td>
                            <td>{{ number_format($pal->throughput, 2) }} Kantong/J</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data operasional pallet robotic arm untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transPallets->isNotEmpty())
                    <tfoot>
                        <tr class="summary-total-row">
                            <td class="text-bold">Jumlah</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right text-bold">{{ $totBags4 }}</td>
                            <td class="text-center text-bold">{{ $fmtDur($totDur4) }}</td>
                            <td class="text-right text-bold">{{ $totDur4 > 0 ? number_format(($totBags4 / ($totDur4 / 3600)), 2) : '0.00' }} Kantong/J</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- REPORT 5: Robotic Arm Per Tray                               --}}
{{-- ============================================================ --}}
@if($actReport === 'report5' || $actReport === 'all')
    @php
        $depalTrayChunks = $depalTrays->isNotEmpty() ? $depalTrays->chunk(25) : collect([collect()]);
        $totalDepalPages = $depalTrayChunks->count();
        $totWeight5 = $depalTrays->sum(function ($t) {
            return (float) ($t->weight_kg > 0 ? $t->weight_kg : (($t->net_weight_gm ?? 0) / 1000));
        });
        $totDur5 = $depalTrays->sum(function ($t) {
            return (int) ($t->duration_seconds ?? $t->feeder_duration_seconds ?? 0);
        });
        $totTrays5 = $depalTrays->count();
    @endphp
    @foreach($depalTrayChunks as $depalIndex => $chunk)
        <div class="bds-paper-sheet">
            <div class="doc-header-top">
                <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
            </div>

            <div class="doc-system-line">
                <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
                <div class="doc-system-center">BDS</div>
                <div style="width: 35%;"></div>
            </div>

            <div class="doc-title-line">
                <div class="doc-main-title">Laporan Operasional Unit Depalletisasi</div>
                <div class="doc-page-counter">
                    <span>Halaman</span>
                    <span>{{ $depalIndex + 1 }}/{{ $totalDepalPages }}</span>
                </div>
            </div>

            <div class="doc-divider-black"></div>

            <div class="doc-meta-block">
                <div>
                    <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                    <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                    <div class="doc-meta-subtitle">Rekapitulasi Operasional</div>
                </div>
                <div class="text-right">
                    <div>90 Kantong/ J</div>
                    <div>{{ $dtDot }}</div>
                </div>
            </div>

            <div class="doc-table-card">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No. Batch</th>
                            <th style="width: 90px;">Pallet ID</th>
                            <th style="width: 90px;">Mulai</th>
                            <th style="width: 90px;">Selesai</th>
                            <th style="width: 70px;">Transport ID</th>
                            <th style="width: 85px;">Tray ID</th>
                            <th>Item</th>
                            <th style="width: 75px;">Mode</th>
                            <th style="width: 70px;">Berat</th>
                            <th style="width: 65px;">Durasi</th>
                            <th style="width: 85px;">Throughput</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chunk as $tray)
                            @php
                                $trayWeight = (float) ($tray->weight_kg > 0 ? $tray->weight_kg : (($tray->net_weight_gm ?? 0) / 1000));
                                $trayDur = (int) ($tray->duration_seconds ?? $tray->feeder_duration_seconds ?? 0);
                                $trayThroughput = (float) ($tray->throughput ?? $tray->feeder_throughput ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $tray->batch_id }}</td>
                                <td>{{ $tray->pallet_id }}</td>
                                <td>{{ $tray->feeder_start_time ? \Carbon\Carbon::parse($tray->feeder_start_time)->format('Y-m-d H:i:s') : '-' }}</td>
                                <td>{{ $tray->feeder_end_time ? \Carbon\Carbon::parse($tray->feeder_end_time)->format('Y-m-d H:i:s') : '-' }}</td>
                                <td>{{ $tray->transport_id ?? '-' }}</td>
                                <td>{{ $tray->tray_id }}</td>
                                <td>{{ $tray->item_code }}</td>
                                <td>{{ $tray->transport_mode }}</td>
                                <td>{{ number_format($trayWeight, 2) }} Kg</td>
                                <td>{{ $fmtDur($trayDur) }}</td>
                                <td>{{ number_format($trayThroughput, 2) }} Kantong/J</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                    Tidak ada data tray robotic arm untuk tanggal operasional ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($loop->last && $depalTrays->isNotEmpty())
                        <tfoot>
                            <tr class="summary-total-row">
                                <td class="text-bold">Jumlah</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-right text-bold">{{ number_format($totWeight5, 2) }} Kg</td>
                                <td class="text-center text-bold">{{ $fmtDur($totDur5) }}</td>
                                <td class="text-right text-bold">{{ $totDur5 > 0 ? number_format(($totTrays5 / ($totDur5 / 3600)), 2) : '0.00' }} Kantong/J</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="doc-sheet-footer">
                <div class="doc-sheet-footer-inner">
                    <table class="doc-footer-table">
                        <tr>
                            <td class="doc-footer-label">Dicetak Oleh :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                        </tr>
                        <tr>
                            <td class="doc-footer-label">Dicetak Tanggal :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- ============================================================ --}}
{{-- REPORT 6: Feeder Unit Per Tray                               --}}
{{-- ============================================================ --}}
@if($actReport === 'report6' || $actReport === 'all')
    @php
        $feedTrayChunks = $feedTrays->isNotEmpty() ? $feedTrays->chunk(25) : collect([collect()]);
        $totalFeedPages = $feedTrayChunks->count();
        $totWeight6 = $feedTrays->sum(function ($t) {
            return (float) ($t->weight_kg > 0 ? $t->weight_kg : (($t->net_weight_gm ?? 0) / 1000));
        });
        $totDur6 = $feedTrays->sum(function ($t) {
            return (int) ($t->duration_seconds ?? $t->feeder_duration_seconds ?? 0);
        });
        $totTrays6 = $feedTrays->count();
    @endphp
    @foreach($feedTrayChunks as $feedIndex => $chunk)
        <div class="bds-paper-sheet">
            <div class="doc-header-top">
                <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
            </div>

            <div class="doc-system-line">
                <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
                <div class="doc-system-center">BDS</div>
                <div style="width: 35%;"></div>
            </div>

            <div class="doc-title-line">
                <div class="doc-main-title">Laporan Operasional Unit Pengumpan</div>
                <div class="doc-page-counter">
                    <span>Halaman</span>
                    <span>{{ $feedIndex + 1 }}/{{ $totalFeedPages }}</span>
                </div>
            </div>

            <div class="doc-divider-black"></div>

            <div class="doc-meta-block">
                <div>
                    <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                    <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                    <div class="doc-meta-subtitle">Rekapitulasi Operasional</div>
                </div>
                <div class="text-right">
                    <div>90 Kantong/ J</div>
                    <div>{{ $dtDot }}</div>
                </div>
            </div>

            <div class="doc-table-card">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th style="width: 75px;">No. Batch</th>
                            <th style="width: 95px;">Mulai</th>
                            <th style="width: 95px;">Selesai</th>
                            <th style="width: 75px;">Transport ID</th>
                            <th style="width: 75px;">Tray ID</th>
                            <th>Item</th>
                            <th style="width: 80px;">Mode</th>
                            <th style="width: 75px;">Berat</th>
                            <th style="width: 65px;">Durasi</th>
                            <th style="width: 85px;">Throughput</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chunk as $ftray)
                            @php
                                $ftrayWeight = (float) ($ftray->weight_kg > 0 ? $ftray->weight_kg : (($ftray->net_weight_gm ?? 0) / 1000));
                                $ftrayDur = (int) ($ftray->duration_seconds ?? $ftray->feeder_duration_seconds ?? 0);
                                $ftrayThroughput = (float) ($ftray->throughput ?? $ftray->feeder_throughput ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $ftray->batch_id }}</td>
                                <td>{{ $ftray->feeder_start_time ? \Carbon\Carbon::parse($ftray->feeder_start_time)->format('Y-m-d H:i:s') : '-' }}</td>
                                <td>{{ $ftray->feeder_end_time ? \Carbon\Carbon::parse($ftray->feeder_end_time)->format('Y-m-d H:i:s') : '-' }}</td>
                                <td>{{ $ftray->transport_id ?? '-' }}</td>
                                <td>{{ $ftray->tray_id }}</td>
                                <td>{{ $ftray->item_code }}</td>
                                <td>{{ $ftray->transport_mode }}</td>
                                <td>{{ number_format($ftrayWeight, 2) }} Kg</td>
                                <td>{{ $fmtDur($ftrayDur) }}</td>
                                <td>{{ number_format($ftrayThroughput, 2) }} Kantong/J</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                    Tidak ada data tray pengumpan untuk tanggal operasional ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($loop->last && $feedTrays->isNotEmpty())
                        <tfoot>
                            <tr class="summary-total-row">
                                <td class="text-bold">Jumlah</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-right text-bold">{{ number_format($totWeight6, 2) }} Kg</td>
                                <td class="text-center text-bold">{{ $fmtDur($totDur6) }}</td>
                                <td class="text-right text-bold">{{ $totDur6 > 0 ? number_format(($totTrays6 / ($totDur6 / 3600)), 2) : '0.00' }} Kantong/J</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="doc-sheet-footer">
                <div class="doc-sheet-footer-inner">
                    <table class="doc-footer-table">
                        <tr>
                            <td class="doc-footer-label">Dicetak Oleh :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                        </tr>
                        <tr>
                            <td class="doc-footer-label">Dicetak Tanggal :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- ============================================================ --}}
{{-- REPORT 7: Shredder Unit Per Batch                            --}}
{{-- ============================================================ --}}
@if($actReport === 'report7' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Operasional Unit Peracik</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Batch</div>
            </div>
            <div class="text-right">
                <div>900 Kg/J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th style="width: 80px;">No Batch</th>
                        <th style="width: 145px;">Mulai</th>
                        <th style="width: 145px;">Selesai</th>
                        <th style="width: 75px;">Durasi</th>
                        <th style="width: 90px;">Throughput</th>
                        <th style="width: 95px;">Mode</th>
                        <th style="width: 85px;">Operator</th>
                        <th>Item</th>
                        <th style="width: 85px;">Berat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $grp)
                        @php $firstItem = $grp->items->first(); @endphp
                        <tr>
                            <td rowspan="{{ $grp->items->count() }}" class="text-center">{{ $grp->no }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->batch_id }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->start_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->end_time }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->duration_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->throughput_formatted }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->mode }}</td>
                            <td rowspan="{{ $grp->items->count() }}">{{ $grp->operator }}</td>
                            <td>{{ $firstItem->item_code ?? '-' }}</td>
                            <td>{{ number_format($firstItem->weight_kg ?? 0, 2) }} Kg</td>
                        </tr>
                        @foreach($grp->items->skip(1) as $item)
                            <tr>
                                <td>{{ $item->item_code }}</td>
                                <td>{{ number_format($item->weight_kg, 2) }} Kg</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-bold">Total</td>
                            <td class="text-bold">{{ number_format($grp->subtotal_weight, 2) }} Kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data batch peracik untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- REPORT 8: Shredder Unit Certificate (3 Sub-Pages)             --}}
{{-- ============================================================ --}}

{{-- REPORT 8 - Sub-Page 1: Pertanggung Jawaban --}}
@if($actReport === 'report8' || $actReport === 'all')
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Sertifikat Peracikan</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/3</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Pertanggung Jawaban</div>
            </div>
            <div class="text-right">
                <div>900 Kg/J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 140px;">No Batch</th>
                        <th style="width: 160px;">Mode</th>
                        <th style="width: 220px;">Operator</th>
                        <th class="text-center">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batchList as $idx8 => $b8)
                        <tr>
                            <td class="text-center">{{ $idx8 + 1 }}</td>
                            <td>{{ $b8->batch_id }}</td>
                            <td>{{ $b8->transport_mode ?? 'AUTOMATIC' }}</td>
                            <td>{{ $b8->operator_name ?? 'CCS_BDS' }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data batch peracikan untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="doc-signature-block" style="margin-top: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px;">
                <div style="font-size: 12px; color: #000; margin-bottom: 10px;">
                    <div style="margin-bottom: 6px;">
                        Pemimpin Pelaksana : &nbsp;<span class="doc-underlined">{{ $supName }}</span> &nbsp;(<span class="doc-underlined">{{ $supNip }}</span>)
                    </div>
                    <div>
                        Pengawas : &nbsp;<span class="doc-underlined">{{ $tlName }}</span> &nbsp;(<span class="doc-underlined">{{ $tlNip }}</span>)
                    </div>
                </div>
                <div style="display: flex; gap: 20px; align-items: flex-end;">
                    <div style="text-align: center; width: 140px;">
                        <div style="font-size: 11px; font-weight: 700; margin-bottom: 4px; color: #000;">{{ $tlName !== '-' ? $tlName : 'Pengawas' }}</div>
                        <div class="doc-signature-box" style="margin: 0 auto; width: 90px; height: 50px;"></div>
                    </div>
                    <div style="text-align: center; width: 140px;">
                        <div style="font-size: 11px; font-weight: 700; margin-bottom: 4px; color: #000;">{{ $supName !== '-' ? $supName : 'Pemimpin Pelaksana' }}</div>
                        <div class="doc-signature-box" style="margin: 0 auto; width: 90px; height: 50px;"></div>
                    </div>
                </div>
            </div>

            <div style="font-size: 12px; font-weight: 700; color: #000; margin-bottom: 4px;">Deskripsi Muatan</div>
            <div class="doc-divider-black" style="margin: 0 0 10px 0; width: 100%;"></div>
            <div style="font-size: 12px; color: #000;">
                Tanggal &nbsp;&nbsp;<span class="doc-underlined">{{ $dtSlash }}</span>
            </div>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- REPORT 8 - Sub-Page 2: Waktu & Idle Time --}}
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Sertifikat Peracikan</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>2/3</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rekapitulasi Waktu & Idle Time Mesin Peracik</div>
            </div>
            <div class="text-right">
                <div>900 Kg/J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th style="width: 80px;">No Batch</th>
                        <th style="width: 100px;">Mode</th>
                        <th style="width: 110px;">Mulai</th>
                        <th style="width: 110px;">Selesai</th>
                        <th style="width: 75px;">Idle Time</th>
                        <th style="width: 80px;">Waktu Proses</th>
                        <th style="width: 85px;">Throughput</th>
                        <th style="width: 90px;">Total Berat</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totIdle9 = 0; $totProc9 = 0; $totWt9 = 0; $totBags9 = 0;
                    @endphp
                    @forelse($batchList as $idx9 => $b9)
                        @php
                            $totIdle9 += $b9->idle_duration_seconds;
                            $totProc9 += $b9->duration_seconds;
                            $totWt9 += $b9->total_weight_kg;
                            $totBags9 += $b9->total_bags;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx9 + 1 }}</td>
                            <td>{{ $b9->batch_id }}</td>
                            <td>{{ $b9->transport_mode ?? 'AUTOMATIC' }}</td>
                            <td>{{ $b9->start_time ? \Carbon\Carbon::parse($b9->start_time)->format('d/m/Y H:i:s') : '-' }}</td>
                            <td>{{ $b9->end_time ? \Carbon\Carbon::parse($b9->end_time)->format('d/m/Y H:i:s') : '-' }}</td>
                            <td>{{ $fmtDur($b9->idle_duration_seconds) }}</td>
                            <td>{{ $fmtDur($b9->duration_seconds) }}</td>
                            <td>{{ number_format($b9->throughput_ktg_j, 2) }} Kg/J</td>
                            <td>{{ number_format($b9->total_weight_kg, 2) }} Kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada data waktu mesin peracik untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($batchList->isNotEmpty())
                    <tfoot>
                        <tr class="summary-total-row">
                            <td class="text-bold">Jumlah</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center text-bold">{{ $fmtDur($totIdle9) }}</td>
                            <td class="text-center text-bold">{{ $fmtDur($totProc9) }}</td>
                            <td class="text-right text-bold">{{ $totProc9 > 0 ? number_format(($totWt9 / ($totProc9 / 3600)), 2) : '0.00' }} Kg/J</td>
                            <td class="text-right text-bold">{{ number_format($totWt9, 2) }} Kg</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- REPORT 8 - Sub-Page 3: Rincian Fisik Denominasi --}}
    <div class="bds-paper-sheet">
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS</div>
            <div style="width: 35%;"></div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Sertifikat Peracikan</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>3/3</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        <div class="doc-meta-block">
            <div>
                <div>Kapasitas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
                <div class="doc-meta-subtitle">Rincian Fisik Denominasi Uang Kertas Yang Dimusnahkan</div>
            </div>
            <div class="text-right">
                <div>900 Kg/J</div>
                <div>{{ $dtDot }}</div>
            </div>
        </div>

        <div class="doc-table-card">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th style="width: 95px;">No Batch</th>
                        <th style="width: 140px;">Mode</th>
                        <th style="width: 120px;">Item</th>
                        <th>Uang Kertas</th>
                        <th style="width: 110px;">Sejumlah</th>
                        <th style="width: 90px;">Berat</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totGm10 = 0; $totKg10 = 0; @endphp
                    @forelse($denoms as $denom)
                        @php $totGm10 += $denom->total_gm; $totKg10 += $denom->total_kg; @endphp
                        <tr>
                            <td class="text-center">{{ $denom->no }}</td>
                            <td>{{ $denom->batch_id }}</td>
                            <td>{{ $denom->mode }}</td>
                            <td>{{ $denom->item_code }}</td>
                            <td>{{ $denom->currency_name }}</td>
                            <td>{{ number_format($denom->total_gm, 0, ',', ',') }} gm</td>
                            <td>{{ number_format($denom->total_kg, 2) }} Kg</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                                Tidak ada rincian denominasi fisik untuk batch ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($denoms->isNotEmpty())
                    <tfoot>
                        <tr class="summary-total-row">
                            <td class="text-bold">Total</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right text-bold">{{ number_format($totGm10, 0, ',', ',') }} gm</td>
                            <td class="text-right text-bold">{{ number_format($totKg10, 2) }} Kg</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="doc-sheet-footer">
            <div class="doc-sheet-footer-inner">
                <table class="doc-footer-table">
                    <tr>
                        <td class="doc-footer-label">Dicetak Oleh :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                    </tr>
                    <tr>
                        <td class="doc-footer-label">Dicetak Tanggal :</td>
                        <td class="doc-footer-value"><span class="doc-underlined">{{ $prtTimestamp }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endif
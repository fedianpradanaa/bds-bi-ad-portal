@php
    $hasThis = isset($this);
    $lang = $reportLang ?? ($hasThis ? $this->reportLang : 'en');
    
    $rawPrinter = Auth::user()?->name ?? 'Administrator';
    $cleanPrinter = trim(str_ireplace('BDS', '', $rawPrinter));
    $printer = !empty($cleanPrinter) ? $cleanPrinter : $rawPrinter;

    $prtTimestamp = date('d.m.Y H:i');
    $service = app(\App\Services\Bds\BreakdownReportService::class);
    $reports = $reports ?? collect();
@endphp

<div class="bds-paper-sheet">
    {{-- Header Top with BI Logo --}}
    <div class="doc-header-top">
        <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        
        {{-- Tombol Pengubah Bahasa (English / Indonesia) --}}
        <div class="no-print" style="display: flex; gap: 6px; align-items: center;">
            <span style="font-size: 11px; font-weight: 700; color: #475569;">Language:</span>
            <button type="button" wire:click="$set('reportLang', 'en')" 
                style="padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; border: 1px solid #cbd5e1; background: {{ $lang === 'en' ? '#2563eb' : '#f8fafc' }}; color: {{ $lang === 'en' ? '#fff' : '#334155' }};">
                EN
            </button>
            <button type="button" wire:click="$set('reportLang', 'id')" 
                style="padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; border: 1px solid #cbd5e1; background: {{ $lang === 'id' ? '#2563eb' : '#f8fafc' }}; color: {{ $lang === 'id' ? '#fff' : '#334155' }};">
                ID
            </button>
        </div>
    </div>

    <div class="doc-system-line">
        <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
        <div class="doc-system-center">BDS</div>
        <div style="width: 35%;"></div>
    </div>

    <div class="doc-title-line">
        <div class="doc-main-title">Laporan Breakdown & Event Sistem</div>
        <div class="doc-page-counter">
            <span>Halaman</span>
            <span>1/1</span>
        </div>
    </div>

    <div class="doc-divider-black"></div>

    {{-- Meta Block --}}
    <div class="doc-meta-block">
        <div>
            <div>Requested timeframe &nbsp;&nbsp;&nbsp;&nbsp;:</div>
            <div class="doc-meta-subtitle">Rekapitulasi Event & Breakdown</div>
        </div>
        <div class="text-right">
            <div>{{ $startDate ?? '-' }} s.d. {{ $endDate ?? '-' }}</div>
        </div>
    </div>

    {{-- Tabel Data dengan Format Kolom Sesuai Referensi Gambar Anda --}}
    <div class="doc-table-card">
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th style="width: 85px;">Date</th>
                    <th style="width: 100px;">Time of Breakdown</th>
                    <th style="width: 85px;">Duration of Breakdown</th>
                    <th style="width: 140px;">The Unit</th>
                    <th>Breakdown Details</th>
                    <th style="width: 180px;">Follow-Up</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $index => $rep)
                    @php
                        // Pilih sumber teks berdasarkan bahasa yang aktif ($lang)
                        $rawDesc = ($lang === 'id') ? ($rep->ind_description ?? $rep->eng_description) : ($rep->eng_description ?? $rep->ind_description);
                        $parsed = $service->parseDescription($rawDesc, $lang);

                        // Ekstrak jam dari EventStart (format YYYY-MM-DD HH:MM:SS -> HH:MM:SS)
                        $timeOnly = $rep->event_start ? date('H:i:s', strtotime($rep->event_start)) : '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $rep->date }}</td>
                        <td class="text-center">{{ $timeOnly }}</td>
                        <td class="text-center font-mono">{{ $rep->duration }}</td>
                        <td class="text-bold">{{ $rep->event_unit }} ({{ $rep->event_number }})</td>
                        <td>{{ $parsed['details'] }}</td>
                        <td style="color: #334155;">{{ $parsed['follow_up'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 24px; color: #64748b; font-style: italic;">
                            Tidak ada data breakdown pada rentang tanggal tersebut.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
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
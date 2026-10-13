@php
    $fmtNum = function ($val) {
        $formatted = number_format((float)($val ?? 0), 2, ',', '.');
        return str_ends_with($formatted, ',00') ? substr($formatted, 0, -3) : $formatted;
    };
@endphp

@if ($hasData && $reportData)
    <div class="bds-paper-sheet">
        {{-- Header Top with BI Logo matching BDS Report --}}
        <div class="doc-header-top">
            <img src="{{ asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia" class="doc-bi-logo">
        </div>

        <div class="doc-system-line">
            <div class="doc-system-label">Sistem Peracikan Uang Kertas :</div>
            <div class="doc-system-center">BDS SC 1000/2</div>
            <div style="width: 35%; text-align: right; font-size: 11px;">
                System ID: <strong>{{ $reportData['system_id'] ?? 'BDS' }}</strong>
            </div>
        </div>

        <div class="doc-title-line">
            <div class="doc-main-title">Laporan Output Operasional (Briquettes Summary)</div>
            <div class="doc-page-counter">
                <span>Halaman</span>
                <span>1/1</span>
            </div>
        </div>

        <div class="doc-divider-black"></div>

        {{-- Eckelmann Output Report Original Section --}}
        <div class="eckelmann-container">
            <div class="eckelmann-header-title">
                <h3>Output report</h3>
            </div>

            <div class="eckelmann-timeframe-row">
                <div class="eckelmann-timeframe-label">Requested timeframe</div>
                <div>{{ $reportData['timeframe_display'] }}</div>
            </div>

            <hr class="eckelmann-divider-black">

            {{-- Metadata Grid --}}
            <div>
                <div class="eckelmann-meta-row">
                    <div class="eckelmann-meta-label">System ID:</div>
                    <div>{{ $reportData['system_id'] }}</div>
                </div>
                <div class="eckelmann-meta-row">
                    <div class="eckelmann-meta-label">Press started:</div>
                    <div>{{ $reportData['press_started'] }}</div>
                </div>
                <div class="eckelmann-meta-row">
                    <div class="eckelmann-meta-label">Press ended:</div>
                    <div>{{ $reportData['press_ended'] }}</div>
                </div>
                <div class="eckelmann-meta-row">
                    <div class="eckelmann-meta-label">Press operational time:</div>
                    <div>{{ $reportData['press_operational_time'] }}</div>
                </div>
                <div class="eckelmann-meta-row" style="margin-top: 14px;">
                    <div class="eckelmann-meta-label">Printed:</div>
                    <div>{{ $reportData['printed'] }}</div>
                </div>
            </div>

            <hr class="eckelmann-divider-black">

            {{-- Press 1 --}}
            <div class="eckelmann-press-block">
                <div class="eckelmann-press-title"><u>Press 1:</u></div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Weight (kg):</div>
                    <div>{{ $fmtNum($reportData['press_1']['weight_kg'] ?? 0) }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Duration (hh:mm:ss):</div>
                    <div>{{ $reportData['press_1']['duration'] }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Throughput (kg/h):</div>
                    <div>{{ $fmtNum($reportData['press_1']['throughput_kg_h'] ?? 0) }}</div>
                </div>
            </div>

            {{-- Press 2 --}}
            <div class="eckelmann-press-block">
                <div class="eckelmann-press-title"><u>Press 2:</u></div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Weight (kg):</div>
                    <div>{{ $fmtNum($reportData['press_2']['weight_kg'] ?? 0) }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Duration (hh:mm:ss):</div>
                    <div>{{ $reportData['press_2']['duration'] }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Throughput (kg/h):</div>
                    <div>{{ $fmtNum($reportData['press_2']['throughput_kg_h'] ?? 0) }}</div>
                </div>
            </div>

            <div class="eckelmann-total-header">Total</div>
            <hr class="eckelmann-divider-black">

            {{-- Total Press 1 (Menggunakan data kumulatif dari service) --}}
            <div class="eckelmann-press-block">
                <div class="eckelmann-press-title"><u>Press 1:</u></div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Weight (kg):</div>
                    <div>{{ $fmtNum($reportData['total']['press_1']['weight_kg'] ?? 0) }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Count:</div>
                    <div>{{ number_format($reportData['total']['press_1']['count'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Weight of Briquette (g):</div>
                    <div>{{ $fmtNum($reportData['total']['press_1']['weight_per_briquette_g'] ?? 1550) }}</div>
                </div>
            </div>

            {{-- Total Press 2 (Menggunakan data kumulatif dari service) --}}
            <div class="eckelmann-press-block">
                <div class="eckelmann-press-title"><u>Press 2:</u></div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Weight (kg):</div>
                    <div>{{ $fmtNum($reportData['total']['press_2']['weight_kg'] ?? 0) }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Briquettes Count:</div>
                    <div>{{ number_format($reportData['total']['press_2']['count'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="eckelmann-press-row">
                    <div class="eckelmann-press-label">Weight of Briquette (g):</div>
                    <div>{{ $fmtNum($reportData['total']['press_2']['weight_per_briquette_g'] ?? 1550) }}</div>
                </div>
            </div>
        </div>

        {{-- Bottom Section pinned to the bottom of the page --}}
        <div class="doc-sheet-bottom-section" style="margin-top: auto;">
            <hr class="eckelmann-divider-black">

            <div class="eckelmann-subfooter-line">
                <div>{{ $reportData['timeframe_display'] }}</div>
                <div>System ID :  {{ $reportData['system_id'] }}</div>
                <div>Page 1 / 1</div>
            </div>

            {{-- Signatures for Pemimpin Pelaksana & Pengawas --}}
            <div class="doc-signature-block" style="margin-top: 10px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
                    <div style="font-size: 12px; color: #000; margin-bottom: 6px;">
                        <div style="margin-bottom: 4px;">
                            Pemimpin Pelaksana : &nbsp;<span class="doc-underlined">{{ $supervisorName }}</span> &nbsp;(<span class="doc-underlined">{{ $supervisorNip }}</span>)
                        </div>
                        <div>
                            Pengawas : &nbsp;<span class="doc-underlined">{{ $teamLeaderName }}</span> &nbsp;(<span class="doc-underlined">{{ $teamLeaderNip }}</span>)
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; align-items: flex-end;">
                        <div style="text-align: center; width: 140px;">
                            <div style="font-size: 11px; font-weight: 700; margin-bottom: 4px; color: #000;">{{ $teamLeaderName !== '-' ? $teamLeaderName : 'Pengawas' }}</div>
                            <div class="doc-signature-box" style="margin: 0 auto; width: 90px; height: 50px;"></div>
                        </div>
                        <div style="text-align: center; width: 140px;">
                            <div style="font-size: 11px; font-weight: 700; margin-bottom: 4px; color: #000;">{{ $supervisorName !== '-' ? $supervisorName : 'Pemimpin Pelaksana' }}</div>
                            <div class="doc-signature-box" style="margin: 0 auto; width: 90px; height: 50px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sheet Footer matching BDS Report --}}
            <div class="doc-sheet-footer" style="padding-top: 2px;">
                <div class="doc-sheet-footer-inner">
                    <table class="doc-footer-table">
                        <tr>
                            <td class="doc-footer-label">Dicetak Oleh :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $printer }}</span></td>
                        </tr>
                        <tr>
                            <td class="doc-footer-label">Dicetak Tanggal :</td>
                            <td class="doc-footer-value"><span class="doc-underlined">{{ $printTimestamp }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Empty State --}}
    <div class="bds-paper-sheet" style="min-height: 350px; justify-content: center; align-items: center; text-align: center;">
        <div class="p-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">Laporan Output Tidak Ditemukan</h3>
            <p class="mt-1 text-xs text-gray-500">
                Tidak ada file data <code>BR_Report</code> untuk tanggal <strong>{{ $selectedDate }}</strong> di server MHS.
            </p>
            @if (! empty($availableDates))
                <div class="mt-4">
                    <p class="text-xs text-gray-600 mb-2">Tanggal laporan yang tersedia:</p>
                    <div class="flex flex-wrap justify-center gap-1.5">
                        @foreach ($availableDates as $ad)
                            <button type="button" wire:click="$set('selectedDate', '{{ $ad }}')"
                                class="px-2.5 py-1 text-xs rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium">
                                {{ $ad }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
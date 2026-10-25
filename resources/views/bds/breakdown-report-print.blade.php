<!DOCTYPE html>
<html lang="{{ $lang ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breakdown Report - {{ $startDate }} to {{ $endDate }}</title>
    
    @include('bds.partials.report-styles')

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .no-print-bar {
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .no-print-bar h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
        }

        .no-print-bar p {
            margin: 2px 0 0 0;
            font-size: 11px;
            color: #94a3b8;
        }

        .btn-action-group {
            display: flex;
            gap: 10px;
        }

        .btn-print-primary {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-close-gray {
            background-color: #334155;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { background: #ffffff !important; }
        }
    </style>
</head>
<body>

    @php
        $isId = ($lang ?? 'en') === 'id';
    @endphp

    <div class="no-print-bar">
        <div>
            <h2>Breakdown Report - {{ $isId ? 'Pratinjau Cetak' : 'Print Preview' }}</h2>
            <p>{{ $isId ? 'Periode' : 'Period' }}: {{ $startDate }} s.d. {{ $endDate }} | A4 Portrait</p>
        </div>
        <div class="btn-action-group">
            <button onclick="window.close()" class="btn-close-gray">{{ $isId ? 'Tutup Halaman' : 'Close Page' }}</button>
            <button onclick="window.print()" class="btn-print-primary">{{ $isId ? 'Cetak Dokumen (Ctrl+P)' : 'Print Document (Ctrl+P)' }}</button>
        </div>
    </div>

    <div class="bds-paper-canvas print-preview-canvas" style="padding-top: 24px; padding-bottom: 40px;">
        <div class="bds-paper-sheet">
            {{-- Header Top with BI Logo --}}
            <div class="doc-header-top">
                <img src="{{ $logoBase64 ?? asset('images/bank_indonesia_logo.png') }}" alt="Bank Indonesia Logo" class="doc-bi-logo">
            </div>

            <div class="doc-system-line">
                <div class="doc-system-label">{{ $isId ? 'Sistem Peracikan Uang Kertas :' : 'Banknote Compounding System :' }}</div>
                <div style="width: 35%; text-align: right; font-size: 11px;">
                    System ID: <strong>BDS</strong>
                </div>
            </div>

            <div class="doc-title-line">
                <div class="doc-main-title">{{ $isId ? 'Laporan Breakdown & Event Sistem' : 'System Breakdown & Event Report' }}</div>
                <div class="doc-page-counter">
                    <span>{{ $isId ? 'Halaman' : 'Page' }}</span>
                    <span>1/1</span>
                </div>
            </div>

            <div class="doc-divider-black"></div>

            <div style="margin: 10px 0; font-size: 12.5px; font-weight: 700;">
                Requested timeframe: <span style="font-weight: normal;">{{ $startDate }} to {{ $endDate }}</span>
            </div>

            <div class="doc-divider-black"></div>

            @php
                $service = app(\App\Services\Bds\BreakdownReportService::class);
            @endphp

            <div class="doc-table-card">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 35px;">No</th>
                            <th style="width: 85px;">{{ $isId ? 'Tanggal' : 'Date' }}</th>
                            <th style="width: 90px;">{{ $isId ? 'Unit / No' : 'Unit / No' }}</th>
                            <th style="width: 110px;">Event Start</th>
                            <th style="width: 110px;">Event Stop</th>
                            <th style="width: 75px;">{{ $isId ? 'Durasi' : 'Duration' }}</th>
                            <th>{{ $isId ? 'Detail Breakdown' : 'Breakdown Details' }}</th>
                            <th>{{ $isId ? 'Tindak Lanjut' : 'Follow-Up' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $index => $rep)
                            @php
                                $parsed = $service->parseDescription($isId ? ($rep->ind_description ?? '') : ($rep->eng_description ?? ''), $isId ? 'ind' : 'eng');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $rep->date }}</td>
                                <td class="text-bold">{{ $rep->event_unit }} ({{ $rep->event_number }})</td>
                                <td>{{ $rep->event_start }}</td>
                                <td>{{ $rep->event_stop }}</td>
                                <td class="text-center font-mono">{{ $rep->duration }}</td>
                                <td>{{ $parsed['details'] }}</td>
                                <td>{{ $parsed['follow_up'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 30px; color: #64748b;">
                                    {{ $isId ? 'Tidak ada data breakdown pada rentang tanggal tersebut.' : 'No breakdown data found for this date range.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="doc-sheet-bottom-section" style="margin-top: auto;">
                <div class="doc-divider-black"></div>

                <div class="eckelmann-subfooter-line" style="display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; margin-bottom: 8px;">
                    <div>{{ $startDate }} to {{ $endDate }}</div>
                    <div>Page 1 / 1</div>
                </div>

                <div class="doc-sheet-footer" style="padding-top: 2px;">
                    <div class="doc-sheet-footer-inner">
                        <table class="doc-footer-table">
                            <tr>
                                <td class="doc-footer-label">{{ $isId ? 'Dicetak Oleh :' : 'Printed By :' }}</td>
                                <td class="doc-footer-value"><span class="doc-underlined">{{ $printedByName ?? 'Administrator' }}</span></td>
                            </tr>
                            <tr>
                                <td class="doc-footer-label">{{ $isId ? 'Dicetak Tanggal :' : 'Printed Date :' }}</td>
                                <td class="doc-footer-value"><span class="doc-underlined">{{ $printTimestamp }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
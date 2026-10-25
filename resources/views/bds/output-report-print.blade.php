<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Output Report BDS SC 1000/2 - {{ $selectedDate }}</title>
    
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
            letter-spacing: 0.5px;
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
            transition: background-color 0.15s;
        }

        .btn-print-primary:hover {
            background-color: #1d4ed8;
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
            transition: background-color 0.15s;
        }

        .btn-close-gray:hover {
            background-color: #475569;
        }

        .print-preview-canvas {
            padding-top: 24px;
            padding-bottom: 40px;
        }

        .bds-paper-sheet {
            justify-content: flex-start !important;
        }

        .doc-sheet-bottom-section {
            margin-top: auto !important;
        }

        /* Eckelmann Inner Section */
        .eckelmann-container {
            margin: 16px 0;
            color: #000000;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .eckelmann-header-title {
            text-align: center;
            margin-bottom: 16px;
        }

        .eckelmann-header-title h3 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .eckelmann-divider-black {
            border: 0;
            border-top: 1.5px solid #000000 !important;
            margin: 6px 0 12px 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .eckelmann-timeframe-row {
            display: flex;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .eckelmann-timeframe-label {
            width: 250px;
        }

        .eckelmann-meta-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 12.5px;
        }

        .eckelmann-meta-label {
            width: 250px;
        }

        .eckelmann-press-block {
            margin-bottom: 14px;
        }

        .eckelmann-press-title {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .eckelmann-press-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 12.5px;
        }

        .eckelmann-press-label {
            width: 250px;
        }

        .eckelmann-total-header {
            font-weight: 700;
            font-size: 13px;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .eckelmann-subfooter-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            font-weight: 700;
            margin-top: 6px;
            margin-bottom: 14px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 12mm;
            }

            html, body {
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background-color: #ffffff !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .eckelmann-divider-black {
                border: 0 !important;
                border-top: 1.5px solid #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: block !important;
                width: 100% !important;
            }

            .print-preview-canvas,
            .bds-paper-canvas {
                padding: 0 !important;
                margin: 0 !important;
                background-color: #ffffff !important;
                box-shadow: none !important;
                height: 100% !important;
                width: 100% !important;
            }

            .bds-paper-sheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 4mm 6mm !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: calc(297mm - 16mm) !important;
                height: calc(297mm - 16mm) !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                box-sizing: border-box !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .doc-sheet-bottom-section {
                margin-top: auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .doc-sheet-footer {
                padding-top: 4px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <h2>BDS - Output Report</h2>
            <p>Tanggal Operasional: {{ $selectedDate }} | Format A4 Portrait</p>
        </div>
        <div class="btn-action-group">
            <button onclick="closeReportPage()" class="btn-close-gray">Tutup Halaman</button>
            <button onclick="window.print()" class="btn-print-primary">Cetak Dokumen (Ctrl+P)</button>
        </div>
    </div>

    <div class="bds-paper-canvas print-preview-canvas">
        @include('bds.partials.output-report-sheet')
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });

        function closeReportPage() {
            // Coba tutup jendela browser
            window.close();
            
            // Jika dalam waktu 200ms jendela tidak tertutup (karena diblokir browser), 
            // arahkan kembali ke halaman dashboard output-reports Filament
            setTimeout(function() {
                window.location.href = '/admin/output-reports';
            }, 200);
        }
    </script>
</body>
</html>

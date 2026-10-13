<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berkas Cetak BDS Bank Indonesia - {{ $formattedDateDot }}</title>
    
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

        @media print {
            .no-print-bar {
                display: none !important;
            }

            body {
                background-color: #ffffff !important;
            }

            .print-preview-canvas,
            .bds-paper-canvas {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <h2>BDS</h2>
            <p>Tanggal Operasional Report : {{ $formattedDateDot }} | Format A4</p>
        </div>
        <div class="btn-action-group">
            <button onclick="window.close()" class="btn-close-gray">Tutup Halaman</button>
            <button onclick="window.print()" class="btn-print-primary">Cetak Dokumen (Ctrl+P)</button>
        </div>
    </div>

    <div class="bds-paper-canvas print-preview-canvas">
        @include('bds.partials.report-sheets')
    </div>

</body>
</html>

<style>
    /* Authentic Paper Document Canvas */
    .bds-paper-canvas {
        background-color: #e2e8f0;
        padding: 30px 10px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        gap: 30px;
        align-items: center;
        overflow-x: auto;
        width: 100%;
        box-sizing: border-box;
    }

    .bds-paper-sheet {
        background-color: #ffffff;
        width: 100%;
        max-width: 1000px;
        min-width: 880px;
        min-height: 1120px;
        padding: 30px 36px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border-radius: 4px;
        position: relative;
        box-sizing: border-box;
        color: #000000;
        font-family: 'Segoe UI', Arial, sans-serif;
        display: flex;
        flex-direction: column;
    }

    /* Document Header matching Power BI */
    .doc-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .doc-bi-logo {
        height: 38px;
        object-fit: contain;
    }

    .doc-subnote {
        font-size: 11px;
        color: #000;
        text-align: right;
        line-height: 1.3;
    }

    .doc-system-line {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-top: 10px;
    }

    .doc-system-label {
        font-size: 13px;
        color: #000;
        width: 35%;
    }

    .doc-system-center {
        font-size: 14px;
        font-weight: 700;
        color: #000;
        text-align: center;
        width: 30%;
    }

    .doc-title-line {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 4px;
    }

    .doc-main-title {
        font-size: 16px;
        font-weight: 700;
        color: #000;
    }

    .doc-page-counter {
        display: flex;
        gap: 40px;
        font-size: 14px;
        font-weight: 700;
        color: #000;
    }

    .doc-divider-black {
        border-bottom: 2px solid #000;
        margin-top: 4px;
        margin-bottom: 8px;
    }

    .doc-meta-block {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: #000;
        line-height: 1.6;
    }

    .doc-meta-subtitle {
        font-weight: 700;
        font-size: 12.5px;
        margin-top: 2px;
    }

    /* Authentic Table Grid */
    .doc-table-card {
        border: 1px solid #d1d5db;
        border-radius: 4px;
        overflow-x: auto;
        margin-top: 14px;
        margin-bottom: 16px;
        background-color: #ffffff;
    }

    table.doc-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 11px;
        color: #000000;
    }

    table.doc-table thead th {
        border-top: 1.5px solid #000000;
        border-bottom: 1.5px solid #000000;
        padding: 5px 6px;
        font-weight: 700;
        color: #000000;
        background-color: #ffffff;
        vertical-align: middle;
        border-right: 1px solid #9ca3af;
    }

    table.doc-table thead th:last-child {
        border-right: none;
    }

    table.doc-table tbody td {
        padding: 4px 6px;
        border-bottom: 1px solid #e5e7eb;
        color: #000000;
        vertical-align: middle;
        border-right: 1px solid #d1d5db;
    }

    table.doc-table tbody td:last-child {
        border-right: none;
    }

    table.doc-table tfoot td,
    table.doc-table tr.subtotal-row td,
    table.doc-table tr.summary-total-row td {
        border-top: 1.5px solid #000000;
        border-bottom: 1.5px solid #000000;
        border-right: 1px solid #d1d5db;
        padding: 5px 6px;
        font-weight: 700;
        color: #000000;
        vertical-align: middle;
    }

    table.doc-table tfoot td:last-child,
    table.doc-table tr.subtotal-row td:last-child,
    table.doc-table tr.summary-total-row td:last-child {
        border-right: none;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: 700; }

    /* Signature & Responsibility Box for Certificate */
    .doc-signature-block {
        margin-top: 24px;
        margin-bottom: 16px;
    }

    .doc-signature-box {
        border: 1.5px solid #000000;
        width: 140px;
        height: 50px;
    }

    /* Document Footer on Every Sheet */
    .doc-sheet-footer {
        margin-top: auto;
        padding-top: 16px;
        display: flex;
        justify-content: flex-end;
        font-size: 11.5px;
        color: #000000;
        line-height: 1.5;
    }

    .doc-sheet-footer-inner {
        display: inline-block;
        text-align: left;
    }

    .doc-footer-table {
        border-collapse: collapse;
        font-size: 11.5px;
        color: #000000;
        width: auto;
    }

    .doc-footer-table td {
        padding: 1px 0;
        border: none !important;
        background: transparent !important;
        vertical-align: middle;
    }

    .doc-footer-label {
        text-align: left;
        white-space: nowrap;
        padding-right: 8px !important;
        width: 1%;
    }

    .doc-footer-value {
        text-align: left;
        white-space: nowrap;
    }

    /* Blue underline for Name and Date */
    .doc-underlined {
        text-decoration: none;
        border-bottom: 1.5px solid #1d4ed8;
        padding-bottom: 1px;
        font-weight: 600;
        display: inline-block;
    }

    /* Print Media Styles: Pure Document Output */
    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        .no-print, .bds-toolbar-card, .bds-sheet-nav, .fi-topbar, .fi-sidebar, .fi-header-actions, .fi-breadcrumbs {
            display: none !important;
        }

        body, .fi-main, .fi-page, .bds-screen-wrapper, .bds-paper-canvas {
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .bds-paper-sheet {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 2mm 4mm !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            min-height: calc(297mm - 12mm) !important;
            height: auto !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            page-break-after: always;
            break-after: page;
            box-sizing: border-box !important;
        }

        .bds-paper-sheet:last-child {
            page-break-after: avoid;
            break-after: avoid;
        }

        .doc-table-card {
            border: none !important;
            overflow: visible !important;
            margin-top: 4px !important;
            margin-bottom: 4px !important;
        }

        table.doc-table {
            border: 1px solid #000000 !important;
            font-size: 9.5px !important;
            line-height: 1.25 !important;
        }

        table.doc-table th {
            padding: 3px 4px !important;
        }

        table.doc-table td,
        table.doc-table tr.subtotal-row td,
        table.doc-table tfoot td,
        table.doc-table tr.summary-total-row td {
            padding: 2.5px 4px !important;
        }

        .doc-signature-block {
            margin-top: 8px !important;
            margin-bottom: 6px !important;
        }

        .doc-meta-block {
            font-size: 11px !important;
            line-height: 1.35 !important;
        }

        thead {
            display: table-header-group !important;
        }

        tfoot {
            display: table-footer-group !important;
        }

        tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .doc-sheet-footer {
            margin-top: auto !important;
            padding-top: 6px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .doc-footer-table {
            min-width: 0 !important;
            width: auto !important;
        }

        .doc-footer-label {
            text-align: left !important;
            padding-right: 8px !important;
            white-space: nowrap !important;
        }

        .doc-footer-value {
            text-align: left !important;
            white-space: nowrap !important;
        }
    }
</style>

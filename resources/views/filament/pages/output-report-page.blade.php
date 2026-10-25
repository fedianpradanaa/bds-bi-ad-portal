<x-filament-panels::page>
    @include('bds.partials.report-styles')
    <style>
        .bds-screen-wrapper {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
        }
        /* =========================================================
           FILTER TOOLBAR
           ========================================================= */
        .bds-toolbar-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
        }
        .bds-field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            margin-bottom: 6px;
        }
        .bds-field-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            color: #0f172a;
            background-color: #ffffff;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }
        .bds-field-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* =========================================================
           ECKELMANN REPORT
           ========================================================= */
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
            border-top: 1.5px solid #000000;
            margin: 6px 0 12px 0;
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
            margin-bottom: 16px;
        }
        .eckelmann-press-title {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 5px;
            font-size: 12.5px;
        }
        .eckelmann-press-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 12px;
        }
        .eckelmann-press-label {
            width: 250px;
        }
        .eckelmann-total-header {
            font-weight: 700;
            font-size: 13px;
            margin-top: 14px;
            margin-bottom: 2px;
        }
        .eckelmann-subfooter-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            font-weight: 700;
            margin-top: 6px;
            margin-bottom: 18px;
        }

        /* =========================================================
           PAPER
           ========================================================= */
        .bds-paper-sheet {
            justify-content: flex-start !important;
        }
        .doc-sheet-bottom-section {
            margin-top: auto !important;
        }

        /* =========================================================
           MODERN LOADING STATE
           ========================================================= */
        .bds-loading-state {
            width: 100%;
            animation: bds-loading-fade-in 0.25s ease-out;
        }
        .bds-loading-card {
            position: relative;
            min-height: 600px;
            padding: 42px 48px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.03),
                0 8px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        /*
         * Very subtle ambient light.
         * Gives the loading card a softer modern appearance.
         */
        .bds-loading-card::before {
            content: '';
            position: absolute;
            top: -180px;
            left: 50%;
            width: 500px;
            height: 300px;
            transform: translateX(-50%);
            background: radial-gradient(
                ellipse,
                rgba(37, 99, 235, 0.045) 0%,
                rgba(37, 99, 235, 0) 70%
            );
            pointer-events: none;
        }

        /* =========================================================
           SKELETON
           ========================================================= */
        .bds-skeleton {
            position: relative;
            overflow: hidden;
            background: #eef2f7;
            border-radius: 5px;
        }
        /*
         * Moving shimmer effect
         */
        .bds-skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.35) 35%,
                rgba(255, 255, 255, 0.85) 50%,
                rgba(255, 255, 255, 0.35) 65%,
                transparent 100%
            );
            animation: bds-shimmer 1.7s ease-in-out infinite;
        }

        /* =========================================================
           LOADING HEADER
           ========================================================= */
        .bds-loading-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .bds-skeleton-title {
            width: 180px;
            height: 18px;
            margin: 0 auto 10px;
            border-radius: 6px;
        }
        .bds-skeleton-subtitle {
            width: 120px;
            height: 9px;
            margin: auto;
            border-radius: 4px;
        }

        /* =========================================================
           LOADING DIVIDER
           ========================================================= */
        .bds-skeleton-divider {
            width: 100%;
            height: 1px;
            margin-bottom: 28px;
            border-radius: 0;
        }

        /* =========================================================
           LOADING META
           ========================================================= */
        .bds-loading-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-bottom: 34px;
        }
        .bds-meta-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .bds-skeleton-label {
            width: 75px;
            height: 8px;
            border-radius: 3px;
        }
        .bds-skeleton-value {
            width: 150px;
            height: 11px;
            border-radius: 4px;
        }
        .bds-skeleton-value.short {
            width: 110px;
        }

        /* =========================================================
           LOADING SECTION
           ========================================================= */
        .bds-loading-section {
            margin-bottom: 32px;
        }
        .bds-skeleton-section-title {
            width: 145px;
            height: 11px;
            margin-bottom: 16px;
            border-radius: 4px;
        }
        .bds-skeleton-section-title.small {
            width: 110px;
        }

        /* =========================================================
           LOADING TEXT LINES
           ========================================================= */
        .bds-loading-lines {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }
        .bds-line {
            width: 100%;
            height: 9px;
            border-radius: 4px;
        }
        .bds-line.medium {
            width: 72%;
        }

        /* =========================================================
           LOADING DETAIL GRID
           ========================================================= */
        .bds-loading-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .bds-grid-line {
            width: 100%;
            height: 8px;
            border-radius: 4px;
        }

        /* =========================================================
           LOADING BOTTOM
           ========================================================= */
        .bds-loading-bottom {
            margin-top: 42px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bds-bottom-line {
            width: 130px;
            height: 7px;
            border-radius: 3px;
        }
        .bds-bottom-line.short {
            width: 70px;
        }

        /* =========================================================
           LOADING INDICATOR
           ========================================================= */
        .bds-loading-indicator {
            position: absolute;
            left: 50%;
            bottom: 28px;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .bds-loading-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #64748b;
            animation: bds-loading-dot 1.2s ease-in-out infinite;
        }

        /* =========================================================
           ANIMATIONS
           ========================================================= */
        @keyframes bds-shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        @keyframes bds-loading-dot {
            0%,
            100% {
                opacity: 0.35;
                transform: scale(0.85);
            }
            50% {
                opacity: 1;
                transform: scale(1);
            }
        }
        @keyframes bds-loading-fade-in {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================================================
           PRINT
           ========================================================= */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 12mm;
            }
            .no-print,
            .bds-toolbar-card,
            .bds-loading-state,
            .fi-topbar,
            .fi-sidebar,
            .fi-header,
            .fi-breadcrumbs,
            aside,
            header,
            nav {
                display: none !important;
            }
            .bds-paper-canvas {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                width: 100% !important;
                height: 100% !important;
            }
            .bds-paper-sheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 4mm 6mm !important;
                max-width: 100% !important;
                width: 100% !important;
                min-height: calc(297mm - 16mm) !important;
                height: calc(297mm - 16mm) !important;
                display: flex !important;
                flex-direction: column !important;
                box-sizing: border-box !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .eckelmann-divider-black {
                border: 0 !important;
                border-top: 1.5px solid #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: block !important;
                width: 100% !important;
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

        /* =========================================================
           RESPONSIVE
           ========================================================= */
        @media (max-width: 768px) {
            .bds-loading-card {
                padding: 30px 24px;
            }
            .bds-loading-meta {
                grid-template-columns: 1fr;
                gap: 18px;
            }
            .bds-loading-indicator {
                bottom: 20px;
            }
        }
    </style>

    <div class="bds-screen-wrapper">
        {{-- =====================================================
             FILTER TOOLBAR
             ===================================================== --}}
        <div class="bds-toolbar-card no-print">
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
                align-items: flex-end;
            ">
               {{-- Select Date dengan Efek Biru saat Diklik --}}
                <div>
                    <label for="selectedDate" class="bds-field-label">
                        Select Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="selectedDate"
                        id="selectedDate"
                        class="bds-field-select"
                    >
                </div>

                {{-- Pemimpin Pelaksana --}}
                <div>
                    <label
                        for="selectedSupervisorId"
                        class="bds-field-label"
                    >
                        Pemimpin Pelaksana
                    </label>
                    <select
                        wire:model.live="selectedSupervisorId"
                        id="selectedSupervisorId"
                        class="bds-field-select"
                    >
                        @foreach($supervisors as $sup)
                            <option value="{{ $sup->id }}">
                                {{ $sup->name }}
                                (NIP:
                                {{ $sup->nip ?? $sup->username ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Pengawas --}}
                <div>
                    <label
                        for="selectedTeamLeaderId"
                        class="bds-field-label"
                    >
                        Pengawas
                    </label>
                    <select
                        wire:model.live="selectedTeamLeaderId"
                        id="selectedTeamLeaderId"
                        class="bds-field-select"
                    >
                        @foreach($teamLeaders as $tl)
                            <option value="{{ $tl->id }}">
                                {{ $tl->name }}
                                (NIP:
                                {{ $tl->nip ?? $tl->username ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- =====================================================
             REPORT CONTENT
             ===================================================== --}}
        <div class="relative">

            {{-- =================================================
                 MODERN SKELETON LOADING
                 ================================================= --}}
            <div
                wire:loading
                wire:target="selectedDate,selectedSupervisorId,selectedTeamLeaderId"
                class="bds-loading-state"
            >
                <div class="bds-loading-card">

                    {{-- Header --}}
                    <div class="bds-loading-header">
                        <div class="bds-skeleton bds-skeleton-title"></div>
                        <div class="bds-skeleton bds-skeleton-subtitle"></div>
                    </div>

                    {{-- Divider --}}
                    <div class="bds-skeleton bds-skeleton-divider"></div>

                    {{-- Metadata --}}
                    <div class="bds-loading-meta">
                        <div class="bds-meta-item">
                            <div class="bds-skeleton bds-skeleton-label"></div>
                            <div class="bds-skeleton bds-skeleton-value"></div>
                        </div>

                        <div class="bds-meta-item">
                            <div class="bds-skeleton bds-skeleton-label"></div>
                            <div class="bds-skeleton bds-skeleton-value short"></div>
                        </div>

                        <div class="bds-meta-item">
                            <div class="bds-skeleton bds-skeleton-label"></div>
                            <div class="bds-skeleton bds-skeleton-value"></div>
                        </div>
                    </div>

                    {{-- Main Content --}}
                    <div class="bds-loading-section">
                        <div class="bds-skeleton bds-skeleton-section-title"></div>
                        <div class="bds-loading-lines">
                            <div class="bds-skeleton bds-line"></div>
                            <div class="bds-skeleton bds-line"></div>
                            <div class="bds-skeleton bds-line medium"></div>
                            <div class="bds-skeleton bds-line"></div>
                        </div>
                    </div>

                    {{-- Detail --}}
                    <div class="bds-loading-section">
                        <div class="bds-skeleton bds-skeleton-section-title small"></div>
                        <div class="bds-loading-grid">
                            <div class="bds-skeleton bds-grid-line"></div>
                            <div class="bds-skeleton bds-grid-line"></div>
                            <div class="bds-skeleton bds-grid-line"></div>
                            <div class="bds-skeleton bds-grid-line"></div>
                        </div>
                    </div>

                    {{-- Bottom --}}
                    <div class="bds-loading-bottom">
                        <div class="bds-skeleton bds-bottom-line"></div>
                        <div class="bds-skeleton bds-bottom-line short"></div>
                    </div>

                    {{-- Loading Indicator --}}
                    <div class="bds-loading-indicator">
                        <span class="bds-loading-dot"></span>
                        <span>
                            Loading report...
                        </span>
                    </div>
                </div>
            </div>
            {{-- =================================================
                 ACTUAL REPORT
                 ================================================= --}}
            <div
                class="bds-paper-canvas transition-all duration-300"
                wire:loading.remove
                wire:target="selectedDate,selectedSupervisorId,selectedTeamLeaderId"
            >
                @include('bds.partials.output-report-sheet')
            </div>
        </div>
    </div>
</x-filament-panels::page>
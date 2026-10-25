<x-filament-panels::page>
    @include('bds.partials.report-styles')

    <style>
        .bds-screen-wrapper {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
        }

        /* Filter Control Bar */
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
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .bds-field-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* Warna dan Efek Tombol disamakan dengan Fetch Latest Data (Ungu/Indigo Filament Action) */
        .bds-btn-primary {
            height: 38px;
            padding: 0 20px;
            background-color: #6366f1; /* Warna indigo/ungu ala button Fetch Latest Data */
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(99, 102, 241, 0.2);
            transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
        }

        .bds-btn-primary:hover {
            background-color: #4f46e5; /* Ungu lebih gelap saat hover */
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.25), 0 2px 4px -1px rgba(99, 102, 241, 0.1);
        }

        .bds-btn-primary:active, 
        .bds-btn-primary:focus {
            background-color: #4338ca !important;
            color: #ffffff !important;
            transform: translateY(1px);
            outline: none;
        }

        /* Spinner Animation ala Filament */
        .bds-spinner {
            animation: bds-spin 1s linear infinite;
        }

        @keyframes bds-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* SKELETON STYLES */
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
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03), 0 8px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .bds-skeleton {
            position: relative;
            overflow: hidden;
            background: #eef2f7;
            border-radius: 5px;
        }

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

        .bds-loading-header { text-align: center; margin-bottom: 24px; }
        .bds-skeleton-title { width: 180px; height: 18px; margin: 0 auto 10px; border-radius: 6px; }
        .bds-skeleton-subtitle { width: 120px; height: 9px; margin: auto; border-radius: 4px; }
        .bds-skeleton-divider { width: 100%; height: 1px; margin-bottom: 28px; border-radius: 0; }

        .bds-loading-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
            margin-bottom: 34px;
        }
        .bds-meta-item { display: flex; flex-direction: column; gap: 8px; }
        .bds-skeleton-label { width: 75px; height: 8px; border-radius: 3px; }
        .bds-skeleton-value { width: 150px; height: 11px; border-radius: 4px; }

        .bds-loading-section { margin-bottom: 32px; }
        .bds-skeleton-section-title { width: 145px; height: 11px; margin-bottom: 16px; border-radius: 4px; }
        .bds-loading-lines { display: flex; flex-direction: column; gap: 11px; }
        .bds-line { width: 100%; height: 9px; border-radius: 4px; }
        .bds-line.medium { width: 72%; }

        .bds-loading-bottom {
            margin-top: 42px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bds-bottom-line { width: 130px; height: 7px; border-radius: 3px; }
        .bds-bottom-line.short { width: 70px; }

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

        .bds-initial-state {
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
        }

        @keyframes bds-shimmer {
            100% { transform: translateX(100%); }
        }
        @keyframes bds-loading-dot {
            0%, 100% { opacity: 0.35; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1); }
        }
        @keyframes bds-loading-fade-in {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="bds-screen-wrapper">
        {{-- Toolbar Filter: Rentang Tanggal & Tombol Show Report --}}
        <div class="bds-toolbar-card">
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="tempStartDate" class="bds-field-label">From Date</label>
                    <input type="date" wire:model="tempStartDate" id="tempStartDate" max="{{ date('Y-m-d') }}" class="bds-field-select">
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="tempEndDate" class="bds-field-label">To Date</label>
                    <input type="date" wire:model="tempEndDate" id="tempEndDate" max="{{ date('Y-m-d') }}" class="bds-field-select">
                </div>

                <div>
                    <button type="button" wire:click="applyFilter" wire:loading.attr="disabled" class="bds-btn-primary">
                        {{-- Icon Kaca Pembesar saat Normal --}}
                        <svg wire:loading.remove wire:target="applyFilter" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        
                        {{-- Icon Spinner saat Loading --}}
                        <svg wire:loading wire:target="applyFilter" class="bds-spinner" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span>Show Report</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Area Konten Laporan & Skeleton Loading Universal --}}
        <div>
            {{-- Skeleton Loading ini sekarang AKTIF meskipun diklik pertama kali saat $isFiltered masih false --}}
            <div wire:loading wire:target="applyFilter" class="bds-loading-state">
                <div class="bds-loading-card">
                    <div class="bds-loading-header">
                        <div class="bds-skeleton bds-skeleton-title"></div>
                        <div class="bds-skeleton bds-skeleton-subtitle"></div>
                    </div>
                    <div class="bds-skeleton bds-skeleton-divider"></div>
                    
                    <div class="bds-loading-meta">
                        <div class="bds-meta-item">
                            <div class="bds-skeleton bds-skeleton-label"></div>
                            <div class="bds-skeleton bds-skeleton-value"></div>
                        </div>
                        <div class="bds-meta-item">
                            <div class="bds-skeleton bds-skeleton-label"></div>
                            <div class="bds-skeleton bds-skeleton-value"></div>
                        </div>
                    </div>

                    <div class="bds-loading-section">
                        <div class="bds-skeleton bds-skeleton-section-title"></div>
                        <div class="bds-loading-lines">
                            <div class="bds-skeleton bds-line"></div>
                            <div class="bds-skeleton bds-line"></div>
                            <div class="bds-skeleton bds-line medium"></div>
                        </div>
                    </div>

                    <div class="bds-loading-bottom">
                        <div class="bds-skeleton bds-bottom-line"></div>
                        <div class="bds-skeleton bds-bottom-line short"></div>
                    </div>

                    <div class="bds-loading-indicator">
                        <span class="bds-loading-dot"></span>
                        <span>Loading breakdown report...</span>
                    </div>
                </div>
            </div>

            {{-- Tampilan Normal / Hasil Laporan --}}
            <div wire:loading.remove wire:target="applyFilter">
                @if(!$isFiltered)
                    <div class="bds-initial-state">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin: 0 auto 16px; color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">No Report Displayed Yet</h3>
                        <p style="font-size: 13px; color: #64748b; max-width: 400px; margin: 0 auto;">Please select the date range above and click the **Show Report** button to load the data.</p>
                    </div>
                @else
                    {{-- Skeleton saat ganti bahasa (reportLang) tetap dipertahankan di sini --}}
                    <div wire:loading wire:target="reportLang" class="bds-loading-state">
                        <div class="bds-loading-card">
                            <div class="bds-loading-header">
                                <div class="bds-skeleton bds-skeleton-title"></div>
                                <div class="bds-skeleton bds-skeleton-subtitle"></div>
                            </div>
                            <div class="bds-skeleton bds-skeleton-divider"></div>
                            <div class="bds-loading-indicator">
                                <span class="bds-loading-dot"></span>
                                <span>Switching language...</span>
                            </div>
                        </div>
                    </div>

                    <div class="bds-paper-canvas transition-all duration-300" wire:loading.remove wire:target="reportLang">
                        @include('bds.partials.breakdown-report-sheet', $this->getReportServiceData())
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
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

        /* Two-Tier Segmented Tabs / Sub-Navigation */
        .bds-two-tier-nav {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .bds-tier1-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 12px;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bds-tier1-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .bds-tier1-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            background-color: transparent;
            border: 1px solid transparent;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }

        .bds-tier1-tab:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .bds-tier1-tab.active {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
        }

        .bds-tier1-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 9999px;
            background-color: #e2e8f0;
            color: #64748b;
            transition: all 0.15s ease-in-out;
        }

        .bds-tier1-tab.active .bds-tier1-badge {
            background-color: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .bds-tier1-tab-all {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
        }

        .bds-tier1-tab-all:hover {
            background-color: #e2e8f0;
            border-color: #94a3b8;
        }

        .bds-tier1-tab-all.active {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .bds-tier2-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background-color: #ffffff;
            flex-wrap: wrap;
            min-height: 52px;
        }

        .bds-tier2-context {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-right: 4px;
        }

        .bds-tier2-context-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
        }

        .bds-tier2-items {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            flex: 1;
        }

        .bds-tier2-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }

        .bds-tier2-btn:hover {
            background-color: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .bds-tier2-btn.active {
            background-color: #1e293b;
            color: #ffffff;
            border-color: #1e293b;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(30, 41, 59, 0.2);
        }

        /* Modern Skeleton Loading Styles */
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

        .bds-loading-card::before {
            content: '';
            position: absolute;
            top: -180px;
            left: 50%;
            width: 500px;
            height: 300px;
            transform: translateX(-50%);
            background: radial-gradient(ellipse, rgba(37, 99, 235, 0.045) 0%, rgba(37, 99, 235, 0) 70%);
            pointer-events: none;
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
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.35) 35%, rgba(255, 255, 255, 0.85) 50%, rgba(255, 255, 255, 0.35) 65%, transparent 100%);
            animation: bds-shimmer 1.7s ease-in-out infinite;
        }

        .bds-loading-header { text-align: center; margin-bottom: 24px; }
        .bds-skeleton-title { width: 180px; height: 18px; margin: 0 auto 10px; border-radius: 6px; }
        .bds-skeleton-subtitle { width: 120px; height: 9px; margin: auto; border-radius: 4px; }
        .bds-skeleton-divider { width: 100%; height: 1px; margin-bottom: 28px; }

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
        {{-- Toolbar Filter: Date, Supervisor, Team Leader --}}
        <div class="bds-toolbar-card">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; align-items: flex-end;">
                <div>
                    <label for="selectedDate" class="bds-field-label">Select Date</label>
                    @if (! empty($this->availableDates))
                        <select wire:model.live="selectedDate" id="selectedDate" class="bds-field-select">
                            @foreach($this->availableDates as $ad)
                                <option value="{{ $ad }}">
                                    {{ \Carbon\Carbon::parse($ad)->format('d/m/Y') }} ({{ $ad }})
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="date" wire:model.live="selectedDate" id="selectedDate" class="bds-field-select">
                    @endif
                </div>

                <div>
                    <label for="selectedSupervisorId" class="bds-field-label">Pemimpin Pelaksana</label>
                    <select wire:model.live="selectedSupervisorId" id="selectedSupervisorId" class="bds-field-select">
                        @foreach($this->supervisors as $sup)
                            <option value="{{ $sup->id }}">
                                {{ $sup->name }} (NIP: {{ $sup->nip ?? $sup->username ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="selectedTeamLeaderId" class="bds-field-label">Pengawas</label>
                    <select wire:model.live="selectedTeamLeaderId" id="selectedTeamLeaderId" class="bds-field-select">
                        @foreach($this->teamLeaders as $tl)
                            <option value="{{ $tl->id }}">
                                {{ $tl->name }} (NIP: {{ $tl->nip ?? $tl->username ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Two-Tier Segmented Tabs / Sub-Navigation (Sinkron 8 Report Python) --}}
        <div class="bds-two-tier-nav">
            <div class="bds-tier1-bar">
                <div class="bds-tier1-group">
                    <button type="button" wire:click="setReport('report1')" class="bds-tier1-tab {{ in_array($activeReport, ['report1', 'report2']) ? 'active' : '' }}">
                        <span>Transfer Unit</span> <span class="bds-tier1-badge">2</span>
                    </button>
                    <button type="button" wire:click="setReport('report3')" class="bds-tier1-tab {{ in_array($activeReport, ['report3', 'report4', 'report5']) ? 'active' : '' }}">
                        <span>Depalletizer (Robotic)</span> <span class="bds-tier1-badge">3</span>
                    </button>
                    <button type="button" wire:click="setReport('report6')" class="bds-tier1-tab {{ $activeReport === 'report6' ? 'active' : '' }}">
                        <span>Feeder Unit</span> <span class="bds-tier1-badge">1</span>
                    </button>
                    <button type="button" wire:click="setReport('report7')" class="bds-tier1-tab {{ $activeReport === 'report7' ? 'active' : '' }}">
                        <span>Shredder Unit</span> <span class="bds-tier1-badge">1</span>
                    </button>
                    <button type="button" wire:click="setReport('report8')" class="bds-tier1-tab {{ $activeReport === 'report8' ? 'active' : '' }}">
                        <span>Certificate</span> <span class="bds-tier1-badge">3</span>
                    </button>
                </div>

                <div class="bds-tier1-all">
                    <button type="button" wire:click="setReport('all')" class="bds-tier1-tab bds-tier1-tab-all {{ $activeReport === 'all' ? 'active' : '' }}">
                        <span>All Reports (1-8)</span> <span class="bds-tier1-badge">Full</span>
                    </button>
                </div>
            </div>

            <div class="bds-tier2-bar">
                <div class="bds-tier2-context"><span class="bds-tier2-context-label">Reports:</span></div>
                <div class="bds-tier2-items">
                    <button type="button" wire:click="setReport('report1')" class="bds-tier2-btn {{ $activeReport === 'report1' ? 'active' : '' }}">1. Transfer Unit Per Batch</button>
                    <button type="button" wire:click="setReport('report2')" class="bds-tier2-btn {{ $activeReport === 'report2' ? 'active' : '' }}">2. Transfer Unit Per Pallet</button>
                    <span style="color: #cbd5e1; margin: 0 2px;">|</span>
                    <button type="button" wire:click="setReport('report3')" class="bds-tier2-btn {{ $activeReport === 'report3' ? 'active' : '' }}">3. Robotic Arm PerBatch</button>
                    <button type="button" wire:click="setReport('report4')" class="bds-tier2-btn {{ $activeReport === 'report4' ? 'active' : '' }}">4. Robotic Arm Per Pallet</button>
                    <button type="button" wire:click="setReport('report5')" class="bds-tier2-btn {{ $activeReport === 'report5' ? 'active' : '' }}">5. Robotic Arm Per Tray</button>
                    <span style="color: #cbd5e1; margin: 0 2px;">|</span>
                    <button type="button" wire:click="setReport('report6')" class="bds-tier2-btn {{ $activeReport === 'report6' ? 'active' : '' }}">6. Feeder Unit Per Tray</button>
                    <span style="color: #cbd5e1; margin: 0 2px;">|</span>
                    <button type="button" wire:click="setReport('report7')" class="bds-tier2-btn {{ $activeReport === 'report7' ? 'active' : '' }}">7. Shredder Unit Per Batch</button>
                    <span style="color: #cbd5e1; margin: 0 2px;">|</span>
                    <button type="button" wire:click="setReport('report8')" class="bds-tier2-btn {{ $activeReport === 'report8' ? 'active' : '' }}">8. Shredder Unit Certificate</button>
                </div>
            </div>
        </div>

        {{-- Paper Document Canvas & Skeleton Loading Integration --}}
        <div class="relative">
            {{-- Skeleton Loading State --}}
            <div
                wire:loading
                wire:target="selectedDate, selectedSupervisorId, selectedTeamLeaderId, setReport"
                class="bds-loading-state"
            >
                <div class="bds-loading-card">
                    <div class="bds-loading-header">
                        <div class="bds-skeleton bds-skeleton-title"></div>
                        <div class="bds-skeleton bds-skeleton-subtitle"></div>
                    </div>
                    <div class="bds-skeleton bds-skeleton-divider"></div>
                    <div class="bds-loading-indicator">
                        <span class="bds-loading-dot"></span>
                        <span>Loading report...</span>
                    </div>
                </div>
            </div>

            {{-- Actual Report Content --}}
            <div
                class="bds-paper-canvas transition-all duration-300"
                wire:loading.remove
                wire:target="selectedDate, selectedSupervisorId, selectedTeamLeaderId, setReport"
            >
                @include('bds.partials.report-sheets', $this->getReportServiceData())
            </div>
        </div>
    </div>
</x-filament-panels::page>
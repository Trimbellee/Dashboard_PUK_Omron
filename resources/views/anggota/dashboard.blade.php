{{-- views/anggota/dashboard.blade.php --}}
@extends('layouts.app')
@section('title', 'Dashboard Anggota')

@section('content')

<style>
/* ── DASHBOARD RESPONSIVE ────────────────────────────────────── */
:root {
    --dash-radius: 16px;
    --dash-radius-sm: 10px;
    --dash-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    --dash-shadow-hover: 0 4px 24px rgba(0,0,0,0.08);
    --dash-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.dash-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--dash-radius);
    padding: 20px 22px;
    box-shadow: var(--dash-shadow);
    transition: var(--dash-transition);
}

.dash-card:hover {
    box-shadow: var(--dash-shadow-hover);
}

.dash-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.dash-card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-card-title .icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: var(--accent-muted);
    color: var(--accent-text);
    flex-shrink: 0;
}

#statGrid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.stat-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: var(--dash-radius-sm);
    padding: 18px 20px 16px;
    transition: var(--dash-transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-hover);
}

.stat-card .label {
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
    margin-bottom: 6px;
}

.stat-card .value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.stat-card .sub {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-card .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

#distribusiRow {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.chart-wrapper {
    position: relative;
    width: 100%;
    height: 180px;
}

.chart-wrapper-lg {
    position: relative;
    width: 100%;
    height: 300px;
}

.chart-wrapper-md {
    position: relative;
    width: 100%;
    height: 240px;
}

#trendStats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
}

#movementStats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.tren-legend {
    display: flex;
    gap: 14px;
    font-size: 12px;
    color: var(--text-secondary);
    flex-wrap: wrap;
}

.tren-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.tren-legend-item .swatch {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

.tren-legend-item .line-swatch {
    display: flex;
    align-items: center;
    gap: 2px;
}
.tren-legend-item .line-swatch .line {
    width: 16px;
    height: 2px;
    border-radius: 1px;
    flex-shrink: 0;
}
.tren-legend-item .line-swatch .dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-left: -2px;
}

/* Mode toggle */
.mode-toggle {
    display: flex;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    font-size: 12px;
}

.mode-toggle button {
    padding: 5px 14px;
    border: none;
    cursor: pointer;
    transition: var(--dash-transition);
    font-family: inherit;
    background: transparent;
    color: var(--text-secondary);
    font-weight: 500;
}

.mode-toggle button.active {
    background: var(--accent-muted);
    color: var(--accent-text);
    font-weight: 600;
}

.mode-toggle button:hover:not(.active) {
    background: var(--bg-elevated);
}

/* Range selects */
.range-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    flex-wrap: wrap;
}

.range-controls select {
    font-size: 12px;
    padding: 4px 24px 4px 10px;
    border-radius: 7px;
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-primary);
    cursor: pointer;
    font-family: inherit;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 6px center;
    background-size: 12px;
}

.range-controls .sep {
    color: var(--text-muted);
}

.umur-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.umur-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 6px 14px;
    border-radius: 10px;
    min-width: 56px;
}

.umur-pill .pill-value {
    font-size: 16px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.umur-pill .pill-label {
    font-size: 10.5px;
    color: var(--text-muted);
    margin-top: 1px;
}

/* Detail modal table */
#detailThead th {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-muted);
    text-align: left;
    white-space: nowrap;
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--border);
}

#detailTbody td {
    padding: 11px 14px;
    color: var(--text-secondary);
    white-space: nowrap;
    border-bottom: 1px solid var(--border);
}

#detailTbody tr:last-child td {
    border-bottom: none;
}

#detailTbody tr:hover td {
    background: var(--bg-elevated);
}

.detail-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 500;
}

.detail-badge-masuk {
    background: rgba(22,163,74,0.1);
    color: #16A34A;
}

.detail-badge-keluar {
    background: rgba(220,38,38,0.1);
    color: #DC2626;
}

/* Pager */
#detailPager {
    display: flex;
    align-items: center;
    gap: 4px;
}

#detailPager button {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-secondary);
    font-size: 13px;
    cursor: pointer;
    transition: var(--dash-transition);
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

#detailPager button:hover:not(:disabled) {
    background: var(--bg-elevated);
    color: var(--text-primary);
}

#detailPager button.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}

#detailPager button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

/* Toast */
#deleteToast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    background: #16A34A;
    color: #fff;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: none;
    align-items: center;
    gap: 10px;
    max-width: 380px;
    animation: slideUp 0.3s ease both;
}

#deleteToast.error {
    background: #DC2626;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── RESPONSIVE ────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    #statGrid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dash-card {
        padding: 16px 16px;
    }

    #statGrid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .stat-card {
        padding: 14px 16px;
    }

    .stat-card .value {
        font-size: 22px;
    }

    #distribusiRow {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .chart-wrapper {
        height: 160px;
    }
    .chart-wrapper-lg {
        height: 240px;
    }
    .chart-wrapper-md {
        height: 200px;
    }

    #trendStats {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    #movementStats {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    #trendStats > div .value,
    #movementStats > div .value {
        font-size: 18px !important;
    }

    .tren-legend {
        gap: 8px;
        font-size: 11px;
    }

    .range-controls {
        font-size: 11px;
    }

    .range-controls select {
        font-size: 11px;
        padding: 3px 20px 3px 8px;
    }

    #dashHeader {
        flex-direction: column;
        align-items: flex-start !important;
    }

    #dashHeaderButtons {
        width: 100%;
        flex-wrap: wrap;
    }

    #dashHeaderButtons button,
    #dashHeaderButtons a {
        flex: 1;
        justify-content: center;
        font-size: 12px !important;
        padding: 7px 10px !important;
    }

    .umur-pills {
        gap: 6px;
    }
    .umur-pill {
        padding: 4px 10px;
        min-width: 44px;
    }
    .umur-pill .pill-value {
        font-size: 14px;
    }

    #modalTotalKaryawan {
        padding: 40px 12px 12px !important;
    }

    #detailModalBox {
        border-radius: 14px !important;
        max-height: 92vh !important;
    }

    #detailTable {
        font-size: 12px;
        min-width: 500px;
    }
    #detailTbody td {
        padding: 8px 10px;
    }
    #detailThead th {
        padding: 8px 10px;
        font-size: 10px;
    }
}

@media (max-width: 480px) {
    #statGrid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .stat-card {
        padding: 12px 14px;
    }

    .stat-card .value {
        font-size: 20px;
    }

    .stat-card .label {
        font-size: 10px;
    }

    #trendStats {
        grid-template-columns: 1fr 1fr;
    }

    #movementStats {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .dash-card-title {
        font-size: 13px;
    }
}

/* Scrollbar styling */
#detailModalBox::-webkit-scrollbar,
.dash-card::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}

#detailModalBox::-webkit-scrollbar-track,
.dash-card::-webkit-scrollbar-track {
    background: transparent;
}

#detailModalBox::-webkit-scrollbar-thumb,
.dash-card::-webkit-scrollbar-thumb {
    background: var(--border-strong);
    border-radius: 4px;
}

/* Spinner */
.spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

.spinner-dark {
    border: 2px solid var(--border-strong);
    border-top-color: var(--accent);
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
const PHP = {
    tren:           @json($tren),
    distribusi:     @json($distribusi),
    departemen:     @json($departemen),
    masaKerja:      @json($masaKerja),
    distribusiUmur: @json($distribusiUmur),
    summary:        @json($summary),
    tahunList:      @json($tahunTersedia),
    tahunAktif:     @json($tahunAktif),
    trenUrl:        "{{ route('anggota.dashboard.tren') }}",
    csrfToken:      "{{ csrf_token() }}",
};
</script>

<div id="dash" style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:20px;animation:fadeUp 0.35s ease both;">

    {{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
    <div id="dashHeader" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:20px;font-weight:700;color:var(--text-primary);margin:0 0 2px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span>📊 Dashboard Anggota</span>
                <span style="font-size:12px;font-weight:400;color:var(--text-muted);background:var(--bg-elevated);padding:2px 12px;border-radius:12px;border:1px solid var(--border);">
                    {{ now()->translatedFormat('d F Y') }}
                </span>
            </h1>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">
                Ringkasan data keanggotaan secara real-time
            </p>
        </div>
        <div id="dashHeaderButtons" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <button onclick="openTotalKaryawanModal()"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
                           border-radius:10px;border:1px solid var(--border);background:var(--bg-surface);
                           color:var(--text-secondary);font-size:13px;font-weight:500;cursor:pointer;
                           transition:var(--dash-transition);font-family:inherit;"
                    onmouseover="this.style.background='var(--bg-elevated)';this.style.borderColor='var(--accent)'"
                    onmouseout="this.style.background='var(--bg-surface)';this.style.borderColor='var(--border)'">
                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Input Total Karyawan
            </button>
            <a href="{{ route('anggota.index') }}" class="btn btn-ghost" style="text-decoration:none;gap:6px;font-size:13px;padding:8px 16px;border-radius:10px;">
                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Lihat Tabel Anggota
            </a>
        </div>
    </div>

    {{-- ── STAT CARDS ───────────────────────────────────────────── --}}
    <div id="statGrid"></div>

    {{-- ── TREN ANGGOTA ─────────────────────────────────────────── --}}
    <div class="dash-card">

        {{-- Header --}}
        <div class="dash-card-header">
            <div class="dash-card-title">
                <span class="icon">📈</span>
                <span id="trenTitle">Tren anggota per bulan</span>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

                {{-- Legend --}}
                <div class="tren-legend">
                    <span class="tren-legend-item">
                        <span class="swatch" style="background:#3d9a5e;"></span>Anggota Aktif
                    </span>
                    <span class="tren-legend-item">
                        <span class="swatch" style="background:#94a3b8;"></span>Total Karyawan
                    </span>
                    <span class="tren-legend-item">
                        <span class="line-swatch">
                            <span class="line" style="background:#f59e0b;"></span>
                            <span class="dot" style="background:#f59e0b;"></span>
                        </span>
                        Rasio (%)
                    </span>
                </div>

                {{-- Mode toggle --}}
                <div class="mode-toggle">
                    <button id="btnModeMonth" onclick="setTrenMode('month')" class="active">Per Bulan</button>
                    <button id="btnModeYear" onclick="setTrenMode('year')">Per Tahun</button>
                </div>

                {{-- Range — month mode --}}
                <div id="trenRangeMonth" class="range-controls">
                    <select id="trenMonthYear" onchange="applyMonthRange()"></select>
                    <span class="sep">Bulan</span>
                    <select id="trenMonthFrom" onchange="applyMonthRange()"></select>
                    <span class="sep">–</span>
                    <select id="trenMonthTo" onchange="applyMonthRange()"></select>
                </div>

                {{-- Range — year mode --}}
                <div id="trenRangeYear" class="range-controls" style="display:none;">
                    <span class="sep">Tahun</span>
                    <select id="trenYearFrom" onchange="applyYearRange()"></select>
                    <span class="sep">–</span>
                    <select id="trenYearTo" onchange="applyYearRange()"></select>
                </div>

            </div>
        </div>

        {{-- Loading --}}
        <div id="trendLoading" style="display:none;text-align:center;padding:60px 0;font-size:13px;color:var(--text-muted);">
            <div class="spinner spinner-dark" style="margin:0 auto 12px;"></div>
            Memuat data...
        </div>

        {{-- Chart utama --}}
        <div class="chart-wrapper-lg">
            <canvas id="trendChart" role="img" aria-label="Grafik tren anggota aktif, total karyawan, dan rasio">
                Data tren anggota.
            </canvas>
        </div>

        {{-- Info: bulan tanpa data total karyawan --}}
        <div id="trendNoDataNote"
             style="display:none;margin-top:12px;padding:10px 16px;border-radius:10px;
                    background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);
                    font-size:12px;color:#92400E;align-items:center;gap:8px;">
            <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
            </svg>
            <span id="trendNoDataText"></span>
        </div>

        {{-- Summary bar --}}
        <div id="trendStats"></div>

        {{-- ── PERGERAKAN ANGGOTA ───────────────────────────────── --}}
        <div style="margin-top:20px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--dash-radius-sm);padding:18px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                <span style="font-size:13.5px;font-weight:600;color:var(--text-primary);">🔄 Pergerakan anggota</span>
                <div style="display:flex;gap:14px;font-size:12px;color:var(--text-secondary);">
                    <span style="display:flex;align-items:center;gap:5px;">
                        <span style="width:10px;height:10px;border-radius:3px;background:#378add;flex-shrink:0;"></span>Masuk
                    </span>
                    <span style="display:flex;align-items:center;gap:5px;">
                        <span style="width:10px;height:10px;border-radius:3px;background:#e24b4a;flex-shrink:0;"></span>Keluar
                    </span>
                </div>
            </div>
            <div class="chart-wrapper-md">
                <canvas id="movementChart"></canvas>
            </div>
            <div id="movementStats"></div>
        </div>

    </div>

    {{-- ── DISTRIBUSI ROW ───────────────────────────────────────── --}}
    <div id="distribusiRow">

        <div class="dash-card">
            <div class="dash-card-title" style="margin-bottom:14px;">
                <span class="icon">👤</span> Jenis kelamin
            </div>
            <div class="chart-wrapper">
                <canvas id="genderChart" role="img" aria-label="Distribusi jenis kelamin">Data jenis kelamin.</canvas>
            </div>
            <div id="genderLegend" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:12px;font-size:12px;color:var(--text-secondary);"></div>
        </div>

        <div class="dash-card">
            <div class="dash-card-title" style="margin-bottom:14px;">
                <span class="icon">💼</span> Status karyawan
            </div>
            <div class="chart-wrapper">
                <canvas id="statusChart" role="img" aria-label="Distribusi status karyawan">Data status karyawan.</canvas>
            </div>
            <div id="statusLegend" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:12px;font-size:12px;color:var(--text-secondary);"></div>
        </div>

        <div class="dash-card">
            <div class="dash-card-title" style="margin-bottom:14px;">
                <span class="icon">🎓</span> Pendidikan terakhir
            </div>
            <div class="chart-wrapper">
                <canvas id="eduChart" role="img" aria-label="Distribusi pendidikan terakhir">Data pendidikan terakhir.</canvas>
            </div>
            <div id="eduLegend" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:12px;font-size:12px;color:var(--text-secondary);"></div>
        </div>

    </div>

    {{-- ── DEPARTEMEN ───────────────────────────────────────────── --}}
    <div class="dash-card">

        <div class="dash-card-header">
            <div class="dash-card-title">
                <span class="icon">🏢</span> Top departemen
            </div>
            <button id="deptToggleBtn"
                    onclick="toggleDepartemen()"
                    style="display:inline-flex;align-items:center;gap:6px;
                           padding:5px 14px;border-radius:8px;border:1px solid var(--border);
                           background:transparent;color:var(--text-secondary);font-size:12px;
                           cursor:pointer;transition:var(--dash-transition);font-family:inherit;"
                    onmouseover="this.style.background='var(--bg-elevated)'"
                    onmouseout="this.style.background='transparent'">
                <svg id="deptToggleIcon"
                     style="width:13px;height:13px;transition:transform 0.25s;"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
                <span id="deptToggleLabel">Lihat Selengkapnya</span>
            </button>
        </div>

        <div id="deptLoading"
             style="display:none;text-align:center;padding:32px 0;font-size:13px;color:var(--text-muted);">
            <div class="spinner spinner-dark" style="margin:0 auto 10px;"></div>
            Memuat data...
        </div>

        <div style="position:relative;width:100%;" id="deptWrap"></div>

    </div>

    {{-- ── DISTRIBUSI UMUR ─────────────────────────────────────── --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title" style="margin-bottom:3px;">
                    <span class="icon">🎂</span> Distribusi umur anggota
                </div>
                <div style="font-size:12px;color:var(--text-muted);">
                    berdasarkan tanggal lahir ·
                    @if($distribusiUmur['tidakDiketahui'] > 0)
                        <span style="color:#ba7517;">{{ number_format($distribusiUmur['tidakDiketahui']) }} anggota tidak memiliki data tanggal lahir</span>
                    @else
                        semua anggota memiliki data tanggal lahir
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <div id="umurStats" class="umur-pills"></div>
                <select id="umurModeSelect" onchange="setUmurMode(parseInt(this.value))"
                        style="font-size:12px;padding:5px 10px;border-radius:8px;border:1px solid var(--border);
                            background:var(--bg-surface);color:var(--text-primary);cursor:pointer;
                            font-family:inherit;outline:none;">
                    <option value="5">Rentang 5 tahun</option>
                    <option value="2">Rentang 2 tahun</option>
                    <option value="1">Rentang 1 tahun</option>
                </select>
            </div>
        </div>
        <div id="umurChartWrap" class="chart-wrapper-md">
            <div id="umurChartScroll" style="position:relative;width:100%;height:100%;">
                <canvas id="umurChart" role="img" aria-label="Distribusi umur anggota aktif">Data distribusi umur anggota.</canvas>
            </div>
        </div>
        <div id="umurFooter" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;color:var(--text-muted);flex-wrap:wrap;"></div>
    </div>

    {{-- ── MASA KERJA ───────────────────────────────────────────── --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title" style="margin-bottom:3px;">
                    <span class="icon">⏳</span> Distribusi masa kerja
                </div>
                <div style="font-size:12px;color:var(--text-muted);">berdasarkan tanggal mulai bekerja</div>
            </div>
            <select id="masaKerjaModeSelect" onchange="setMasaKerjaMode(parseInt(this.value))"
                    style="font-size:12px;padding:5px 10px;border-radius:8px;border:1px solid var(--border);
                        background:var(--bg-surface);color:var(--text-primary);cursor:pointer;
                        font-family:inherit;outline:none;">
                <option value="5">Rentang 5 tahun</option>
                <option value="3">Rentang 3 tahun</option>
                <option value="2">Rentang 2 tahun</option>
            </select>
        </div>
        <div id="tenureChartWrap" class="chart-wrapper-md">
            <canvas id="tenureChart" role="img" aria-label="Distribusi masa kerja anggota">Data masa kerja anggota.</canvas>
        </div>
    </div>

</div>

{{-- ── MODAL: INPUT TOTAL KARYAWAN ────────────────────────────── --}}
<div id="modalTotalKaryawan"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);
            align-items:flex-start;justify-content:center;padding:60px 16px 16px;overflow-y:auto;">

    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:20px;
                box-shadow:0 24px 64px rgba(0,0,0,0.2);width:100%;max-width:420px;
                animation:fadeUp 0.2s ease both;">

        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:18px 24px 14px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:34px;height:34px;border-radius:10px;background:var(--accent-muted);
                             display:flex;align-items:center;justify-content:center;">
                    <svg style="width:16px;height:16px;color:var(--accent-text);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text-primary);">Input Total Karyawan</div>
                    <div style="font-size:12px;color:var(--text-muted);">Isi jumlah total karyawan perusahaan</div>
                </div>
            </div>
            <button onclick="closeTotalKaryawanModal()"
                    style="width:28px;height:28px;border-radius:7px;border:1px solid var(--border);
                           background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
                           color:var(--text-muted);transition:var(--dash-transition);font-size:14px;"
                    onmouseover="this.style.background='var(--bg-elevated)'">✕</button>
        </div>

        <div style="padding:20px 24px;">

            <div id="modalError"
                 style="display:none;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:10px;
                        background:rgba(220,38,38,0.07);border:1px solid rgba(220,38,38,0.2);
                        margin-bottom:16px;font-size:13px;color:#B91C1C;">
                <span id="modalErrorText"></span>
            </div>

            <div id="modalSuccess"
                 style="display:none;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:10px;
                        background:rgba(22,163,74,0.07);border:1px solid rgba(22,163,74,0.2);
                        margin-bottom:16px;font-size:13px;color:#15803d;">
                <span id="modalSuccessText"></span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label class="form-label" style="font-size:12px;">Bulan</label>
                    <select id="tkBulan" class="form-input" style="cursor:pointer;padding:8px 12px;">
                        @foreach(range(1,12) as $b)
                            <option value="{{ $b }}" {{ now()->month == $b ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;">Tahun</label>
                    <select id="tkTahun" class="form-input" style="cursor:pointer;padding:8px 12px;">
                        @for($t = now()->year + 1; $t >= 2020; $t--)
                            <option value="{{ $t }}" {{ now()->year == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size:12px;">Total Karyawan</label>
                <input id="tkTotal" type="number" min="0" placeholder="Contoh: 2500"
                       class="form-input" style="font-size:16px;font-weight:600;padding:10px 14px;"
                       onkeydown="if(event.key==='Enter') saveTotalKaryawan()">
                <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px;">Jumlah seluruh karyawan perusahaan pada periode ini</div>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 24px 20px;border-top:1px solid var(--border);">
            <button onclick="closeTotalKaryawanModal()" class="btn btn-ghost" style="padding:8px 18px;font-size:13px;">Batal</button>
            <button id="tkSaveBtn" onclick="saveTotalKaryawan()" class="btn btn-primary" style="gap:6px;padding:8px 20px;font-size:13px;">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan
            </button>
        </div>

    </div>
</div>


<script>
/* ── SCRIPT DASHBOARD ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /* ── SETUP ───────────────────────────────────────────────────── */
    const isDark    = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    const txtColor  = isDark ? '#94a3b8' : '#6b7280';
    const isMobile  = window.innerWidth <= 640;

    Chart.defaults.font.family = 'inherit';
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = txtColor;

    const PALETTE = ['#378add','#3d9a5e','#e24b4a','#ba7517','#7f77dd',
                     '#d4537e','#1d9e75','#639922','#985b3b','#2a9d8f'];
    const BULAN_LABEL = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const BULAN_FULL  = ['Januari','Februari','Maret','April','Mei','Juni',
                         'Juli','Agustus','September','Oktober','November','Desember'];

    /* ── HELPERS ─────────────────────────────────────────────────── */
    function fmtNum(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toLocaleString('id-ID');
    }

    function makeLegend(containerId, labels, colors, data) {
        const el = document.getElementById(containerId);
        if (!el) return;
        const total = data.reduce((a, b) => a + b, 0);
        el.innerHTML = labels.map((lbl, i) => {
            const pct = total > 0 ? Math.round(data[i] / total * 100) : 0;
            return `<span style="display:flex;align-items:center;gap:4px;">
                <span style="width:9px;height:9px;border-radius:2px;background:${colors[i]};flex-shrink:0;"></span>
                ${lbl} <strong style="color:var(--text-primary);">${fmtNum(data[i])}</strong> (${pct}%)
            </span>`;
        }).join('');
    }

    function doughnutCenterPlugin(id, total, label) {
        return {
            id,
            afterDraw(chart) {
                if (chart.tooltip && chart.tooltip.getActiveElements && chart.tooltip.getActiveElements().length > 0) {
                    return;
                }
                const { ctx } = chart;
                const { left, top, width, height } = chart.chartArea;
                const cx = left + width / 2;
                const cy = top + height / 2;
                ctx.save();
                ctx.fillStyle = isDark ? '#f1f5f9' : '#111827';
                ctx.font = `600 16px ${getComputedStyle(document.body).fontFamily}`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(fmtNum(total), cx, cy - 8);
                ctx.fillStyle = txtColor;
                ctx.font = `11px ${getComputedStyle(document.body).fontFamily}`;
                ctx.fillText(label, cx, cy + 10);
                ctx.restore();
            }
        };
    }

    /* ── STAT CARDS ──────────────────────────────────────────────── */
    (function() {
        const s = PHP.summary;
        const netSign  = s.netChange > 0 ? '+' : '';
        const netColor = s.netChange > 0 ? '#16A34A' : s.netChange < 0 ? '#DC2626' : txtColor;
        const cards = [
            { label:'Total Anggota Aktif', value:fmtNum(s.totalAktif), sub:`Dari ${fmtNum(s.totalHistoris)} historis`, color:'#3d9a5e' },
            { label:'Masuk Bulan Ini',     value:fmtNum(s.masukBulanIni), sub:'Anggota baru', color:'#378add' },
            { label:'Keluar Bulan Ini',    value:fmtNum(s.keluarBulanIni), sub:'Resign / habis kontrak', color:'#e24b4a' },
            { label:'Net Change Bulan Ini',value:netSign+fmtNum(s.netChange), sub:`Rasio aktif ${s.rasioAktif}%`, color:netColor, valueColor:netColor },
        ];
        document.getElementById('statGrid').innerHTML = cards.map(c => `
            <div class="stat-card">
                <div class="label">${c.label}</div>
                <div class="value" style="color:${c.valueColor || 'var(--text-primary)'}">${c.value}</div>
                <div class="sub"><span class="dot" style="background:${c.color};"></span>${c.sub}</div>
            </div>
        `).join('');
    })();

    /* ════════════════════════════════════════════════════════════
       TREN — state
    ═════════════════════════════════════════════════════════════ */
    let trendChartInst    = null;
    let movementChartInst = null;
    let trenMode          = 'month';
    let currentTrenData   = null;
    const trendCache      = {};
    trendCache[PHP.tahunAktif] = PHP.tren;

    /* Populate selects */
    (function() {
        const years = PHP.tahunList?.length ? PHP.tahunList : [new Date().getFullYear()];
        const selMY = document.getElementById('trenMonthYear');
        years.forEach(y => selMY.appendChild(new Option(y, y, y === PHP.tahunAktif, y === PHP.tahunAktif)));

        ['trenMonthFrom','trenMonthTo'].forEach((id, idx) => {
            const sel = document.getElementById(id);
            BULAN_FULL.forEach((b, i) => sel.appendChild(new Option(b, i+1, idx===0&&i===0, idx===1&&i===11)));
        });
        ['trenYearFrom','trenYearTo'].forEach((id, idx) => {
            const sel = document.getElementById(id);
            const minY = Math.min(...years);
            years.forEach(y => sel.appendChild(new Option(y, y, idx===0&&y===minY, idx===1&&y===PHP.tahunAktif)));
        });
    })();

    function updateTrenTitle(mode, ctx) {
        const el = document.getElementById('trenTitle');
        if (!el) return;
        if (mode === 'year') {
            const fromY = document.getElementById('trenYearFrom').value;
            const toY   = document.getElementById('trenYearTo').value;
            el.textContent = fromY === toY ? `Tren anggota tahun ${fromY}` : `Tren anggota ${fromY}–${toY}`;
        } else {
            const tahun = ctx.tahun;
            const fromM = parseInt(document.getElementById('trenMonthFrom').value);
            const toM   = parseInt(document.getElementById('trenMonthTo').value);
            el.textContent = fromM === toM
                ? `Tren anggota ${BULAN_FULL[fromM-1]} ${tahun}`
                : `Tren anggota ${BULAN_FULL[fromM-1]}–${BULAN_FULL[toM-1]} ${tahun}`;
        }
    }

    window.setTrenMode = function(mode) {
        trenMode = mode;
        ['Month','Year'].forEach(m => {
            const active = m.toLowerCase() === mode;
            const btn = document.getElementById('btnMode' + m);
            if (btn) btn.classList.toggle('active', active);
            const range = document.getElementById('trenRange' + m);
            if (range) range.style.display = active ? 'flex' : 'none';
        });
        mode === 'month' ? applyMonthRange() : applyYearRange();
    };

    async function fetchTahun(tahun) {
        if (trendCache[tahun]) return trendCache[tahun];
        const res = await fetch(`${PHP.trenUrl}?tahun=${tahun}`, {
            headers: { 'X-CSRF-TOKEN': PHP.csrfToken, 'Accept': 'application/json' }
        });
        trendCache[tahun] = await res.json();
        return trendCache[tahun];
    }

    function setLoading(on) {
        const loading = document.getElementById('trendLoading');
        const chart   = document.getElementById('trendChart');
        if (loading) loading.style.display = on ? 'block' : 'none';
        if (chart) chart.style.opacity = on ? '0.2' : '1';
    }

    async function applyMonthRange() {
        const tahun = parseInt(document.getElementById('trenMonthYear').value);
        let fromM   = parseInt(document.getElementById('trenMonthFrom').value);
        let toM     = parseInt(document.getElementById('trenMonthTo').value);
        if (fromM > toM) { document.getElementById('trenMonthTo').value = fromM; toM = fromM; }

        setLoading(true);
        try {
            const data = await fetchTahun(tahun);
            const fi = fromM - 1, ti = toM - 1;
            const now    = new Date();
            const cutoff = tahun === now.getFullYear() ? now.getMonth() : 11;

            const slice = (arr) => {
                if (!Array.isArray(arr)) return new Array(ti - fi + 1).fill(null);
                return arr.slice(fi, ti + 1).map((v, i) => (fi + i) <= cutoff ? v : null);
            };

            const sliced = {
                labels        : BULAN_LABEL.slice(fi, ti + 1),
                aktif         : slice(data.aktif),
                masuk         : slice(data.masuk),
                keluar        : slice(data.keluar),
                totalKaryawan : slice(data.totalKaryawan ?? new Array(12).fill(null)),
                rasio         : slice(data.rasio         ?? new Array(12).fill(null)),
                tahun,
                mode          : 'month',
                tahunList     : [tahun],
            };

            currentTrenData = sliced;
            updateTrenTitle('month', { tahun });
            buildTrendChart(sliced);
            buildTrendStats(sliced);
            buildMovementChart(sliced);
            buildMovementStats(sliced);
        } catch(e) { console.error(e); }
        finally    { setLoading(false); }
    }

    async function applyYearRange() {
        let fromY = parseInt(document.getElementById('trenYearFrom').value);
        let toY   = parseInt(document.getElementById('trenYearTo').value);
        if (fromY > toY) { document.getElementById('trenYearTo').value = fromY; toY = fromY; }

        setLoading(true);
        try {
            const years   = [];
            for (let y = fromY; y <= toY; y++) years.push(y);
            const allData = await Promise.all(years.map(fetchTahun));

            const now    = new Date();
            const labels = years.map(String);

            const aktif = allData.map((d, i) => {
                const y = years[i];
                if (y < now.getFullYear())   return d.aktif[11] ?? d.aktif.filter(v=>v!==null).at(-1) ?? null;
                if (y === now.getFullYear()) return d.aktif[now.getMonth()];
                return null;
            });

            const totalKaryawan = allData.map((d, i) => {
                const arr  = d.totalKaryawan ?? [];
                const now2 = new Date();
                const y    = years[i];
                if (y < now2.getFullYear())  return arr[11] ?? arr.filter(v=>v!==null).at(-1) ?? null;
                if (y === now2.getFullYear()) return arr[now2.getMonth()] ?? null;
                return null;
            });

            const rasio = aktif.map((a, i) => {
                const tk = totalKaryawan[i];
                return (a !== null && tk !== null && tk > 0) ? round1(a / tk * 100) : null;
            });

            const masuk  = allData.map(d => d.masuk.reduce((a,b)=>a+b,0));
            const keluar = allData.map(d => d.keluar.reduce((a,b)=>a+b,0));

            const sliced = { 
                labels, 
                aktif, 
                masuk, 
                keluar, 
                totalKaryawan, 
                rasio, 
                tahun:`${fromY}–${toY}`, 
                mode:'year',
                tahunList: years,
                labelMap: years.map((y, i) => ({ label: labels[i], year: y }))
            };

            currentTrenData = sliced;
            updateTrenTitle('year', {});
            buildTrendChart(sliced);
            buildTrendStats(sliced);
            buildMovementChart(sliced);
            buildMovementStats(sliced);
        } catch(e) { console.error(e); }
        finally    { setLoading(false); }
    }

    function round1(n) { return Math.round(n * 10) / 10; }

    function buildTrendChart(data) {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;
        if (trendChartInst) trendChartInst.destroy();

        const toBar  = arr => arr.map(v => (v === null || v === undefined) ? NaN : v);
        const toLine = arr => arr.map(v => (v === null || v === undefined) ? null : v);

        const hasTK    = (data.totalKaryawan ?? []).some(v => v !== null && !isNaN(v));
        const hasRasio = (data.rasio ?? []).some(v => v !== null);

        const noteEl   = document.getElementById('trendNoDataNote');
        const noteText = document.getElementById('trendNoDataText');
        if (!hasTK) {
            if (noteEl) noteEl.style.display  = 'flex';
            if (noteText) noteText.textContent  = 'Data total karyawan belum diinput untuk periode ini. Klik "Input Total Karyawan" untuk mengisi.';
        } else {
            const missing = data.labels.filter((_, i) => data.totalKaryawan[i] === null && data.aktif[i] !== null);
            if (missing.length > 0) {
                if (noteEl) noteEl.style.display = 'flex';
                if (noteText) noteText.textContent = `Data karyawan belum tersedia untuk: ${missing.join(', ')}. Rasio pada bulan tersebut tidak ditampilkan.`;
            } else {
                if (noteEl) noteEl.style.display = 'none';
            }
        }

        trendChartInst = new Chart(ctx, {
            type : 'bar',
            data : {
                labels   : data.labels,
                datasets : [
                    {
                        type:'bar', label:'Anggota Aktif', data:toBar(data.aktif),
                        backgroundColor: isDark ? 'rgba(61,154,94,0.82)' : 'rgba(61,154,94,0.78)',
                        borderRadius:4, borderSkipped:false, yAxisID:'yLeft', order:2,
                    },
                    {
                        type:'bar', label:'Total Karyawan',
                        data:toBar(data.totalKaryawan ?? new Array(data.labels.length).fill(NaN)),
                        backgroundColor: isDark ? 'rgba(148,163,184,0.55)' : 'rgba(148,163,184,0.50)',
                        borderRadius:4, borderSkipped:false, yAxisID:'yLeft', order:3,
                    },
                    {
                        type:'line', label:'Rasio (%)',
                        data:toLine(data.rasio ?? new Array(data.labels.length).fill(null)),
                        borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,0.12)',
                        borderWidth:2.5, pointRadius:5, pointHoverRadius:7,
                        pointBackgroundColor:'#f59e0b',
                        pointBorderColor: isDark ? '#181C26' : '#fff',
                        pointBorderWidth:2, tension:0.35, fill:false, spanGaps:false,
                        yAxisID:'yRight', order:1,
                    },
                ]
            },
            options : {
                responsive:true, maintainAspectRatio:false,
                interaction:{ mode:'index', intersect:false },
                plugins:{
                    legend:{ display:false },
                    datalabels:{
                        display:(ctx) => ctx.dataset.type === 'bar',
                        anchor:'end', align:'top', offset:2,
                        rotation: window.innerWidth < 640 ? -90 : 0,
                        color: isDark ? '#e5e7eb' : '#374151',
                        font:{ size:11, weight:'600' },
                        formatter:(value) => isNaN(value) ? '' : fmtNum(value)
                    },
                    tooltip:{
                        callbacks:{
                            label:ctx => {
                                if (isNaN(ctx.parsed.y) || ctx.parsed.y === null) return null;
                                if (ctx.dataset.label === 'Rasio (%)') return ` Rasio: ${ctx.parsed.y}%`;
                                return ` ${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}`;
                            }
                        }
                    }
                },
                scales:{
                    x:{ grid:{display:false}, ticks:{autoSkip:false, maxRotation: isMobile ? 90 : 0, minRotation: isMobile ? 90 : 0, font:{size:11}} },
                    yLeft:{
                        type:'linear', position:'left', grid:{color:gridColor},
                        ticks:{ stepSize:100, font:{size:11}, callback:v => v>=1000?(v/1000).toFixed(1)+'k':v },
                        beginAtZero:false, grace:'18%',
                    },
                    yRight:{
                        type:'linear', position:'right', display:hasRasio,
                        grid:{drawOnChartArea:false}, min:0, max:100,
                        ticks:{ font:{size:11}, color:'#f59e0b', callback:v => v+'%' },
                    },
                }
            }
        });
    }

    function buildTrendStats(data) {
        const el = document.getElementById('trendStats');
        if (!el) return;
        const validAktif = data.aktif.filter(v => v !== null && !isNaN(v));
        const tertinggi  = validAktif.length ? Math.max(...validAktif) : 0;
        const terendah   = validAktif.length ? Math.min(...validAktif) : 0;
        const terakhir   = validAktif.length ? validAktif[validAktif.length - 1] : 0;
        const validRasio = (data.rasio ?? []).filter(v => v !== null);
        const rasioTerakhir = validRasio.length ? validRasio[validRasio.length - 1] : null;

        el.innerHTML = `
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Aktif tertinggi</div>
                <div style="font-size:20px;font-weight:700;color:#3d9a5e;">${fmtNum(tertinggi)}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Aktif terendah</div>
                <div style="font-size:20px;font-weight:700;color:#e24b4a;">${fmtNum(terendah)}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Aktif saat ini</div>
                <div style="font-size:20px;font-weight:700;color:var(--text-primary);">${fmtNum(terakhir)}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Rasio terakhir</div>
                <div style="font-size:20px;font-weight:700;color:#f59e0b;">
                    ${rasioTerakhir !== null ? rasioTerakhir + '%' : '—'}
                </div>
            </div>`;
    }

    function buildMovementChart(data) {
        const ctx = document.getElementById('movementChart');
        if (!ctx) return;
        if (movementChartInst) movementChartInst.destroy();

        const toBar = arr => arr.map(v => (v === null || v === undefined) ? NaN : v);

        movementChartInst = new Chart(ctx, {
            type:'bar',
            data:{
                labels:data.labels,
                datasets:[
                    { label:'Masuk',  data:toBar(data.masuk),  backgroundColor:'#378addcc', borderRadius:5, borderSkipped:false },
                    { label:'Keluar', data:toBar(data.keluar), backgroundColor:'#e24b4acc', borderRadius:5, borderSkipped:false },
                ]
            },
            options:{
                responsive:true, maintainAspectRatio:false,
                interaction:{ mode:'nearest', intersect:true },
                onClick: function(e, elements) {
                    if (!elements.length) return;
                    const el    = elements[0];
                    const jenis = el.datasetIndex === 0 ? 'masuk' : 'keluar';
                    const index = el.index;
                    
                    let tahun = null;
                    let bulan = null;
                    let label = '';
                    
                    if (currentTrenData && currentTrenData.mode === 'year') {
                        // Mode tahun: ambil tahun dari labelMap
                        if (currentTrenData.labelMap && currentTrenData.labelMap[index]) {
                            tahun = currentTrenData.labelMap[index].year;
                            label = String(tahun);
                            // Kirim bulan = 0 atau null untuk menandakan seluruh tahun
                            bulan = 0;
                        }
                    } else if (currentTrenData && currentTrenData.mode === 'month') {
                        // Mode bulan: gunakan tahun dan bulan
                        tahun = currentTrenData.tahun;
                        bulan = index + 1;
                        label = `${data.labels[index]} ${tahun}`;
                    }
                    
                    if (!tahun) {
                        console.warn('Tahun tidak ditemukan untuk index:', index);
                        return;
                    }
                    
                    const title = jenis === 'masuk' ? 'Anggota Masuk' : 'Anggota Keluar';
                    const icon = jenis === 'masuk' ? '🟢' : '🔴';
                    const subtitle = currentTrenData.mode === 'year' 
                        ? `Seluruh tahun ${tahun}` 
                        : `${data.labels[index]} ${tahun}`;
                    
                    showDetail(
                        { type: jenis, tahun: tahun, bulan: bulan },
                        `Detail ${title}`,
                        subtitle,
                        icon
                    );
                },
                plugins:{
                    legend:{ display:false },
                    datalabels:{
                        display:true, anchor:'end', align:'top', offset:2,
                        clip:false, clamp:true,
                        rotation: window.innerWidth < 640 ? -90 : 0,
                        color: isDark ? '#e5e7eb' : '#374151',
                        font:{ size:11, weight:'600' },
                        formatter:(value) => isNaN(value) ? '' : fmtNum(value)
                    },
                    tooltip:{ callbacks:{ label:ctx => isNaN(ctx.parsed.y) ? null : ` ${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}` } }
                },
                scales:{
                    x:{ grid:{display:false}, ticks:{autoSkip:false, maxRotation: isMobile ? 90 : 0, minRotation: isMobile ? 90 : 0, font:{size:11}} },
                    y:{ beginAtZero:true, grace:'10%', grid:{color:gridColor}, ticks:{font:{size:11}, callback:v=>fmtNum(v)} }
                }
            }
        });
    }

    function buildMovementStats(data) {
        const el = document.getElementById('movementStats');
        if (!el) return;
        const totalMasuk  = data.masuk.reduce((a,b)=>a+(b??0),0);
        const totalKeluar = data.keluar.reduce((a,b)=>a+(b??0),0);
        const net = totalMasuk - totalKeluar;
        el.innerHTML = `
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Total masuk</div>
                <div style="font-size:20px;font-weight:700;color:#378add;">${fmtNum(totalMasuk)}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Total keluar</div>
                <div style="font-size:20px;font-weight:700;color:#e24b4a;">${fmtNum(totalKeluar)}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Net growth</div>
                <div style="font-size:20px;font-weight:700;color:${net>=0?'#16A34A':'#DC2626'};">${net>0?'+':''}${fmtNum(net)}</div>
            </div>`;
    }

    /* Render awal */
    applyMonthRange();

    /* ── DOUGHNUT — gender ───────────────────────────────────────── */
    (function(){
        const raw=PHP.distribusi.gender,labels=Object.keys(raw),vals=Object.values(raw).map(Number);
        const colors=['#378add','#d4537e','#639922','#ba7517'],total=vals.reduce((a,b)=>a+b,0);
        const canvas = document.getElementById('genderChart');
        if (!canvas) return;
        new Chart(canvas,{
            type:'doughnut',
            data:{labels,datasets:[{data:vals,backgroundColor:colors.slice(0,labels.length),borderColor:isDark?'#181C26':'#fff',borderWidth:3,hoverOffset:6}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{display:false},datalabels:{display:false},
            tooltip:{
                backgroundColor:isDark?'#111827':'#1f2937',titleColor:'#ffffff',bodyColor:'#ffffff',
                borderColor:'#374151',borderWidth:1,cornerRadius:10,padding:12,displayColors:true,
                titleFont:{size:14,weight:'bold'},bodyFont:{size:13},
                callbacks:{
                    title:function(ctx){ return ctx[0].label; },
                    label:function(ctx){ const pct=total>0?Math.round(ctx.parsed/total*100):0; return `Total: ${fmtNum(ctx.parsed)} (${pct}%)`; },
                    afterLabel:function(ctx){
                        const detail=PHP.distribusi.genderDetail?.[ctx.label]??{};
                        const rows=Object.entries(detail).map(([s,j])=>`• ${s}: ${fmtNum(j)}`);
                        return rows.length?[' ',...rows]:[];
                    }
                }
            }}},
            plugins:[doughnutCenterPlugin('genderCenter',total,'total')]
        });
        makeLegend('genderLegend',labels,colors,vals);
    })();

    /* ── DOUGHNUT — status ───────────────────────────────────────── */
    (function(){
        const raw=PHP.distribusi.status,labels=Object.keys(raw),vals=Object.values(raw).map(Number);
        const colors=['#3d9a5e','#ba7517','#7f77dd','#e24b4a','#378add'],total=vals.reduce((a,b)=>a+b,0);
        const canvas = document.getElementById('statusChart');
        if (!canvas) return;
        new Chart(canvas,{
            type:'doughnut',
            data:{labels,datasets:[{data:vals,backgroundColor:colors.slice(0,labels.length),borderColor:isDark?'#181C26':'#fff',borderWidth:3,hoverOffset:6}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{display:false},datalabels:{display:false},
            tooltip:{
                backgroundColor:isDark?'#111827':'#1f2937',titleColor:'#ffffff',bodyColor:'#ffffff',
                borderColor:'#374151',borderWidth:1,cornerRadius:10,padding:12,displayColors:true,
                titleFont:{size:14,weight:'bold'},bodyFont:{size:13},
                callbacks:{
                    title:function(ctx){ return ctx[0].label; },
                    label:function(ctx){ const pct=total>0?Math.round(ctx.parsed/total*100):0; return `Total: ${fmtNum(ctx.parsed)} (${pct}%)`; },
                    afterLabel:function(ctx){
                        const detail=PHP.distribusi.statusDetail?.[ctx.label]??{};
                        const rows=Object.entries(detail).map(([g,j])=>`• ${g}: ${fmtNum(j)}`);
                        return rows.length?[' ',...rows]:[];
                    }
                }
            }}},
            plugins:[doughnutCenterPlugin('statusCenter',total,'total')]
        });
        makeLegend('statusLegend',labels,colors,vals);
    })();

    /* ── DOUGHNUT — pendidikan ───────────────────────────────────── */
    (function(){
        const raw=PHP.distribusi.pendidikan,labels=Object.keys(raw),vals=Object.values(raw).map(Number);
        const colors=['#378add','#3d9a5e','#ba7517','#7f77dd','#e24b4a','#d4537e','#1d9e75','#639922','#985b3b','#2a9d8f'],total=vals.reduce((a,b)=>a+b,0);
        const canvas = document.getElementById('eduChart');
        if (!canvas) return;
        new Chart(canvas,{
            type:'doughnut',
            data:{labels,datasets:[{data:vals,backgroundColor:colors.slice(0,labels.length),borderColor:isDark?'#181C26':'#fff',borderWidth:3,hoverOffset:6}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{display:false},datalabels:{display:false},
            tooltip:{callbacks:{label:ctx=>{const pct=total>0?Math.round(ctx.parsed/total*100):0;return ` ${ctx.label}: ${fmtNum(ctx.parsed)} (${pct}%)`}}}}},
            plugins:[doughnutCenterPlugin('eduCenter',total,'total')]
        });
        makeLegend('eduLegend',labels,colors,vals);
    })();

    /* ── BAR — departemen ────────────────────────────────────────── */
    let deptChartInst = null;
    let deptShowAll   = false;
    const deptUrl     = "{{ route('anggota.dashboard.departemen') }}";

    function buildDeptChart(labels, vals) {
        const wrap = document.getElementById('deptWrap');
        if (!wrap) return;
        const h = Math.max(280, labels.length * 44 + 60);
        wrap.style.height = h + 'px';

        let canvas = document.getElementById('deptChart');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'deptChart';
            wrap.appendChild(canvas);
        }

        if (deptChartInst) deptChartInst.destroy();

        deptChartInst = new Chart(canvas, {
            type:'bar',
            data:{
                labels,
                datasets:[{
                    label:'Anggota', data:vals,
                    backgroundColor:labels.map((_,i)=>PALETTE[i%PALETTE.length]+(isDark?'bb':'cc')),
                    borderRadius:5, borderSkipped:false,
                }]
            },
            options:{
                responsive:true, maintainAspectRatio:false, indexAxis:'y',
                animation:{
                    duration: deptShowAll ? 400 : 2000,
                    easing:'easeOutQuart',
                    delay:(ctx) => deptShowAll ? 0 : ctx.dataIndex * 80,
                },
                onClick(e, elements){
                    if (!elements.length) return;
                    const index = elements[0].index;
                    showDetail(
                        { type:'departemen', value:labels[index] },
                        'Detail Departemen', labels[index], '🏢'
                    );
                },
                plugins:{
                    legend:{ display:false },
                    datalabels:{
                        display:true, anchor:'end', align:'right', offset:6,
                        color: isDark ? '#e5e7eb' : '#374151',
                        font:{ size:12, weight:'700' },
                        formatter:(value) => isNaN(value) ? '' : fmtNum(value),
                    },
                    tooltip:{ callbacks:{ label:ctx => ` ${fmtNum(ctx.parsed.x)} anggota` } }
                },
                scales:{
                    x:{ grid:{color:gridColor}, ticks:{font:{size:12}, grace:'10%', callback:v=>fmtNum(v)} },
                    y:{ grid:{display:false}, ticks:{font:{size:12}} }
                }
            }
        });
    }

    async function loadDept(all = false) {
        const loading = document.getElementById('deptLoading');
        const wrap    = document.getElementById('deptWrap');
        if (loading) loading.style.display = 'block';
        if (wrap) wrap.style.opacity = '0.3';
        try {
            const res  = await fetch(`${deptUrl}?all=${all}`, {
                headers:{ 'X-CSRF-TOKEN':PHP.csrfToken, 'Accept':'application/json' }
            });
            const data = await res.json();
            buildDeptChart(data.labels, data.data);
        } catch(e) { console.error(e); }
        finally {
            if (loading) loading.style.display = 'none';
            if (wrap) wrap.style.opacity = '1';
        }
    }

    window.toggleDepartemen = async function() {
        deptShowAll = !deptShowAll;
        const icon  = document.getElementById('deptToggleIcon');
        const label = document.getElementById('deptToggleLabel');
        if (icon) icon.style.transform = deptShowAll ? 'rotate(180deg)' : 'rotate(0deg)';
        if (label) label.textContent    = deptShowAll ? 'Tampilkan Lebih Sedikit' : 'Lihat Selengkapnya';
        await loadDept(deptShowAll);
    };

    buildDeptChart(PHP.departemen.labels, PHP.departemen.data);

    /* ── BAR — umur ──────────────────────────────────────────────── */
    (function(){
        const d = PHP.distribusiUmur;
        let umurChartInst = null;
        let umurCurrentMode = 5;

        const pillColors = {
            'Rata-rata' : { bg: isDark ? '#1e1b4b' : '#eef2ff', text: isDark ? '#a5b4fc' : '#4338ca' },
            'Median'    : { bg: isDark ? '#0c2340' : '#eff6ff', text: isDark ? '#93c5fd' : '#1d4ed8' },
            'Termuda'   : { bg: isDark ? '#052e16' : '#f0fdf4', text: isDark ? '#86efac' : '#15803d' },
            'Tertua'    : { bg: isDark ? '#431407' : '#fff7ed', text: isDark ? '#fdba74' : '#c2410c' },
        };
        const statsEl = document.getElementById('umurStats');
        if (statsEl) {
            statsEl.innerHTML = [
                { label:'Rata-rata', value: d.rataRata !== null ? d.rataRata + ' thn' : '—' },
                { label:'Median',    value: d.median    !== null ? d.median    + ' thn' : '—' },
                { label:'Termuda',   value: d.termuda   !== null ? d.termuda   + ' thn' : '—' },
                { label:'Tertua',    value: d.tertua    !== null ? d.tertua    + ' thn' : '—' },
            ].map(p => {
                const c = pillColors[p.label];
                return `<div class="umur-pill" style="background:${c.bg};">
                    <span class="pill-value" style="color:${c.text}">${p.value}</span>
                    <span class="pill-label">${p.label}</span>
                </div>`;
            }).join('');
        }

        function getUmurDataset(mode) {
            if (mode === 2) return { labels: d.labels2, permanen: d.permanen2, kontrak: d.kontrak2 };
            if (mode === 1) return { labels: d.labels1, permanen: d.permanen1, kontrak: d.kontrak1 };
            return { labels: d.labels, permanen: d.permanen, kontrak: d.kontrak };
        }

        function buildUmurChart(mode) {
            const canvas = document.getElementById('umurChart');
            const scroll = document.getElementById('umurChartScroll');
            const wrap   = document.getElementById('umurChartWrap');
            
            if (!canvas || !wrap) return;
            if (umurChartInst) umurChartInst.destroy();

            const ds     = getUmurDataset(mode);
            const minBarPx = mode === 1 ? 26 : mode === 2 ? 36 : 52;
            const minTotal = ds.labels.length * minBarPx;
            const wrapW    = wrap.offsetWidth || 300;

            if (scroll) {
                if (minTotal > wrapW) {
                    scroll.style.width    = minTotal + 'px';
                    scroll.style.position = 'relative';
                    wrap.style.overflowX  = 'auto';
                    wrap.style.overflowY  = 'hidden';
                    wrap.style.webkitOverflowScrolling = 'touch';
                } else {
                    scroll.style.width   = '100%';
                    wrap.style.overflowX = 'hidden';
                }
            }

            const chartH = isMobile ? (mode === 1 ? 260 : 220) : 240;
            wrap.style.height   = chartH + 'px';
            if (scroll) scroll.style.height = '100%';

            const needsRotate = mode === 1 || (mode === 2 && isMobile);

            umurChartInst = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: ds.labels,
                    datasets: [
                        {
                            label: 'Permanen',
                            data: ds.permanen,
                            backgroundColor: isDark ? '#2563ebcc' : '#3b82f6cc',
                            borderSkipped: 'bottom',
                            stack: 'status',
                        },
                        {
                            label: 'Kontrak',
                            data: ds.kontrak,
                            backgroundColor: isDark ? '#d97706cc' : '#f59e0bcc',
                            borderSkipped: 'bottom',
                            stack: 'status',
                        },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode:'index', intersect:false },
                    onClick(e, elements) {
                        if (!elements.length) return;
                        const index = elements[0].index;
                        showDetail(
                            { type:'umur', value: ds.labels[index] },
                            'Detail Distribusi Umur', ds.labels[index], '🎂'
                        );
                    },
                    plugins: {
                        legend: {
                            display: true, position: 'top', align: 'end',
                            labels: { boxWidth:12, boxHeight:12, borderRadius:3, useBorderRadius:true, font:{size:12}, color:txtColor }
                        },
                        datalabels: {
                            display: (ctx) => {
                                if (ctx.datasetIndex !== 1) return false;
                                if (mode === 1) return false;
                                if (mode === 2 && isMobile) return false;
                                const total = (ds.permanen[ctx.dataIndex] ?? 0) + (ds.kontrak[ctx.dataIndex] ?? 0);
                                return total > 0;
                            },
                            anchor:'end', align:'top', offset:4, clip:false, clamp:true,
                            color: isDark ? '#e5e7eb' : '#374151',
                            font: { size:11, weight:'600' },
                            formatter: (_, ctx) => {
                                const total = (ds.permanen[ctx.dataIndex] ?? 0) + (ds.kontrak[ctx.dataIndex] ?? 0);
                                return fmtNum(total);
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}`,
                                footer: (items) => `Total: ${fmtNum(items.reduce((s,i) => s + i.parsed.y, 0))}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display:false },
                            ticks: {
                                font: { size: mode === 1 ? 10 : 11 },
                                autoSkip: false,
                                maxRotation: needsRotate ? 90 : 0,
                                minRotation: needsRotate ? 90 : 0,
                            },
                            stacked: true
                        },
                        y: {
                            grid: { color:gridColor },
                            ticks: { font:{size:11}, callback:v => v>=1000?(v/1000).toFixed(1)+'k':v },
                            beginAtZero: true, grace:'10%', stacked: true
                        }
                    }
                }
            });
        }

        window.setUmurMode = function(mode) {
            umurCurrentMode = mode;
            const select = document.getElementById('umurModeSelect');
            if (select) select.value = mode;
            buildUmurChart(mode);
        };

        setTimeout(function() {
            buildUmurChart(5);
        }, 100);

        const coverage = d.totalDiketahui > 0
            ? Math.round(d.totalDiketahui / (d.totalDiketahui + d.tidakDiketahui) * 100) : 0;
        const footer = document.getElementById('umurFooter');
        if (footer) {
            footer.innerHTML = `
                <svg style="width:14px;height:14px;flex-shrink:0;color:var(--text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Data umur tersedia untuk <strong style="color:var(--text-secondary);">${fmtNum(d.totalDiketahui)}</strong>
                dari <strong style="color:var(--text-secondary);">${fmtNum(d.totalDiketahui + d.tidakDiketahui)}</strong> anggota aktif
                <span style="margin:0 6px;color:var(--border-strong);">·</span>
                cakupan data <strong style="color:var(--text-secondary);">${coverage}%</strong>`;
        }
    })();

    /* ── BAR — masa kerja ────────────────────────────────────────── */
    (function(){
        const mk = PHP.masaKerja;
        let masaKerjaChartInst = null;

        function getMasaKerjaDataset(mode) {
            if (mode === 3) return { labels: mk.labels3, permanen: mk.permanen3, kontrak: mk.kontrak3 };
            if (mode === 2) return { labels: mk.labels2, permanen: mk.permanen2, kontrak: mk.kontrak2 };
            return { labels: mk.labels, permanen: mk.permanen, kontrak: mk.kontrak };
        }

        function buildMasaKerjaChart(mode) {
            const ctx = document.getElementById('tenureChart');
            if (!ctx) return;
            const ds  = getMasaKerjaDataset(mode);

            if (masaKerjaChartInst) masaKerjaChartInst.destroy();

            masaKerjaChartInst = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ds.labels,
                    datasets: [
                        {
                            label: 'Permanen',
                            data: ds.permanen,
                            backgroundColor: isDark ? '#2563ebcc' : '#3b82f6cc',
                            borderSkipped: 'bottom',
                            stack: 'status',
                        },
                        {
                            label: 'Kontrak',
                            data: ds.kontrak,
                            backgroundColor: isDark ? '#d97706cc' : '#f59e0bcc',
                            borderSkipped: 'bottom',
                            stack: 'status',
                        },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode:'index', intersect:false },
                    onClick(e, elements) {
                        if (!elements.length) return;
                        const index = elements[0].index;
                        showDetail(
                            { type:'masa_kerja', value: ds.labels[index] },
                            'Detail Masa Kerja', ds.labels[index], '💼'
                        );
                    },
                    plugins: {
                        legend: {
                            display: true, position: 'top', align: 'end',
                            labels: { boxWidth:12, boxHeight:12, borderRadius:3, useBorderRadius:true, font:{size:12}, color:txtColor }
                        },
                        datalabels: {
                            display: (ctx) => {
                                if (ctx.datasetIndex !== 1) return false;
                                const total = (ds.permanen[ctx.dataIndex] ?? 0) + (ds.kontrak[ctx.dataIndex] ?? 0);
                                return total > 0;
                            },
                            anchor:'end', align:'top', offset:2, clip:false, clamp:true,
                            color: isDark ? '#e5e7eb' : '#374151',
                            font: { size:11, weight:'600' },
                            formatter: (_, ctx) => {
                                const total = (ds.permanen[ctx.dataIndex] ?? 0) + (ds.kontrak[ctx.dataIndex] ?? 0);
                                return fmtNum(total);
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}`,
                                footer: (items) => `Total: ${fmtNum(items.reduce((s,i) => s + i.parsed.y, 0))}`
                            }
                        }
                    },
                    scales: {
                        x: { grid:{display:false}, ticks:{autoSkip:false, font:{size:11}}, stacked:true },
                        y: { grid:{color:gridColor}, ticks:{font:{size:11}, callback:v=>fmtNum(v)}, beginAtZero:true, grace:'10%', stacked:true }
                    }
                }
            });
        }

        window.setMasaKerjaMode = function(mode) {
            const select = document.getElementById('masaKerjaModeSelect');
            if (select) select.value = mode;
            buildMasaKerjaChart(mode);
        };

        setTimeout(function() {
            buildMasaKerjaChart(5);
        }, 100);
    })();

    /* ── MODAL: TOTAL KARYAWAN ───────────────────────────────────── */
    window.openTotalKaryawanModal = function() {
        const modal = document.getElementById('modalTotalKaryawan');
        const error = document.getElementById('modalError');
        const success = document.getElementById('modalSuccess');
        const total = document.getElementById('tkTotal');
        if (error) error.style.display = 'none';
        if (success) success.style.display = 'none';
        if (total) total.value = '';
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => { if (total) total.focus(); }, 150);
        }
    };

    window.closeTotalKaryawanModal = function() {
        const modal = document.getElementById('modalTotalKaryawan');
        if (modal) modal.style.display = 'none';
    };

    document.getElementById('modalTotalKaryawan')?.addEventListener('click', function(e) {
        if (e.target === this) window.closeTotalKaryawanModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') window.closeTotalKaryawanModal();
    });

    window.saveTotalKaryawan = async function() {
        const bulan = parseInt(document.getElementById('tkBulan').value);
        const tahun = parseInt(document.getElementById('tkTahun').value);
        const total = document.getElementById('tkTotal').value.trim();

        const error = document.getElementById('modalError');
        const success = document.getElementById('modalSuccess');
        if (error) error.style.display = 'none';
        if (success) success.style.display = 'none';

        if (total === '' || isNaN(parseInt(total)) || parseInt(total) < 0) {
            const errorText = document.getElementById('modalErrorText');
            if (errorText) errorText.textContent = 'Total karyawan harus diisi dengan angka yang valid.';
            if (error) {
                error.style.display = 'flex';
                document.getElementById('tkTotal')?.focus();
            }
            return;
        }

        const btn = document.getElementById('tkSaveBtn');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;"></div> Menyimpan...';
        }

        try {
            const res = await fetch('{{ route("anggota.total-karyawan.save") }}', {
                method:'POST',
                headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':PHP.csrfToken },
                body:JSON.stringify({ bulan, tahun, total_karyawan:parseInt(total) }),
            });
            const json = await res.json();
            if (json.ok) {
                const successText = document.getElementById('modalSuccessText');
                if (successText) successText.textContent = json.message;
                if (success) success.style.display = 'flex';
                delete trendCache[tahun];
                setTimeout(window.closeTotalKaryawanModal, 1800);
            } else {
                const errorText = document.getElementById('modalErrorText');
                if (errorText) errorText.textContent = json.message;
                if (error) error.style.display = 'flex';
            }
        } catch {
            const errorText = document.getElementById('modalErrorText');
            if (errorText) errorText.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
            if (error) error.style.display = 'flex';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.innerHTML = '<svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Simpan';
            }
        }
    };

    // Expose functions globally
    window.applyMonthRange = applyMonthRange;
    window.applyYearRange = applyYearRange;

}); // end DOMContentLoaded
</script>

@include('anggota.partials.detail-modal')
@endsection
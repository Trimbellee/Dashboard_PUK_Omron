@extends('layouts.app')
@section('title','Dashboard')

@section('content')

<div id="dashboardContent" style="padding:8px 0;min-height:100vh;transition:opacity 0.3s;opacity:1;">

    {{-- ── HEADER ── --}}
    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <h1 style="font-size:1.1rem;font-weight:600;color:var(--text-primary);margin-bottom:3px;">Financial Dashboard</h1>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin:0;">Monitoring pengajuan, realisasi dan pelaporan dana</p>
            </div>
            <form method="GET" id="filterForm" style="display:flex;gap:8px;flex-wrap:wrap;">
                <select name="bulan" onchange="handleFilterChange()" class="form-input" style="width:auto;padding:7px 12px;font-size:13px;">
                    <option value="semua" {{ $bulan == 'semua' ? 'selected' : '' }}>Semua Bulan</option>
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}" {{ $bulan == $i ? 'selected':'' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                    @endfor
                </select>
                <div style="position:relative;flex:1;min-width:140px;">
                    <input list="tahunList" name="tahun" id="tahunInput" maxlength="4"
                        value="{{ $tahun === 'semua' ? '' : $tahun }}" placeholder="Tahun (kosong=semua)"
                        onchange="handleFilterChange()" class="form-input" style="width:100%;font-size:13px;">
                    <datalist id="tahunList">
                        <option value="semua">Semua Tahun</option>
                        @php $thisYear = date('Y'); @endphp
                        @for($t = $thisYear - 2; $t <= $thisYear + 2; $t++)
                            <option value="{{ $t }}">
                        @endfor
                    </datalist>
                </div>
            </form>
        </div>
    </div>

    {{-- PERIODE BADGE --}}
    @php
        if($bulan == 'semua' && $tahun == 'semua') { $periodeText = 'Semua Data'; }
        elseif($bulan == 'semua') { $periodeText = 'Semua Bulan • Tahun ' . $tahun; }
        elseif($tahun == 'semua') { $periodeText = date('F', mktime(0,0,0,$bulan,1)) . ' • Semua Tahun'; }
        else { $periodeText = date('F', mktime(0,0,0,$bulan,1)) . ' ' . $tahun; }
    @endphp
    <div style="margin-bottom:24px;">
        <div id="periodeBadge" style="display:inline-flex;align-items:center;gap:8px;padding:5px 14px;background:var(--accent-muted);color:var(--accent-text);font-size:0.8rem;font-weight:500;border-radius:999px;border:1px solid rgba(37,99,235,.15);">
            📊 {{ $periodeText }}
        </div>
    </div>

    {{-- ── SUMMARY CARDS ── --}}
    <div class="dash-grid-3" style="margin-bottom:28px;">
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:var(--text-muted);">📄 Total Pengajuan</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $totalPengajuan }}">0</h2>
            <p class="summary-biaya rupiah dash-card-sub" data-value="{{ $totalBiaya ?? 0 }}">Rp 0</p>
            <p class="kpi-indicator dash-card-kpi">-</p>
        </div>
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:#3B82F6;">💰 Realisasi</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $aktualBiaya }}">0</h2>
            <h2 class="summary-realisasi rupiah dash-card-sub" data-value="{{ $totalRealisasi ?? 0 }}">Rp 0</h2>
            <div>
                <div style="display:flex;justify-content:flex-end;font-size:10px;color:var(--text-secondary);margin-bottom:2px;">
                    <span class="summary-persen" data-value="{{ $persenRealisasi ?? 0 }}">0%</span>
                </div>
                <div style="width:100%;background:var(--bg-elevated);border-radius:999px;height:5px;overflow:hidden;">
                    <div class="progress-bar" data-value="{{ $persenRealisasi ?? 0 }}" style="background:var(--accent);height:5px;border-radius:999px;width:0%;transition:width .7s ease-out;"></div>
                </div>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:#EF4444;">⚠️ Belum Lapor</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $belomLapor }}">0</h2>
            <h2 class="summary-belum rupiah dash-card-sub" data-value="{{ $totalBelumDilaporkan ?? 0 }}">Rp 0</h2>
        </div>
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:#F59E0B;">⏳ Menunggu</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $menunggu }}">0</h2>
            <p class="summary-biaya rupiah dash-card-sub" data-value="{{ $biayaMenunggu ?? 0 }}">Rp 0</p>
            <p class="kpi-indicator dash-card-kpi">-</p>
        </div>
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:#22C55E;">✅ Disetujui</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $disetujui }}">0</h2>
            <p class="summary-biaya rupiah dash-card-sub" data-value="{{ $biayaDisetujui ?? 0 }}">Rp 0</p>
            <p class="kpi-indicator dash-card-kpi">-</p>
        </div>
        <div class="dash-card">
            <div class="dash-card-bar"></div>
            <p class="dash-card-label" style="color:#EF4444;">❌ Ditolak</p>
            <h2 class="summary-total dash-card-value" data-value="{{ $ditolak }}">0</h2>
            <p class="summary-biaya rupiah dash-card-sub" data-value="{{ $biayaDitolak ?? 0 }}">Rp 0</p>
            <p class="kpi-indicator dash-card-kpi">-</p>
        </div>
    </div>

    {{-- ── INSIGHT ── --}}
    <div class="insight-box" style="background:var(--accent-muted);border:1px solid rgba(99,102,241,.15);border-radius:14px;padding:18px;margin-bottom:28px;">
        <h3 style="font-weight:600;color:var(--accent-text);margin-bottom:8px;font-size:0.9rem;">💡 Insight Otomatis</h3>
        <ul id="insightList" style="font-size:0.825rem;color:var(--text-primary);display:flex;flex-direction:column;gap:4px;margin:0;padding:0;list-style:none;"></ul>
    </div>

    {{-- ── TABEL RINCIAN ── --}}
    <div style="background:var(--bg-surface);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow-x:auto;margin-bottom:28px;">
        <table style="min-width:640px;width:100%;font-size:0.75rem;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--bg-elevated);">
                    @foreach(['Alokasi Dana','Pengajuan Dana','Aktual Biaya','Dana Dikembalikan','Kegiatan Belum Dilaporkan','Belum Dilaporkan'] as $th)
                    <th style="padding:9px 14px;text-align:center;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary);border-bottom:1px solid var(--border);">{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="rincianTable" style="color:var(--text-primary);">
                @forelse($rincian as $row)
                <tr class="dash-tr-hover">
                    <td style="padding:9px 14px;font-weight:500;">{{ $row->bidang }}</td>
                    <td style="padding:9px 14px;text-align:right;">Rp {{ number_format($row->total_pengajuan ?? 0,0,',','.') }}</td>
                    <td style="padding:9px 14px;text-align:right;">Rp {{ number_format($row->total_aktual ?? 0,0,',','.') }}</td>
                    <td style="padding:9px 14px;text-align:right;">Rp {{ number_format($row->total_dikembalikan ?? 0,0,',','.') }}</td>
                    @php $kegiatan = $row->kegiatan_belum_lapor ?? 0; @endphp
                    <td style="padding:9px 14px;text-align:center;">
                        @if($kegiatan == 0)
                            <span style="padding:3px 8px;font-size:10px;font-weight:500;background:rgba(34,197,94,.1);color:#16A34A;border-radius:999px;">✓ Sudah Semua</span>
                        @else
                            <span onclick="showBelumLapor('{{ $row->bidang }}')" style="padding:3px 8px;font-size:10px;font-weight:500;background:rgba(239,68,68,.1);color:#DC2626;border-radius:999px;cursor:pointer;">⚠ {{ $kegiatan }} kegiatan</span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;text-align:right;font-weight:600;color:{{ ($row->belum_dilaporkan ?? 0) > 0 ? '#DC2626' : '#16A34A' }};">
                        Rp {{ number_format($row->belum_dilaporkan ?? 0,0,',','.') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Tidak ada data pada periode ini</td></tr>
                @endforelse
            </tbody>
            @if(count($rincian) > 0)
            <tfoot>
                <tr style="background:var(--bg-elevated);font-weight:600;color:var(--text-primary);">
                    <td style="padding:9px 14px;text-align:center;border-top:1px solid var(--border);">TOTAL</td>
                    <td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp {{ number_format($totalRow['total_pengajuan'],0,',','.') }}</td>
                    <td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp {{ number_format($totalRow['total_aktual'],0,',','.') }}</td>
                    <td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp {{ number_format($totalRow['total_dikembalikan'],0,',','.') }}</td>
                    <td style="border-top:1px solid var(--border);"></td>
                    <td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);color:#DC2626;">Rp {{ number_format($totalRow['belum_dilaporkan'],0,',','.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- ════ GRAFIK #1 + #3 ════ --}}
    <div class="graf-section" style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;padding:18px 20px;box-shadow:var(--shadow-sm);margin-bottom:20px;">

        {{-- Header --}}
        <div class="graf-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
            <span id="trenKeuTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);">Tren Dana Disetujui vs Aktual</span>
            <div class="graf-controls" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <div style="display:flex;gap:8px;font-size:11px;color:var(--text-secondary);flex-wrap:wrap;">
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:2px;background:#378add;flex-shrink:0;"></span>Disetujui</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:2px;background:#3d9a5e;flex-shrink:0;"></span>Aktual</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:2px;background:#f59e0b;border-radius:1px;flex-shrink:0;"></span>Rasio</span>
                </div>
                <div class="mode-toggle" style="display:flex;border:1px solid var(--border);border-radius:8px;overflow:hidden;font-size:12px;">
                    <button id="btnKeuModeMonth" onclick="setKeuMode('month')" style="padding:5px 11px;border:none;cursor:pointer;transition:all .15s;font-family:inherit;background:var(--accent-muted);color:var(--accent-text);font-weight:600;">Per Bulan</button>
                    <button id="btnKeuModeYear" onclick="setKeuMode('year')" style="padding:5px 11px;border:none;cursor:pointer;transition:all .15s;font-family:inherit;background:transparent;color:var(--text-secondary);">Per Tahun</button>
                </div>
                <div id="keuRangeMonth" class="range-wrap" style="display:flex;align-items:center;gap:5px;font-size:11px;flex-wrap:wrap;">
                    <select id="keuMonthYear" onchange="applyKeuMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">Bulan</span>
                    <select id="keuMonthFrom" onchange="applyKeuMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">–</span>
                    <select id="keuMonthTo" onchange="applyKeuMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                </div>
                <div id="keuRangeYear" style="display:none;align-items:center;gap:5px;font-size:11px;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);">Tahun</span>
                    <select id="keuYearFrom" onchange="applyKeuYearRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">–</span>
                    <select id="keuYearTo" onchange="applyKeuYearRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                </div>
            </div>
        </div>

        <div id="trenKeuLoading" style="display:none;text-align:center;padding:40px 0;font-size:13px;color:var(--text-muted);">Memuat data...</div>

        <div id="trenKeuChart-wrap" style="position:relative;width:100%;height:280px;">
            <canvas id="trenKeuChart"></canvas>
        </div>

        {{-- Stats — disembunyikan di mobile via CSS --}}
        <div id="trenKeuStats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);"></div>

        {{-- Grafik #3a: Pengeluaran per bidang --}}
        <div class="graf-inner" style="margin-top:18px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:14px;padding:16px;">
            <div style="margin-bottom:10px;">
                <span id="bidangTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);">Pengeluaran per bidang</span>
            </div>
            <div id="bidangLegend" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11px;color:var(--text-secondary);"></div>
            <div id="bidangWrap" style="position:relative;width:100%;height:380px;"><canvas id="bidangChart"></canvas></div>
        </div>

        {{-- Grafik #3b: Rasio per bidang --}}
        <div class="graf-inner" style="margin-top:10px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:14px;padding:16px;">
            <div style="margin-bottom:10px;">
                <span style="font-size:13px;font-weight:600;color:var(--text-primary);">Rasio penggunaan per bidang terhadap Dana Operasional</span>
                <p style="font-size:11px;color:var(--text-muted);margin:3px 0 0;">% = Aktual Biaya ÷ Dana Operasional</p>
                <span id="rasioLaporanNote" style="display:inline-flex;align-items:center;gap:5px;margin-top:4px;padding:2px 10px;border-radius:999px;font-size:10px;font-weight:500;background:rgba(99,102,241,.1);color:var(--accent-text);border:1px solid rgba(99,102,241,.2);"></span>
            </div>
            <div id="rasioWrap" style="position:relative;width:100%;height:280px;"><canvas id="rasioChart"></canvas></div>
        </div>
    </div>

    {{-- ════ GRAFIK #2: Debit vs Kredit ════ --}}
    <div class="graf-section" style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;padding:18px 20px;box-shadow:var(--shadow-sm);margin-bottom:20px;">

        <div class="graf-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
            <div>
                <span id="dkTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);">Dana Masuk vs Dana Keluar per jenis dana</span>
                <p style="font-size:11px;color:var(--text-muted);margin:3px 0 0;">Sumber: Laporan Bendahara</p>
                <span id="dkLaporanBadge" style="display:inline-flex;align-items:center;gap:5px;margin-top:4px;padding:2px 10px;border-radius:999px;font-size:10px;font-weight:500;background:rgba(99,102,241,.1);color:var(--accent-text);border:1px solid rgba(99,102,241,.2);">
                    📋 Memuat info laporan...
                </span>
            </div>
            <div class="graf-controls" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <div style="display:flex;gap:8px;font-size:11px;color:var(--text-secondary);flex-wrap:wrap;">
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:2px;background:#3d9a5e;flex-shrink:0;"></span>Dana Masuk</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:2px;background:#e24b4a;flex-shrink:0;"></span>Dana Keluar</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:2px;background:#7f77dd;border-radius:1px;flex-shrink:0;"></span>Saldo Akhir</span>
                </div>
                <div class="mode-toggle" style="display:flex;border:1px solid var(--border);border-radius:8px;overflow:hidden;font-size:12px;">
                    <button id="btnDkModeMonth" onclick="setDkMode('month')" style="padding:5px 11px;border:none;cursor:pointer;transition:all .15s;font-family:inherit;background:var(--accent-muted);color:var(--accent-text);font-weight:600;">Per Bulan</button>
                    <button id="btnDkModeYear" onclick="setDkMode('year')" style="padding:5px 11px;border:none;cursor:pointer;transition:all .15s;font-family:inherit;background:transparent;color:var(--text-secondary);">Per Tahun</button>
                </div>
                <div id="dkRangeMonth" class="range-wrap" style="display:flex;align-items:center;gap:5px;font-size:11px;flex-wrap:wrap;">
                    <select id="dkMonthYear" onchange="applyDkMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">Bulan</span>
                    <select id="dkMonthFrom" onchange="applyDkMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">–</span>
                    <select id="dkMonthTo" onchange="applyDkMonthRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                </div>
                <div id="dkRangeYear" style="display:none;align-items:center;gap:5px;font-size:11px;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);">Tahun</span>
                    <select id="dkYearFrom" onchange="applyDkYearRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                    <span style="color:var(--text-muted);">–</span>
                    <select id="dkYearTo" onchange="applyDkYearRange()" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);background:var(--bg-surface);color:var(--text-primary);cursor:pointer;"></select>
                </div>
            </div>
        </div>

        <div id="dkLoading" style="display:none;text-align:center;padding:40px 0;font-size:13px;color:var(--text-muted);">Memuat data...</div>

        

        <div id="dkTrendChart-wrap" style="position:relative;width:100%;height:280px;">
            <canvas id="dkTrendChart"></canvas>
        </div>

        {{-- Stats — disembunyikan di mobile via CSS --}}
        <div id="dkTrendStats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);"></div>

        {{-- Breakdown per jenis dana --}}
        <div class="graf-inner" style="margin-top:18px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:14px;padding:16px;">
            <div class="breakdown-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Breakdown per jenis</div>
                <select id="dkJenisFilter" onchange="changeDkJenis()"
                        style="min-width:180px;padding:6px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg-base);color:var(--text-primary);font-size:12px;cursor:pointer;">
                </select>
            </div>


            {{-- Stats jenis — disembunyikan di mobile via CSS --}}
            <div id="dkJenisStats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;"></div>

            <div id="dkBreakdownChart-wrap" style="position:relative;width:100%;height:300px;">
                <canvas id="dkBreakdownChart"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- ── MODAL DETAIL BIDANG ── --}}
<div id="bidangDetailModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,0.25);width:100%;max-width:960px;max-height:90vh;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:var(--accent-muted);">🏢</span>
                <div>
                    <div id="bdModalTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);">Detail Pengeluaran</div>
                    <div id="bdModalSubtitle" style="font-size:11px;color:var(--text-muted);margin-top:1px;"></div>
                </div>
            </div>
            <button onclick="closeBidangModal()" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text-muted);">✕</button>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border);flex-shrink:0;gap:8px;flex-wrap:wrap;">
            <div style="position:relative;flex:1;min-width:160px;max-width:280px;">
                <input id="bdSearch" type="text" placeholder="Cari nama / keperluan..." oninput="bdFilterTable()"
                    style="width:100%;padding:6px 10px 6px 30px;border-radius:7px;border:1px solid var(--border);background:var(--bg-base);color:var(--text-primary);font-family:inherit;font-size:12px;outline:none;"
                    onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                <span style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;font-size:12px;">🔍</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span id="bdCount" style="font-size:11px;color:var(--text-muted);white-space:nowrap;"></span>
                <button onclick="bdExportCSV()" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:7px;border:1px solid var(--border);background:transparent;color:var(--text-secondary);font-size:11px;cursor:pointer;font-family:inherit;">
                    <svg style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-3-3m3 3l3-3M4 20h16"/></svg>CSV
                </button>
            </div>
        </div>
        <div style="flex:1;overflow-y:auto;overflow-x:auto;scrollbar-width:thin;">
            <div id="bdLoading" style="display:none;text-align:center;padding:40px 20px;color:var(--text-muted);font-size:13px;">
                <div style="width:26px;height:26px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.7s linear infinite;margin:0 auto 10px;"></div>Memuat data...
            </div>
            <div id="bdEmpty" style="display:none;text-align:center;padding:40px 20px;"><div style="font-size:32px;margin-bottom:8px;">📭</div><div style="font-size:13px;color:var(--text-muted);">Tidak ada data ditemukan</div></div>
            <table id="bdTable" style="width:100%;border-collapse:collapse;font-size:12px;min-width:680px;">
                <thead style="position:sticky;top:0;z-index:2;background:var(--bg-elevated);">
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:center;width:36px;">#</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:left;">Nama</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:left;">Tujuan</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:left;">Keperluan</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Realisasi</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:center;">Tanggal</th>
                        <th style="padding:9px 12px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:center;">Sumber</th>
                    </tr>
                </thead>
                <tbody id="bdTbody"></tbody>
                <tfoot id="bdTfoot" style="position:sticky;bottom:0;background:var(--bg-elevated);border-top:2px solid var(--border);font-weight:600;"></tfoot>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:10px 20px;border-top:1px solid var(--border);flex-shrink:0;">
            <div style="font-size:11px;color:var(--text-muted);" id="bdPagerInfo"></div>
            <div style="display:flex;align-items:center;gap:5px;" id="bdPager"></div>
        </div>
    </div>
</div>


{{-- ── MODAL: RINGKASAN KAS ── --}}
<div id="kasDetailModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,0.25);width:100%;max-width:620px;max-height:88vh;display:flex;flex-direction:column;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:var(--accent-muted);">💼</span>
                <div>
                    <div id="kasModalTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);">Ringkasan Kas</div>
                    <div id="kasModalSubtitle" style="font-size:11px;color:var(--text-muted);margin-top:1px;"></div>
                </div>
            </div>
            <button onclick="closeKasModal()" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text-muted);">✕</button>
        </div>

        {{-- Table --}}
        <div style="flex:1;overflow-y:auto;overflow-x:auto;scrollbar-width:thin;">
            <div id="kasModalLoading" style="display:none;text-align:center;padding:40px 20px;color:var(--text-muted);font-size:13px;">
                <div style="width:26px;height:26px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.7s linear infinite;margin:0 auto 10px;"></div>Memuat data...
            </div>
            <div id="kasModalEmpty" style="display:none;text-align:center;padding:40px 20px;">
                <div style="font-size:32px;margin-bottom:8px;">📭</div>
                <div style="font-size:13px;color:var(--text-muted);">Tidak ada data tersedia</div>
            </div>
            <table id="kasModalTable" style="width:100%;border-collapse:collapse;font-size:12px;min-width:400px;">
                <thead style="position:sticky;top:0;z-index:2;background:var(--bg-elevated);">
                    <tr id="kasModalThead" style="border-bottom:1px solid var(--border);"></tr>
                </thead>
                <tbody id="kasModalTbody" style="color:var(--text-secondary);"></tbody>
                <tfoot id="kasModalTfoot" style="position:sticky;bottom:0;background:var(--bg-elevated);border-top:2px solid var(--border);font-weight:600;"></tfoot>
            </table>
        </div>

    </div>
</div>

{{-- ── MODAL: PILIH JENIS LAPORAN ── --}}
<div id="dashModalPilihLaporan" class="modal-backdrop">
    <div class="modal-box" style="max-width:300px;text-align:center;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Pilih Jenis Laporan</h2>
            <button class="modal-close" onclick="closeDashModal('dashModalPilihLaporan')">✕</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button id="dashBtnInputManual" class="btn btn-primary" style="justify-content:center;">📝 Input Manual</button>
            <button id="dashBtnUploadPDF" class="btn btn-ghost" style="justify-content:center;border-color:var(--accent);color:var(--accent);">📄 Upload PDF</button>
        </div>
    </div>
</div>

{{-- ── MODAL: UPLOAD LAPORAN ── --}}
<div id="dashModalUploadLaporan" class="modal-backdrop">
    <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Upload Laporan Kegiatan</h2>
            <button class="modal-close" onclick="closeDashModal('dashModalUploadLaporan')">✕</button>
        </div>
        <input type="hidden" id="dashLaporanTargetId">
        <div class="form-group">
            <label class="form-label">Dana Pengajuan</label>
            <input type="text" id="dashLaporanBiayaPengajuan" class="form-input" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Aktual Biaya</label>
            <input type="text" id="dashLaporanAktualBiaya" inputmode="numeric" class="form-input" placeholder="Masukkan aktual biaya" autocomplete="off" oninput="dashApplyRibuan(this);dashHitungSelisih()">
            <div id="dashLaporanSelisihBox" style="margin-top:8px;font-size:13px;display:none;"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Upload Laporan <span style="color:var(--text-muted);font-weight:400;">(PDF, maks. 5MB)</span></label>
            <input type="file" id="dashLaporanFileInput" accept="application/pdf" style="font-size:13px;color:var(--text-secondary);">
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="closeDashModal('dashModalUploadLaporan')">Batal</button>
            <button id="dashConfirmUpload" type="button" class="btn btn-primary">
                <span id="dashUploadText">Upload</span>
                <div id="dashUploadSpinner" class="spinner" style="display:none;"></div>
            </button>
        </div>
    </div>
</div>

<div id="dash-toast" style="position:fixed;bottom:70px;right:16px;z-index:99999;padding:10px 14px;border-radius:10px;font-size:12px;color:#fff;background:#111827;box-shadow:var(--shadow-lg);opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity 0.25s,transform 0.25s;max-width:280px;"></div>

{{-- ── JEMBATAN DATA DARI PHP KE dashboard.js ── --}}
<script>
window.DASHBOARD_DATA = {
    KEU: {
        tahunList:      @json($tahunTersedia ?? []),
        tahunAktif:     @json($tahunAktifTren ?? date('Y')),
        trenKeuUrl:     "{{ route('dashboard.tren-keuangan') }}",
        debitKreditUrl: "{{ route('dashboard.debit-kredit') }}",
        csrfToken:      "{{ csrf_token() }}",
    },
    DASH_USERNAME: @json(session('user.username')),
    DASH_ROLE: @json(session('user.role')),
    initialDashboardData: {
        persenRealisasi: @json($persenRealisasi),
        summary: {
            totalPengajuan:@json($totalPengajuan),aktualBiaya:@json($aktualBiaya),totalRealisasi:@json($totalRealisasi),
            belomLapor:@json($belomLapor),menunggu:@json($menunggu),disetujui:@json($disetujui),ditolak:@json($ditolak),
            totalBiaya:@json($totalBiaya),biayaMenunggu:@json($biayaMenunggu),biayaDisetujui:@json($biayaDisetujui),
            biayaDitolak:@json($biayaDitolak),totalBelumDilaporkan:@json($totalBelumDilaporkan),
        },
        rincian:@json($rincian),totalRow:@json($totalRow),
        growth:{total:@json($growthTotal ?? 0),approved:@json($growthApproved ?? 0),rejected:@json($growthRejected ?? 0)}
    },
    belumLaporData: @json($belumLaporGrouped ?? []),
    dashboardUrl: "{{ route('dashboard') }}",
    detailBidangUrl: "{{ route('dashboard.detail-bidang') }}",
    uploadLaporanUrl: "{{ route('pengajuan.uploadLaporan') }}",
    csrfToken: "{{ csrf_token() }}",
};
</script>

{{-- ── CSS & JS HALAMAN INI ── --}}
@vite(['resources/css/dashboard/dashboard.css', 'resources/js/dashboard/dashboard.js'])

@endsection
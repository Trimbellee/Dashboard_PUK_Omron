@extends('layouts.app')
@section('title', 'Preview Import')

@section('content')

@php
    $hasErrors    = count($previewErrors) > 0;
    $errorCount   = count($previewErrors);
    $errorRowNums = collect($previewErrors)
        ->map(fn ($e) => preg_match('/^Baris (\d+)/', $e, $m) ? (int)$m[1] : null)
        ->filter()->unique()->flip();
    $tableRows = $hasErrors
        ? $rows->filter(fn ($row) => $errorRowNums->has((int)($row['_excel_row'] ?? 0)))->values()
        : $rows->take(20);
@endphp

<div style="max-width:1200px;margin:0 auto;display:flex;flex-direction:column;gap:24px;animation:fadeUp 0.35s ease both;">

    {{-- ── PIPELINE STEPS ───────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;position:relative;">
        <div style="position:absolute;top:50%;left:0;right:0;height:2px;background:var(--border);z-index:0;transform:translateY(-50%);"></div>

        {{-- Step 1: Done --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span style="font-size:13px;font-weight:500;color:var(--accent-text);white-space:nowrap;">Upload File</span>
        </div>

        <div style="flex:1;height:2px;background:var(--accent);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 2: Active --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;box-shadow:0 0 0 4px var(--accent-muted);">2</span>
            <span style="font-size:13px;font-weight:600;color:var(--accent-text);white-space:nowrap;">Preview & Validasi</span>
        </div>

        <div style="flex:1;height:2px;background:var(--border);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 3: Next --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--bg-elevated);border:2px solid var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">3</span>
            <span style="font-size:13px;font-weight:500;color:var(--text-muted);white-space:nowrap;">Selesai</span>
        </div>
    </div>

    {{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
    <div>
        <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 4px;">Preview Import Anggota</h1>
        <p style="font-size:13px;color:var(--text-muted);margin:0;">Periksa data sebelum melanjutkan — perubahan bersifat permanen setelah diproses.</p>
    </div>

    {{-- ── STAT CARDS ───────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">

        {{-- Total Data --}}
        <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Total Data</span>
                <span style="width:32px;height:32px;border-radius:9px;background:var(--accent-muted);display:flex;align-items:center;justify-content:center;">
                    <svg style="width:15px;height:15px;color:var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
            </div>
            <div style="font-size:30px;font-weight:800;color:var(--text-primary);letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ number_format($totalRows) }}
            </div>
            @if($removedRows > 0)
                <div style="margin-top:8px;display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:500;color:#D97706;background:rgba(217,119,6,0.1);padding:3px 9px;border-radius:6px;border:1px solid rgba(217,119,6,0.2);">
                    <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
                    {{ number_format($removedRows) }} baris dilewati
                </div>
            @else
                <div style="margin-top:8px;font-size:11.5px;color:var(--text-muted);">semua baris terbaca</div>
            @endif
        </div>

        {{-- Error --}}
        @php $errColor = $hasErrors ? '#DC2626' : '#16A34A'; $errBg = $hasErrors ? 'rgba(220,38,38,0.08)' : 'rgba(22,163,74,0.08)'; $errBorder = $hasErrors ? 'rgba(220,38,38,0.2)' : 'rgba(22,163,74,0.2)'; @endphp
        <div style="background:var(--bg-surface);border:1px solid {{ $hasErrors ? 'rgba(220,38,38,0.3)' : 'rgba(22,163,74,0.3)' }};border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Error</span>
                <span style="width:32px;height:32px;border-radius:9px;background:{{ $errBg }};display:flex;align-items:center;justify-content:center;">
                    @if($hasErrors)
                        <svg style="width:15px;height:15px;color:#DC2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                    @else
                        <svg style="width:15px;height:15px;color:#16A34A;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </span>
            </div>
            <div style="font-size:30px;font-weight:800;color:{{ $errColor }};letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ $errorCount }}
            </div>
            <div style="margin-top:8px;font-size:11.5px;font-weight:500;color:{{ $errColor }};">
                {{ $hasErrors ? 'masalah ditemukan' : 'tidak ada error' }}
            </div>
        </div>

        {{-- Status --}}
        <div style="background:var(--bg-surface);border:1px solid {{ $hasErrors ? 'rgba(220,38,38,0.3)' : 'rgba(22,163,74,0.3)' }};border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Status</span>
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $hasErrors ? '#DC2626' : '#16A34A' }};box-shadow:0 0 0 3px {{ $hasErrors ? 'rgba(220,38,38,0.2)' : 'rgba(22,163,74,0.2)' }};"></span>
            </div>
            @if($hasErrors)
                <div style="font-size:17px;font-weight:700;color:#DC2626;margin-bottom:8px;">Tidak Siap</div>
                <div style="font-size:12px;color:var(--text-muted);line-height:1.6;">Perbaiki {{ $errorCount }} masalah di file Excel, lalu upload ulang.</div>
            @else
                <div style="font-size:17px;font-weight:700;color:#16A34A;margin-bottom:8px;">Siap Diproses</div>
                <div style="font-size:12px;color:var(--text-muted);line-height:1.6;">{{ number_format($totalRows) }} data siap disinkronisasi ke database.</div>
            @endif
        </div>

    </div>

    {{-- ── BANNER: BARIS DILEWATI ───────────────────────────────── --}}
    @if($removedRows > 0)
        <div style="display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:11px;background:rgba(217,119,6,0.08);border:1px solid rgba(217,119,6,0.25);font-size:13px;color:#92400E;">
            <svg style="width:16px;height:16px;flex-shrink:0;margin-top:1px;color:#D97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <span><strong>{{ number_format($removedRows) }} baris dilewati</strong> — terdeteksi sebagai baris kosong atau footer TOTAL/JUMLAH dan tidak akan diimport.</span>
        </div>
    @endif

    {{-- ── ERROR LIST ───────────────────────────────────────────── --}}
    @if($hasErrors)
        <div style="background:rgba(220,38,38,0.05);border:1px solid rgba(220,38,38,0.25);border-radius:14px;overflow:hidden;">
            <button id="errToggle" onclick="toggleErrList()" style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:none;border:none;cursor:pointer;text-align:left;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg style="width:15px;height:15px;color:#DC2626;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                    <span style="font-size:13px;font-weight:600;color:#DC2626;">{{ $errorCount }} masalah ditemukan — perbaiki file Excel lalu upload ulang</span>
                </div>
                <svg id="errChevron" style="width:15px;height:15px;color:#DC2626;transition:transform 0.2s;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="errList" style="border-top:1px solid rgba(220,38,38,0.2);padding:12px 18px;max-height:220px;overflow-y:auto;">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($previewErrors as $idx => $error)
                        <div style="display:flex;align-items:flex-start;gap:8px;font-size:12.5px;">
                            <span style="width:20px;height:20px;border-radius:5px;background:rgba(220,38,38,0.1);color:#DC2626;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px;">{{ $idx + 1 }}</span>
                            <span style="color:#B91C1C;line-height:1.5;">{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ── DATA TABLE ───────────────────────────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);">

        {{-- Table header bar --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $hasErrors ? '#DC2626' : '#16A34A' }};flex-shrink:0;"></span>
                <span style="font-size:13.5px;font-weight:600;color:var(--text-primary);">
                    {{ $hasErrors ? 'Baris Bermasalah' : 'Preview Data' }}
                </span>
                <span style="padding:2px 9px;border-radius:5px;font-size:11.5px;font-weight:500;background:var(--bg-elevated);color:var(--text-muted);border:1px solid var(--border);">
                    @if($hasErrors)
                        {{ $tableRows->count() }} baris
                    @else
                        20 dari {{ number_format($totalRows) }}
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:var(--text-muted);">
                <span style="display:inline-flex;align-items:center;padding:1px 6px;border-radius:4px;background:rgba(220,38,38,0.1);color:#DC2626;font-size:10px;font-weight:600;">W</span>
                kolom wajib
            </div>
        </div>

        {{-- Scroll wrapper --}}
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">

                <thead>
                    <tr style="background:var(--bg-elevated);border-bottom:1px solid var(--border);">
                        {{-- Row number --}}
                        <th style="padding:10px 14px;text-align:center;font-size:10.5px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--text-muted);white-space:nowrap;position:sticky;left:0;z-index:2;background:var(--bg-elevated);box-shadow:2px 0 5px rgba(0,0,0,0.05);">#</th>

                        @foreach($header as $col)
                            @php $isReq = in_array($col, ['nik_karyawan','nama_lengkap','departemen','jenis_kelamin','status_karyawan','jabatan']); @endphp
                            <th style="padding:10px 14px;text-align:left;font-size:10.5px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--text-muted);white-space:nowrap;">
                                <span style="display:inline-flex;align-items:center;gap:5px;">
                                    {{ $col }}
                                    @if($isReq)
                                        <span style="display:inline-flex;align-items:center;padding:1px 5px;border-radius:4px;background:rgba(220,38,38,0.1);color:#DC2626;font-size:10px;font-weight:600;">W</span>
                                    @endif
                                </span>
                            </th>
                        @endforeach

                        @if($hasErrors)
                            <th style="padding:10px 14px;text-align:left;font-size:10.5px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#DC2626;white-space:nowrap;min-width:200px;">Masalah</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach($tableRows as $i => $row)
                        @php
                            $excelRow   = (int) ($row['_excel_row'] ?? ($i + 2));
                            $rowIsError = $hasErrors && $errorRowNums->has($excelRow);
                            $rowErrors  = $hasErrors
                                ? collect($previewErrors)
                                    ->filter(fn ($e) => str_starts_with($e, "Baris {$excelRow}:"))
                                    ->map(fn ($e) => preg_replace('/^Baris \d+:\s*/', '', $e))
                                    ->values()
                                : collect();
                        @endphp

                        <tr style="border-bottom:1px solid var(--border);transition:background 0.1s;{{ $rowIsError ? 'background:rgba(220,38,38,0.04);' : '' }}"
                            onmouseover="this.style.background='{{ $rowIsError ? 'rgba(220,38,38,0.08)' : 'var(--bg-elevated)' }}'"
                            onmouseout="this.style.background='{{ $rowIsError ? 'rgba(220,38,38,0.04)' : 'transparent' }}'">

                            {{-- Row number --}}
                            <td style="padding:10px 14px;text-align:center;font-size:11.5px;font-family:'DM Mono',monospace;font-weight:{{ $rowIsError ? '700' : '400' }};color:{{ $rowIsError ? '#DC2626' : 'var(--text-muted)' }};position:sticky;left:0;z-index:1;background:{{ $rowIsError ? 'rgba(254,242,242,0.98)' : 'var(--bg-surface)' }};box-shadow:2px 0 5px rgba(0,0,0,0.04);">
                                {{ $excelRow }}
                            </td>

                            @foreach($header as $col)
                                @php
                                    $val = $row[$col] ?? null;
                                    if ($col === 'nik_karyawan' && is_numeric($val)) $val = (string)(int)$val;
                                    if ($col === 'foto' && $val) $val = basename($val);
                                    // Kolom seragam: tampilkan nilai yang AKAN disimpan (Ya/Tidak, ukuran baku).
                                    // Nilai tidak valid dibiarkan apa adanya — alasannya muncul di kolom "Masalah".
                                    if (in_array($col, ['sudah_punya_kaos', 'sudah_punya_kemeja']) && \App\Helpers\SeragamHelper::validYaTidak($val)) {
                                        $val = \App\Helpers\SeragamHelper::labelYaTidak(\App\Helpers\SeragamHelper::parseYaTidak($val));
                                    }
                                    if (in_array($col, ['ukuran_kaos', 'ukuran_kemeja']) && \App\Helpers\SeragamHelper::normalizeUkuran($val)) {
                                        $val = \App\Helpers\SeragamHelper::normalizeUkuran($val);
                                    }
                                    $isEmpty   = $val === null || trim((string)$val) === '';
                                    $isReq     = in_array($col, ['nik_karyawan','nama_lengkap','departemen','jenis_kelamin','status_karyawan','jabatan']);
                                    $cellError = $isEmpty && $isReq;
                                @endphp
                                <td style="padding:10px 14px;white-space:nowrap;color:{{ $cellError ? '#DC2626' : 'var(--text-secondary)' }};">
                                    @if($cellError)
                                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:600;color:#DC2626;background:rgba(220,38,38,0.08);padding:2px 8px;border-radius:5px;border:1px solid rgba(220,38,38,0.2);">
                                            <svg style="width:10px;height:10px;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            kosong
                                        </span>
                                    @elseif($isEmpty)
                                        <span style="color:var(--border-strong);user-select:none;">—</span>
                                    @elseif($col === 'foto')
                                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:500;color:var(--accent-text);background:var(--accent-muted);padding:2px 8px;border-radius:5px;">
                                            <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $val }}
                                        </span>
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            @endforeach

                            {{-- Error column --}}
                            @if($hasErrors)
                                <td style="padding:10px 14px;min-width:200px;">
                                    @if($rowErrors->isNotEmpty())
                                        <div style="display:flex;flex-direction:column;gap:5px;">
                                            @foreach($rowErrors as $errMsg)
                                                <div style="display:flex;align-items:flex-start;gap:6px;font-size:12px;color:#B91C1C;line-height:1.5;">
                                                    <svg style="width:11px;height:11px;flex-shrink:0;margin-top:2px;color:#DC2626;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                    {{ $errMsg }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        @if(!$hasErrors && $totalRows > 20)
            <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:var(--text-muted);">
                <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ number_format($totalRows - 20) }} data lainnya tidak ditampilkan di preview
            </div>
        @endif

    </div>

    {{-- ── ACTION BAR ───────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding-bottom:24px;">

        <a href="{{ route('anggota.import.form') }}" class="btn btn-ghost" style="text-decoration:none;gap:6px;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Upload Ulang
        </a>

        <form action="{{ route('anggota.import.process') }}" method="POST">
            @csrf
            @if($hasErrors)
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:12px;color:#DC2626;font-weight:500;">Selesaikan {{ $errorCount }} error terlebih dahulu</span>
                    <button type="button" disabled class="btn btn-ghost"
                        style="opacity:0.5;cursor:not-allowed;gap:6px;">
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Proses Import
                    </button>
                </div>
            @else
                <button type="submit" class="btn btn-primary" style="gap:8px;">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Proses Import
                    <span style="padding:1px 8px;border-radius:5px;background:rgba(255,255,255,0.2);font-size:11.5px;font-weight:700;font-variant-numeric:tabular-nums;">
                        {{ number_format($totalRows) }}
                    </span>
                </button>
            @endif
        </form>

    </div>

</div>

<script>
function toggleErrList() {
    const list    = document.getElementById('errList');
    const chevron = document.getElementById('errChevron');
    const isOpen  = list.style.display !== 'none';
    list.style.display    = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

@endsection
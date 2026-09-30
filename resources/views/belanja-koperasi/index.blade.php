@extends('layouts.app')
@section('title','Laporan Belanja Koperasi')

@section('content')
@php
    use Carbon\Carbon;
    $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');
    $isEditor  = in_array(session('user')['role'], ['Sekretaris','Bendahara']);
@endphp

<div id="belanjaKoperasiApp"
    data-is-editor="{{ $isEditor ? '1' : '0' }}"
    data-is-edit="{{ $isEdit ? '1' : '0' }}"
    data-existing="{{ json_encode($dataBelanja ?? []) }}"
    data-bidang-list="{{ json_encode($bidangList ?? []) }}"
    data-bulan="{{ $bulan }}"
    data-tahun="{{ $tahun }}"
    data-nama-bulan="{{ $namaBulan }}"
    data-simpan-url="{{ url('/belanja-koperasi/simpan') }}"
    data-back-url="{{ route('laporan-belanja.index') }}"
    data-csrf="{{ csrf_token() }}">

    {{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <button id="btnBackBelanja" type="button"
                class="btn btn-ghost" style="padding:6px 12px;font-size:12.5px;gap:6px;margin-bottom:8px;">
                ← Kembali
            </button>
            <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 2px;">Belanja Koperasi</h1>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        @if($isEditor)
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="file" id="excelBelanja" accept=".xlsx,.xls"
                    style="font-size:12.5px;color:var(--text-secondary);">
                <button type="button" id="btnImportExcel" class="btn btn-ghost" style="gap:6px;border-color:rgba(22,163,74,0.4);color:#16A34A;padding:7px 14px;">
                    📥 Import Excel
                </button>
            </div>
            <button type="button" id="btnSimpanLaporan" class="btn btn-primary" style="gap:8px;">
                <div id="spinnerSimpan" class="spinner" style="display:none;"></div>
                <span id="textSimpan">{{ $isEdit ? 'Update Laporan' : 'Simpan Laporan' }}</span>
            </button>
        </div>
        @endif
    </div>

    {{-- ── TABLE ────────────────────────────────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm);overflow-x:auto;">
        <table id="tblBelanjaKoperasi" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:var(--bg-elevated);border-bottom:1px solid var(--border);">
                    <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);width:120px;">Tanggal</th>
                    <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Bidang</th>
                    <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Keperluan</th>
                    <th style="padding:11px 14px;text-align:right;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Biaya</th>
                    @if($isEditor)
                    <th style="padding:11px 14px;width:40px;"></th>
                    @endif
                </tr>
            </thead>
            <tbody id="bkTbody">
                <tr class="bk-row">
                    <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                        <input type="date" class="bk-tanggal bk-input" autocomplete="off">
                    </td>
                    <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
                        <input type="text" class="bk-bidang bk-input" autocomplete="off">
                    </td>
                    <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                        <input type="text" class="bk-keperluan bk-input" autocomplete="off">
                    </td>
                    <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                        <input type="text" inputmode="numeric" value="0" class="bk-biaya bk-input" style="text-align:right;" autocomplete="off">
                    </td>
                    @if($isEditor)
                    <td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
                        <button type="button" class="bk-remove-row" style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                    </td>
                    @endif
                </tr>
            </tbody>
            <tfoot style="position:sticky;bottom:0;background:var(--bg-surface);border-top:1px solid var(--border);">
                <tr>
                    <td colspan="3" style="padding:10px 14px;text-align:right;font-size:12.5px;font-weight:600;color:var(--text-secondary);">TOTAL</td>
                    <td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:700;color:var(--text-primary);" id="totalBelanjaKoperasi">0</td>
                    @if($isEditor)
                    <td></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>

    @if($isEditor)
    <button type="button" id="btnTambahBaris"
        class="btn btn-ghost" style="margin-top:10px;padding:6px 14px;font-size:12.5px;gap:6px;">
        ＋ Tambah Baris
    </button>
    @endif

    {{-- Toast wrap --}}
    <div id="toastWrap"
        style="position:fixed;top:80px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;z-index:999999;pointer-events:none;min-width:280px;max-width:380px;"></div>
</div>

@vite(['resources/css/dashboard/belanja-koperasi.css', 'resources/js/dashboard/belanja-koperasi.js'])

@endsection

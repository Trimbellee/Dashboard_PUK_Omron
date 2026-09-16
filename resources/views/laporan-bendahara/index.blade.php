@extends('layouts.app')
@section('title','Laporan Bendahara')

@section('content')
<div id="desktopAutoScale" class="bg-white text-black">
@php
$laporan = $laporan ?? [];

/* meta */
$meta = $laporan['meta'] ?? [
    'bulan' => now()->month,
    'tahun' => now()->year,
    'is_edit' => false,
    'status' => 'draft',
];

/* data utama */
$ringkasanKas = $laporan['ringkasan_kas'] ?? [];
$perBidang    = $laporan['per_bidang'] ?? [];
$danaLain     = $laporan['dana_lain'] ?? [];
$danaSumbangan = $laporan['dana_sumbangan'] ?? [];
$insentifSekretaris = $laporan['insentif_sekretaris'] ?? [];

/* raw saved */
$savedRaw = $laporan['saved_raw'] ?? [];

$saldoAwalRaw    = $savedRaw['saldo_awal'] ?? [];
$debitManualRaw  = $savedRaw['debit_manual'] ?? [];
$kreditManualRaw = $savedRaw['kredit_manual'] ?? [];
$cosRaw          = $savedRaw['cos'] ?? 0;

$isEdit = $meta['is_edit'];

/* ==========================================
   WORKFLOW STATE
========================================== */
$statusOrder = ['draft', 'menunggu_sekretaris', 'menunggu_ketua', 'selesai'];
$currentStatus = $meta['status'] ?? 'draft';
$currentStepIdx = array_search($currentStatus, $statusOrder);
if ($currentStepIdx === false) { $currentStepIdx = 0; }

// hanya bisa diedit saat draft — status lain jadi mode baca-saja
$userRole = session('user')['role'] ?? null;
$readOnly = $currentStatus !== 'draft' || !in_array($userRole, ['Bendahara', 'Sekretaris']);

$statusLabel = [
    'draft'                => 'Draft',
    'menunggu_sekretaris'  => 'Menunggu Sekretaris',
    'menunggu_ketua'       => 'Menunggu Ketua',
    'selesai'              => 'Selesai',
][$currentStatus] ?? 'Draft';
@endphp

@php
$userRole    = session('user')['role'] ?? null;
$canApprove  = ($userRole === 'Sekretaris' && $currentStatus === 'menunggu_sekretaris')
            || ($userRole === 'Ketua' && $currentStatus === 'menunggu_ketua');
$approveLabel = $userRole === 'Sekretaris' ? 'Setujui' : 'Approve';
$canReopen = strtolower($userRole) === 'ketua' && strtolower($currentStatus) === 'selesai';
@endphp

<div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
        @if(!$readOnly)
        <button
            type="button"
            id="btnSimpanLaporan"
            onclick="handleSimpanLaporan()"
            class="px-4 py-2 bg-blue-600 text-white rounded flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">

            <svg id="spinnerSimpan"
                class="hidden animate-spin h-4 w-4 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>

            <span id="textSimpan">
                {{ $isEdit ? 'Update Laporan' : 'Simpan Laporan' }}
            </span>

        </button>
        @elseif($canApprove)
        <button
            type="button"
            id="btnSetujuiLaporan"
            onclick="handleApproveLaporan()"
            class="px-4 py-2 bg-purple-600 text-white rounded flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">

            <svg id="spinnerSetujui"
                class="hidden animate-spin h-4 w-4 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>

            <span id="textSetujui">{{ $approveLabel }}</span>
        </button>

        <button
            type="button"
            id="btnTolakLaporan"
            onclick="openModalTolakLaporan()"
            class="px-4 py-2 bg-red-600 text-white rounded flex items-center gap-2">
            Tolak
        </button>
        @elseif($canReopen)
        <button
            type="button"
            id="btnBukaKembali"
            onclick="openModalBukaKembali()"
            class="px-4 py-2 bg-amber-600 text-white rounded flex items-center gap-2">
            <i class="ti ti-lock-open" style="font-size:16px;"></i>
            Buka Kembali untuk Revisi
        </button>
        @else
        <span class="px-4 py-2 rounded bg-gray-100 text-gray-500 text-sm inline-flex items-center gap-2">
            <i class="ti ti-lock" style="font-size:16px;"></i>
            Laporan berstatus "{{ $statusLabel }}" — tidak dapat diedit
        </span>
        @endif
    </div>

    <button type="button" onclick="handleBackButton()" class="text-blue-600 text-sm me-5">
        ← Kembali&nbsp;&nbsp;&nbsp;&nbsp;
    </button>
</div>

<div id="progressBox" class="hidden mt-3">
    <div class="text-xs mb-1" id="progressText">Menyiapkan…</div>
    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
        <div id="progressBar"
             class="h-2 bg-blue-600 transition-all duration-300"
             style="width:0%">
        </div>
    </div>
</div>

<!-- =======================
  STEPPER ALUR PERSETUJUAN
======================= -->
<div class="flex items-center mb-6" style="max-width:640px;margin:24px auto;">
    @foreach(['Bendahara','Sekretaris','Ketua','Selesai'] as $i => $label)
        @php
            $isDone = $i < $currentStepIdx || $currentStatus === 'selesai';
            $isActive = $i === $currentStepIdx && $currentStatus !== 'selesai';
        @endphp
        <div class="flex flex-col items-center flex-1" style="position:relative;">
            @if($i > 0)
                <div style="position:absolute;top:16px;right:50%;width:100%;height:2px;z-index:0;
                    background:{{ $isDone || $isActive ? '#2563eb' : '#d1d5db' }};"></div>
            @endif
            <div class="rounded-full flex items-center justify-center text-xs font-semibold"
                style="width:32px;height:32px;position:relative;z-index:1;
                background:{{ $isDone ? '#16a34a' : ($isActive ? '#2563eb' : '#e5e7eb') }};
                color:{{ $isDone || $isActive ? '#fff' : '#6b7280' }};">
                {{ $isDone ? '✓' : ($i+1) }}
            </div>
            <span class="text-xs mt-1" style="{{ $isActive ? 'font-weight:600;' : 'color:#6b7280;' }}">
                {{ $label }}
            </span>
        </div>
    @endforeach
</div>

<!-- =======================
  BANNER CATATAN REVISI / PENOLAKAN
======================= -->
@if($currentStatus === 'draft' && !empty($meta['catatan_revisi']))
<div class="mb-6 p-4 rounded border-2" style="max-width:900px;margin:0 auto 24px;
     border-color:#fca5a5;background:#fef2f2;">
    <div class="font-semibold text-red-800 flex items-center gap-2">
        <i class="ti ti-alert-triangle" style="font-size:16px;"></i>
        Laporan dikembalikan untuk revisi
    </div>
    <div class="text-sm text-red-700 mt-1">
        Ditolak oleh <strong>{{ $meta['ditolak_role'] ?? '' }}</strong>
        ({{ $meta['ditolak_oleh'] ?? '-' }})
        @if(!empty($meta['ditolak_at']))
            &middot; {{ \Carbon\Carbon::parse($meta['ditolak_at'])->translatedFormat('d M Y, H:i') }}
        @endif
    </div>
    <div class="text-sm text-red-800 mt-2 italic">
        "{{ $meta['catatan_revisi'] }}"
    </div>
</div>
@endif

@if($currentStatus === 'draft' && !empty($meta['dibuka_kembali_alasan']))
<div class="mb-6 p-4 rounded border-2" style="max-width:900px;margin:0 auto 24px;
     border-color:#fbbf24;background:#fffbeb;">
    <div class="font-semibold text-amber-800 flex items-center gap-2">
        <i class="ti ti-lock-open" style="font-size:16px;"></i>
        Laporan dibuka kembali oleh Ketua
    </div>
    <div class="text-sm text-amber-700 mt-1">
        Oleh <strong>{{ $meta['dibuka_kembali_oleh'] ?? '-' }}</strong>
        @if(!empty($meta['dibuka_kembali_at']))
            &middot; {{ \Carbon\Carbon::parse($meta['dibuka_kembali_at'])->translatedFormat('d M Y, H:i') }}
        @endif
    </div>
    <div class="text-sm text-amber-800 mt-2 italic">
        "{{ $meta['dibuka_kembali_alasan'] }}"
    </div>
    <div class="text-xs text-amber-700 mt-2">
        Laporan ini harus melalui persetujuan Sekretaris dan Ketua kembali setelah direvisi.
    </div>
</div>
@endif

<!-- =======================
  JUDUL
======================= -->
<div class="text-center mb-6">
    <h1 class="font-bold text-lg uppercase">LAPORAN BENDAHARA</h1>
    <h2 class="uppercase">
        BULAN {{ strtoupper(\Carbon\Carbon::create()->month($meta['bulan'])->translatedFormat('F')) }}
        {{ $meta['tahun'] }}
    </h2>
</div>

<fieldset id="fieldsetLaporan" {{ $readOnly ? 'disabled' : '' }} style="border:none;padding:0;margin:0;">

<div class="font-semibold">
        COS :
        <input id="cos"
        value="{{ number_format(
            $isEdit
                ? ($cosRaw ?? 0)
                : 0
        ,0,',','.') }}"
       inputmode="numeric"
       class="border px-2 py-1 text-right w-48">
</div>
<!-- =======================
  RINGKASAN KAS
======================= -->
<table class="w-full border mb-8" id="tblRingkasanKas">
<thead class="bg-gray-200">
<tr>
    <th style="width:5%">No.</th>
    <th style="width:27%">Keterangan</th>
    <th style="width:17%">Saldo Awal</th>
    <th style="width:17%">Debit</th>
    <th style="width:17%">Kredit</th>
    <th style="width:17%">Total</th>
</tr>
</thead>
<tbody>

@foreach($ringkasanKas as $r)
<tr>
    <td class="text-center">{{ $r['no'] }}</td>
    <td>{{ strtoupper($r['jenis']) }}</td>

    <!-- SALDO AWAL -->
    <td>
        @php
            $disableSaldoAwal = in_array($r['jenis'], [
                'COS DPP',
                'Biaya Transfer'
            ]);
        @endphp

        @if($disableSaldoAwal)
            <span class="block text-right">0</span>
        @else
          <input class="saldo-awal w-full text-right" 
          data-key="{{ $r['jenis'] }}" 
          value="{{ number_format($saldoAwalRaw[$r['jenis']] ?? 0,0,',','.') }}">
           @endif

    </td>

    <!-- DEBIT -->
    <td>
      @php
          $autoDebit = in_array($r['jenis'], [
              'Dana Operasional',
              'Dana Perjuangan',
              'Dana Sosial',
              'Dana Permusyawaratan',
              'Dana Sosial Politik'
          ]);

      @endphp

      @if($autoDebit)
          <span class="debit block text-right"
                data-key="{{ $r['jenis'] }}">0</span>

      @else
          <input class="debit-manual w-full text-right"
            data-key="{{ $r['jenis'] }}"
            value="{{ number_format($debitManualRaw[$r['jenis']] ?? 0,0,',','.') }}"> 
      @endif

  </td>


    <!-- KREDIT -->
    <td>
        @php
            $autoKredit = in_array($r['jenis'], [
                'Dana Operasional',
                'Dana Perjuangan',
                'Dana Sosial',
                'Dana Permusyawaratan',
                'Dana Sosial Politik',
                'Bunga Giro',
                'Bunga Deposito',

            ]);

            $manualKredit = in_array($r['jenis'], [
                'COS DPP',
                'Biaya Transfer'
            ]);
        @endphp

        @if($autoKredit)
           <span class="kredit-auto block text-right"
                  data-key="{{ $r['jenis'] }}"
                  data-base="{{ $r['kredit'] ?? 0 }}">
                {{ number_format($r['kredit'] ?? 0,0,',','.') }}
           </span>


        @elseif($manualKredit)
            <input class="kredit-manual w-full text-right"
            data-key="{{ $r['jenis'] }}"
            value="{{ number_format($kreditManualRaw[$r['jenis']] ?? 0,0,',','.') }}">
        @endif


    </td>

    <!-- TOTAL -->
    <td class="text-right font-semibold">
        <span class="total" data-key="{{ $r['jenis'] }}">0</span>
    </td>
</tr>
@endforeach

<tr class="font-bold bg-green-100">
    <td colspan="2" class="text-center">TOTAL</td>
    <td class="text-right" id="grandSaldo">0</td>
    <td class="text-right" id="grandDebit">0</td>
    <td class="text-right" id="grandKredit">0</td>
    <td class="text-right" id="grandTotal">0</td>
</tr>

</tbody>
</table>


<!-- =======================
  RINCIAN PER BIDANG
======================= -->
<div class="text-center mb-6">
    <h2 class= "font-bold uppercase">rincian pengeluaran</h2>
        </div>
@php
$urutanBidang = [
    'Sekretaris',
    'Bidang 1',
    'Bidang 2',
    'Bidang 3',
    'Bidang 4',
    'Bidang 5',
    'Bidang 6',
    'GM',
    'KorLap',
    'Dana Sosial',
    'Dana Permusyawaratan',
    'Dana Sosial Politik',
    'Dana Perjuangan',
    'Bunga Giro',
    'Bunga Deposito'
];

/* NORMALISASI DATA BACKEND */
$perBidangNormalized = [];
foreach($perBidang as $k => $v){
    $perBidangNormalized[strtoupper(trim($k))] = $v;
}
@endphp
@php
$insentifSekretaris = $laporan['insentif_sekretaris'] ?? [];
@endphp

@foreach($urutanBidang as $bidang)
    @php
        $key = strtoupper(trim($bidang));
    @endphp

    @if(isset($perBidangNormalized[$key]) || $key === 'SEKRETARIS' || $key === 'BUNGA GIRO')
    @php
        $items = $perBidangNormalized[$key] ?? [];
        if($key === 'BUNGA GIRO' && empty($items)){
            $items = [];
        }
        $totalBidang = 0;
    @endphp

    <h3 class="font-bold uppercase mt-6 mb-2">{{ $bidang }}</h3>

    <table class="w-full border mb-4">
        <thead>
            <tr>
                <th style="width:15%">Tanggal</th>
                <th style="width:25%">Tujuan</th>
                <th style="width:43%">Keperluan</th>
                <th style="width:17%">Realisasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
                @php $totalBidang += $row['aktual']; @endphp
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['tujuan'] }}</td>
                    <td>{{ $row['keperluan'] ?? '-' }}</td>
                    <td class="text-right">
                        {{ number_format($row['aktual'],0,',','.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center italic text-gray-400">
                        Tidak ada pengeluaran
                    </td>
                </tr>
            @endforelse
            @if($key === 'BUNGA GIRO')
            <tr class="pajak-row">
                <td>
                    {{ \Carbon\Carbon::create()->month($meta['bulan'])->translatedFormat('M') }} {{ $meta['tahun'] }}
                </td>
                <td>Bank</td>
                <td>Pajak Bank</td>
                <td class="text-right">
                    <input type="text"
                    name="pajak_bank" class="pajak-giro text-right w-full"
                    inputmode="numeric"
                    value="{{ number_format($laporan['pajak_bank'] ?? 0,0,',','.') }}">
                </td>
            </tr>
            @endif
            
                @if($key === 'SEKRETARIS')
                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="4">INSENTIF SEKRETARIAT</td>
                    </tr>

                    @foreach($insentifSekretaris as $i => $ins)
                    <tr class="insentif-row">
                        <td>-</td>
                        <td>{{ $ins['tujuan'] }}</td>
                        <td>{{ $ins['keperluan'] }}</td>
                        <td class="text-right">
                            <input type="text"
                                class="insentif-nominal text-right w-full"
                                data-bidang="SEKRETARIS"
                                data-index="{{ $i }}"
                                value="{{ number_format($ins['nominal'],0,',','.') }}">
                        </td>
                    </tr>
                    @endforeach
                @endif

                <tr class="font-bold">
                    <td colspan="3" class="text-right">TOTAL</td>
                    <td class="text-right total-bidang"
                        data-bidang="{{ $key }}"
                        data-base="{{ $totalBidang }}">
                        {{ number_format($totalBidang,0,',','.') }}
                    </td>

                </tr>
            </tbody>
        </table>
    @endif
@endforeach

<!-- =======================
  DANA LAIN-LAIN
======================= -->
<h3 class="font-bold uppercase mt-8 mb-2">Pengeluaran Dana Operasional Lainnya</h3>

<table class="w-full border mb-4 text-sm" id="tblDanaLain">
<thead class="bg-gray-200">
<tr>
    <th width="15%">Bidang</th>
    <th width="20%">Tujuan</th>
    <th>Keperluan</th>
    <th width="15%">Nominal</th>
    <th width="7%">Hapus</th>
</tr>
</thead>
<tbody>

@if(count($danaLain))
    @foreach($danaLain as $i => $d)
    <tr>
        <td>
            <input type="text"
                   name="dana_lain[{{ $i }}][bidang]"
                   class="dana-bidang w-full"
                   value="{{ $d['bidang'] ?? '' }}">
        </td>
        <td>
            <input type="text"
                   name="dana_lain[{{ $i }}][tujuan]"
                   class="dana-tujuan w-full"
                   value="{{ $d['tujuan'] ?? '' }}">
        </td>
        <td>
            <input type="text"
                   name="dana_lain[{{ $i }}][keperluan]"
                   class="dana-keperluan w-full"
                   value="{{ $d['keperluan'] ?? '' }}">
        </td>
        <td>
            <input type="text"
                   inputmode="numeric"
                   name="dana_lain[{{ $i }}][nominal]"
                   class="dana-lain-nominal w-full text-right"
                   value="{{ number_format($d['nominal'] ?? 0,0,',','.') }}">
        </td>
        <td class="text-center">
            <button class="inline-flex items-center justify-center w-6 h-6 bg-red-600 text-white rounded-md font-bold text-sm leading-none" type="button" onclick="removeDanaLain(this)">✕</button>
        </td>
    </tr>
    @endforeach
@else
    <tr>
        <td>
            <input type="text" name="dana_lain[0][bidang]" class="dana-bidang w-full">
        </td>
        <td>
            <input type="text" name="dana_lain[0][tujuan]" class="dana-tujuan w-full">
        </td>
        <td>
            <input type="text" name="dana_lain[0][keperluan]" class="dana-keperluan w-full">
        </td>
        <td>
            <input type="text"
                   inputmode="numeric"
                   name="dana_lain[0][nominal]"
                   class="dana-lain-nominal w-full text-right"
                   value="0">
        </td>
        <td class="text-center">
            <button class="inline-flex items-center justify-center
             w-6 h-6 bg-red-600 text-white
             rounded-md font-bold text-sm leading-none" type="button" onclick="removeDanaLain(this)">✕</button>
        </td>
    </tr>
@endif

</tbody>

<tfoot class="font-bold bg-gray-100">
<tr>
    <td colspan="3" class="text-right">TOTAL</td>
    <td class="text-right" id="totalDanaLain">0</td>
    <td></td>
</tr>
</tfoot>
</table>

<button type="button"
        onclick="addDanaLain()"
        class="px-3 py-1 border rounded">
+ Tambah Baris
</button>

<!-- =======================
  DANA SUMBANGAN
======================= -->
<h3 class="font-bold uppercase mt-8 mb-2">Dana Sumbangan</h3>

<table class="w-full border mb-4 text-sm" id="tblDanaSumbangan">
<thead class="bg-gray-200">
<tr>
    <th width="18%">Donatur</th>
    <th width="18%">Bidang</th>
    <th width="18%">Tujuan</th>
    <th>Keperluan</th>
    <th width="15%">Nominal</th>
    <th width="7%">Hapus</th>
</tr>
</thead>
<tbody>

@if(count($danaSumbangan))
    @foreach($danaSumbangan as $i => $d)
    <tr>
        <td>
            <input type="text"
                   name="dana_sumbangan[{{ $i }}][donatur]"
                   class="sumbangan-donatur w-full"
                   value="{{ $d['donatur'] ?? '' }}">
        </td>

        <td>
            <select
                name="dana_sumbangan[{{ $i }}][bidang]"
                class="sumbangan-bidang w-full">

                @php
                    $opsiBidang = [
                        'Dana Operasional',
                        'Dana Perjuangan',
                        'Dana Sosial',
                        'Dana Permusyawaratan',
                        'Dana Sosial Politik'
                    ];
                @endphp

                <option value="">-- Pilih --</option>

                @foreach($opsiBidang as $opsi)
                    <option value="{{ $opsi }}"
                        {{ ($d['bidang'] ?? '') === $opsi ? 'selected' : '' }}>
                        {{ $opsi }}
                    </option>
                @endforeach
            </select>
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[{{ $i }}][tujuan]"
                   class="sumbangan-tujuan w-full"
                   value="{{ $d['tujuan'] ?? '' }}">
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[{{ $i }}][keperluan]"
                   class="sumbangan-keperluan w-full"
                   value="{{ $d['keperluan'] ?? '' }}">
        </td>

        <td>
            <input type="text"
                   inputmode="numeric"
                   name="dana_sumbangan[{{ $i }}][nominal]"
                   class="sumbangan-nominal w-full text-right"
                   value="{{ number_format($d['nominal'] ?? 0,0,',','.') }}">
        </td>

        <td class="text-center">
            <button
                type="button"
                onclick="removeDanaSumbangan(this)"
                class="inline-flex items-center justify-center
                       w-6 h-6 bg-red-600 text-white
                       rounded-md font-bold text-sm leading-none">
                ✕
            </button>
        </td>
    </tr>
    @endforeach
@else
    <tr>
        <td>
            <input type="text"
                   name="dana_sumbangan[0][donatur]"
                   class="sumbangan-donatur w-full">
        </td>

        <td>
            <select
                name="dana_sumbangan[0][bidang]"
                class="sumbangan-bidang w-full">

                <option value="">-- Pilih --</option>
                <option value="Dana Operasional">Dana Operasional</option>
                <option value="Dana Perjuangan">Dana Perjuangan</option>
                <option value="Dana Sosial">Dana Sosial</option>
                <option value="Dana Permusyawaratan">Dana Permusyawaratan</option>
                <option value="Dana Sosial Politik">Dana Sosial Politik</option>

            </select>
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[0][tujuan]"
                   class="sumbangan-tujuan w-full">
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[0][keperluan]"
                   class="sumbangan-keperluan w-full">
        </td>

        <td>
            <input type="text"
                   inputmode="numeric"
                   name="dana_sumbangan[0][nominal]"
                   class="sumbangan-nominal w-full text-right"
                   value="0">
        </td>

        <td class="text-center">
            <button
                type="button"
                onclick="removeDanaSumbangan(this)"
                class="inline-flex items-center justify-center
                       w-6 h-6 bg-red-600 text-white
                       rounded-md font-bold text-sm leading-none">
                ✕
            </button>
        </td>
    </tr>
@endif

</tbody>

<tfoot class="font-bold bg-gray-100">
<tr>
    <td colspan="4" class="text-right">TOTAL</td>
    <td class="text-right" id="totalDanaSumbangan">0</td>
    <td></td>
</tr>
</tfoot>
</table>

<button type="button"
        onclick="addDanaSumbangan()"
        class="px-3 py-1 border rounded">
+ Tambah Baris
</button>

</fieldset>

{{-- ── MODAL TOLAK LAPORAN ─────────────────────────────────── --}}
<div id="modalTolakLaporan"
    style="position:fixed;inset:0;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);display:none;align-items:center;
           justify-content:center;z-index:8100;padding:20px;">

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;
                box-shadow:0 20px 50px rgba(0,0,0,.2);
                width:100%;max-width:420px;padding:28px;"
         onclick="event.stopPropagation()">

        <div style="display:flex;align-items:center;justify-content:space-between;
                    margin-bottom:16px;">
            <h2 style="font-size:1rem;font-weight:600;color:#991b1b;margin:0;">
                Tolak & Minta Revisi
            </h2>
            <button onclick="closeModalTolakLaporan()"
                style="width:28px;height:28px;border-radius:7px;
                       border:1px solid #e5e7eb;background:none;
                       cursor:pointer;font-size:14px;color:#6b7280;
                       display:flex;align-items:center;justify-content:center;">
                ✕
            </button>
        </div>

        <p style="font-size:0.8125rem;color:#6b7280;margin:0 0 12px;">
            Laporan akan dikembalikan ke Bendahara berstatus draft.
            Jelaskan apa yang perlu diperbaiki agar revisi lebih cepat.
        </p>

        <form id="formTolakLaporan">
            <div style="margin-bottom:16px;">
                <label style="font-size:0.8125rem;font-weight:600;display:block;margin-bottom:6px;">
                    Catatan revisi <span style="color:#dc2626;">*</span>
                </label>
                <textarea name="catatan" id="catatanTolakLaporan" required rows="4"
                    style="width:100%;border:1px solid #d1d5db;border-radius:10px;
                           padding:10px;resize:vertical;font-size:0.8125rem;"
                    placeholder="Contoh: Saldo awal Dana Sosial belum sesuai, mohon dicek ulang."></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeModalTolakLaporan()"
                    style="padding:8px 16px;border-radius:8px;border:1px solid #d1d5db;
                           background:#fff;color:#374151;cursor:pointer;">
                    Batal
                </button>
                <button type="submit" id="btnKirimTolakLaporan"
                    style="padding:8px 16px;border-radius:8px;border:none;
                           background:#dc2626;color:#fff;cursor:pointer;
                           display:flex;align-items:center;gap:8px;">
                    <svg id="spinTolakLaporan"
                        class="hidden animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="textTolakLaporan">Kirim Penolakan</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL BUKA KEMBALI ─────────────────────────────────── --}}
<div id="modalBukaKembali"
    style="position:fixed;inset:0;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);display:none;align-items:center;
           justify-content:center;z-index:8100;padding:20px;">

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;
                box-shadow:0 20px 50px rgba(0,0,0,.2);
                width:100%;max-width:420px;padding:28px;"
         onclick="event.stopPropagation()">

        <div style="display:flex;align-items:center;justify-content:space-between;
                    margin-bottom:16px;">
            <h2 style="font-size:1rem;font-weight:600;color:#92400e;margin:0;">
                Buka Kembali Laporan
            </h2>
            <button onclick="closeModalBukaKembali()"
                style="width:28px;height:28px;border-radius:7px;
                       border:1px solid #e5e7eb;background:none;
                       cursor:pointer;font-size:14px;color:#6b7280;
                       display:flex;align-items:center;justify-content:center;">
                ✕
            </button>
        </div>

        <p style="font-size:0.8125rem;color:#6b7280;margin:0 0 12px;">
            Laporan yang sudah <strong>Selesai</strong> akan dikembalikan ke status draft.
            Bendahara harus revisi, dan laporan wajib disetujui ulang oleh Sekretaris & Ketua.
        </p>

        <form id="formBukaKembali">
            <div style="margin-bottom:16px;">
                <label style="font-size:0.8125rem;font-weight:600;display:block;margin-bottom:6px;">
                    Alasan membuka kembali <span style="color:#dc2626;">*</span>
                </label>
                <textarea name="alasan" id="alasanBukaKembali" required rows="4"
                    style="width:100%;border:1px solid #d1d5db;border-radius:10px;
                           padding:10px;resize:vertical;font-size:0.8125rem;"
                    placeholder="Contoh: Ditemukan kesalahan input pada Dana Sosial bulan ini."></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeModalBukaKembali()"
                    style="padding:8px 16px;border-radius:8px;border:1px solid #d1d5db;
                           background:#fff;color:#374151;cursor:pointer;">
                    Batal
                </button>
                <button type="submit" id="btnKirimBukaKembali"
                    style="padding:8px 16px;border-radius:8px;border:none;
                           background:#d97706;color:#fff;cursor:pointer;
                           display:flex;align-items:center;gap:8px;">
                    <svg id="spinBukaKembali"
                        class="hidden animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="textBukaKembali">Buka Kembali</span>
                </button>
            </div>
        </form>
    </div>
</div>

@include('laporan-bendahara.partials.riwayat_laporan')

@include('laporan-bendahara.partials.riwayat_arsip')

<!-- =======================
  STYLE
======================= -->
<style>

body {
    background: #ffffff !important;
    color: #000000 !important;
}

#desktopAutoScale {
    background: #ffffff;
}

#desktopAutoScale{
    width:1200px;
    min-width:1200px;
    transform-origin: top left;
    margin:auto;
}

html, body{
    overflow:auto;
    scrollbar-width:none;
    -ms-overflow-style:none;
}

html::-webkit-scrollbar,
body::-webkit-scrollbar{
    display:none;
}

table{
    border-collapse:collapse;
    width:100%;
    table-layout:fixed;
}

th,td{
    border:1px solid #000;
    padding:4px;
    word-wrap:break-word;
    overflow-wrap:break-word;
    white-space:normal;
    vertical-align:top;
}

td.text-right{
    white-space:nowrap;
}

.dark table{
    background:#1e293b;
    color:#e2e8f0;
}

.dark th,
.dark td{
    border:1px solid #475569;
}

.dark thead{
    background:#334155;
}

.dark .bg-gray-200{
    background:#334155 !important;
}

.dark .bg-green-100{
    background:#14532d !important;
}

input{
    border:none;
    background:transparent;
    color:inherit;
    min-width:0;
}

input:focus{
    outline:none;
}

fieldset:disabled{
    opacity:.65;
}

fieldset:disabled input,
fieldset:disabled select,
fieldset:disabled button{
    cursor:not-allowed;
}

@page{
    margin:20mm;
}

body{
    font-family:"Times New Roman",serif;
    font-size:11pt;
}

#toastWrap > div{
    pointer-events:auto;
}

@keyframes toastDrop{
    from{
        opacity:0;
        transform:translateY(-20px) scale(.95);
    }
    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.animate-fadeIn{
    animation:toastDrop .25s ease;
}

</style>
<script>
function setProgress(percent, text){
    document.getElementById('progressBox').classList.remove('hidden');
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressText').innerText = text;
}
</script>

<script>
const bulan  = @json($meta['bulan']);
const tahun  = @json($meta['tahun']);
const isEdit = @json($isEdit);
const isReadOnly = @json($readOnly);
</script>

<script>

let kasRows = [];
let danaLainInputs = [];
let insentifInputs = [];

let recalcTimer = null;

const PER_BIDANG_TOTALS = @json(
    collect($perBidangNormalized)->map(fn($rows)=>collect($rows)->sum('aktual'))
);

function parseID(val){
    if(val == null) return 0;

    return Number(
        val.toString()
            .replace(/[^\d\-]/g,'')
    ) || 0;
}

function formatID(val){
    const floored = Math.floor(val || 0);
    return floored.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}

function formatInput(el){
    const raw = parseID(el.value);
    el.value = formatID(raw);
}

function hitungDanaLain(){
    let total = 0;
    document.querySelectorAll('.dana-lain-nominal').forEach(el => {
        total += parseID(el.value);
    });
    document.getElementById('totalDanaLain').innerText = formatID(total);
    return total;
}

function hitungInsentifSekretaris(){

    let total = 0;

    insentifInputs.forEach(el=>{
        total += parseID(el.value);
    });

    return total;
}

let FINAL_DEBIT  = {};
let FINAL_KREDIT = {};
let FINAL_TOTAL  = {};

function hitungDanaSumbangan(){

    let total = 0;

    document.querySelectorAll('.sumbangan-nominal')
        .forEach(el => {
            total += parseID(el.value);
        });

    document.getElementById('totalDanaSumbangan').innerText = formatID(total);

    return total;
}

function recalc(){

    const pajakGiro =
    parseID(document.querySelector('.pajak-giro')?.value);

    const cos = parseID(document.getElementById('cos')?.value);

    const debitCosDpp = parseID(
        document.querySelector('.debit-manual[data-key="COS DPP"]')?.value
    );

    const debitTransfer = parseID(
        document.querySelector('.debit-manual[data-key="Biaya Transfer"]')?.value
    );

    const totalDanaLain = hitungDanaLain();
    const totalInsentif = hitungInsentifSekretaris();

    const perBidangTotals = {...PER_BIDANG_TOTALS};
    perBidangTotals['BUNGA GIRO'] =
        (perBidangTotals['BUNGA GIRO'] || 0) + pajakGiro;

    const danaSumbanganMap = {
        'DANA OPERASIONAL': 0,
        'DANA PERJUANGAN': 0,
        'DANA SOSIAL': 0,
        'DANA PERMUSYAWARATAN': 0,
        'DANA SOSIAL POLITIK': 0,
    };

    document.querySelectorAll('#tblDanaSumbangan tbody tr')
    .forEach(tr => {

        const bidang = tr.querySelector('.sumbangan-bidang')
            ?.value
            ?.trim()
            ?.replace(/\s+/g,' ')
            ?.toUpperCase();

        const nominal =
            parseID(tr.querySelector('.sumbangan-nominal')?.value);

        if(danaSumbanganMap[bidang] !== undefined){
            danaSumbanganMap[bidang] += nominal;
        }

    });

    hitungDanaSumbangan();

    const autoDebit = {

        'Dana Operasional':
            Math.floor((cos / 1.5) - (debitCosDpp + debitTransfer))
            + danaSumbanganMap['DANA OPERASIONAL'],

        'Dana Perjuangan':
            Math.floor(cos * 0.5 / 1.5 * 0.2 / 0.5)
            + danaSumbanganMap['DANA PERJUANGAN'],

        'Dana Sosial':
            Math.floor(cos * 0.5 / 1.5 * 0.05 / 0.5)
            + danaSumbanganMap['DANA SOSIAL'],

        'Dana Permusyawaratan':
            Math.floor(cos * 0.5 / 1.5 * 0.2 / 0.5)
            + danaSumbanganMap['DANA PERMUSYAWARATAN'],

        'Dana Sosial Politik':
            Math.floor(cos * 0.5 / 1.5 * 0.05 / 0.5)
            + danaSumbanganMap['DANA SOSIAL POLITIK'],
    };

    let gs=0, gd=0, gk=0, gt=0;

    kasRows.forEach(row=>{

        const keyEl = row.querySelector('[data-key]');
        if(!keyEl) return;

        const key = keyEl.dataset.key;

        const saldoAwal =
            parseID(row.querySelector('.saldo-awal')?.value);

        let debit = 0;

        if(autoDebit[key] !== undefined){

            debit = autoDebit[key];

            const debitEl = row.querySelector('.debit');
            if(debitEl) debitEl.innerText = formatID(debit);

        }else{

            debit =
                parseID(row.querySelector('.debit-manual')?.value);

        }

        let kredit = 0;

        const otherDanaKeys = [
            'DANA PERJUANGAN',
            'DANA SOSIAL',
            'DANA PERMUSYAWARATAN',
            'DANA SOSIAL POLITIK',
            'BUNGA GIRO',
            'BUNGA DEPOSITO',
        ];

        if(key === 'Dana Operasional'){

            kredit =
                ((perBidangTotals['SEKRETARIS'] || 0) + totalInsentif) +
                (perBidangTotals['BIDANG 1'] || 0) +
                (perBidangTotals['BIDANG 2'] || 0) +
                (perBidangTotals['BIDANG 3'] || 0) +
                (perBidangTotals['BIDANG 4'] || 0) +
                (perBidangTotals['BIDANG 5'] || 0) +
                (perBidangTotals['BIDANG 6'] || 0) +
                (perBidangTotals['GM'] || 0) +
                (perBidangTotals['KORLAP'] || 0) +
                totalDanaLain;

            const kreditEl = row.querySelector('.kredit-auto');
            if(kreditEl) kreditEl.innerText = formatID(kredit);

        }
        else if(otherDanaKeys.includes(key.toUpperCase())){

            kredit = perBidangTotals[key.toUpperCase()] || 0;

            const kreditEl = row.querySelector('.kredit-auto');
            if(kreditEl) kreditEl.innerText = formatID(kredit);

        }
        else{

            kredit =
                parseID(row.querySelector('.kredit-auto')?.innerText) ||
                parseID(row.querySelector('.kredit-manual')?.value);

        }

        const total = saldoAwal + debit - kredit;

        const totalEl = row.querySelector('.total');
        if(totalEl) totalEl.innerText = formatID(total);

        FINAL_DEBIT[key]  = Math.floor(debit);
        FINAL_KREDIT[key] = Math.floor(kredit);
        FINAL_TOTAL[key]  = Math.floor(total);

        gs += saldoAwal;
        gd += debit;
        gk += kredit;
        gt += total;

    });

    document.getElementById('grandSaldo').innerText  = formatID(gs);
    document.getElementById('grandDebit').innerText  = formatID(gd);
    document.getElementById('grandKredit').innerText = formatID(gk);
    document.getElementById('grandTotal').innerText  = formatID(gt);

    const totalBidangSekEl =
        document.querySelector('.total-bidang[data-bidang="SEKRETARIS"]');

    if(totalBidangSekEl){

        totalBidangSekEl.innerText =
            formatID((perBidangTotals['SEKRETARIS']||0) + totalInsentif);

    }
    const totalBidangGiroEl =
        document.querySelector('.total-bidang[data-bidang="BUNGA GIRO"]');

    if(totalBidangGiroEl){
        totalBidangGiroEl.innerText =
            formatID(perBidangTotals['BUNGA GIRO'] || 0);
    }

}

function recalcDebounced(){

    clearTimeout(recalcTimer);

    recalcTimer = setTimeout(()=>{

        recalc();

    },120);
}


let danaLainIndex = document.querySelectorAll('#tblDanaLain tbody tr').length;

function addDanaLain(){
    if(isReadOnly) return;
    const tbody = document.querySelector('#tblDanaLain tbody');
    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td>
            <input type="text"
                name="dana_lain[${danaLainIndex}][bidang]"
                class="dana-bidang w-full">
        </td>
        <td>
            <input type="text"
                name="dana_lain[${danaLainIndex}][tujuan]"
                class="dana-tujuan w-full">
        </td>
        <td>
            <input type="text"
                name="dana_lain[${danaLainIndex}][keperluan]"
                class="dana-keperluan w-full">
        </td>
        <td>
            <input type="text"
                inputmode="numeric"
                name="dana_lain[${danaLainIndex}][nominal]"
                class="dana-lain-nominal w-full text-right"
                value="0">
        </td>
        <td class="text-center">
            <button type="button"
                class="inline-flex items-center justify-center w-6 h-6
                       bg-red-600 text-white rounded-md font-bold text-sm"
                onclick="removeDanaLain(this)">✕</button>
        </td>
    `;

    tbody.appendChild(tr);
    danaLainIndex++;

    tr.querySelector('.dana-lain-nominal')
        .addEventListener('input', e => {
            formatInput(e.target);
            recalc();
        });

    recalc();
    IS_DIRTY = true;
}

function removeDanaLain(btn){
    if(isReadOnly) return;
    btn.closest('tr').remove();
    recalc();
    IS_DIRTY = true;
}

function removeDanaSumbangan(btn){
    if(isReadOnly) return;
    btn.closest('tr').remove();
    recalc();
    IS_DIRTY = true;
}

let danaSumbanganIndex =
    document.querySelectorAll('#tblDanaSumbangan tbody tr').length;

function addDanaSumbangan(){
    if(isReadOnly) return;

    const tbody = document.querySelector('#tblDanaSumbangan tbody');

    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td>
            <input type="text"
                   name="dana_sumbangan[${danaSumbanganIndex}][donatur]"
                   class="sumbangan-donatur w-full">
        </td>

        <td>
            <select
                name="dana_sumbangan[${danaSumbanganIndex}][bidang]"
                class="sumbangan-bidang w-full">

                <option value="">-- Pilih --</option>
                <option value="Dana Operasional">Dana Operasional</option>
                <option value="Dana Perjuangan">Dana Perjuangan</option>
                <option value="Dana Sosial">Dana Sosial</option>
                <option value="Dana Permusyawaratan">Dana Permusyawaratan</option>
                <option value="Dana Sosial Politik">Dana Sosial Politik</option>

            </select>
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[${danaSumbanganIndex}][tujuan]"
                   class="sumbangan-tujuan w-full">
        </td>

        <td>
            <input type="text"
                   name="dana_sumbangan[${danaSumbanganIndex}][keperluan]"
                   class="sumbangan-keperluan w-full">
        </td>

        <td>
            <input type="text"
                   inputmode="numeric"
                   name="dana_sumbangan[${danaSumbanganIndex}][nominal]"
                   class="sumbangan-nominal w-full text-right"
                   value="0">
        </td>

        <td class="text-center">
            <button
                type="button"
                onclick="removeDanaSumbangan(this)"
                class="inline-flex items-center justify-center
                       w-6 h-6 bg-red-600 text-white
                       rounded-md font-bold text-sm leading-none">
                ✕
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    danaSumbanganIndex++;

    tr.querySelector('.sumbangan-nominal')
        .addEventListener('input', e => {
            formatInput(e.target);
            recalc();
        });

    recalc();
    IS_DIRTY = true;

    }


function collectRawData(){

    const danaSumbangan = [];
    const saldoAwal={}, debitManual={}, kreditManual={}, danaLain=[], insentif = [];

    document.querySelectorAll('.insentif-row')
    .forEach((row, i) => {

        const tds = row.querySelectorAll('td');

        insentif.push({
            tujuan: tds[1]?.innerText || '',
            keperluan: tds[2]?.innerText || '',
            nominal: parseID(
                row.querySelector('.insentif-nominal')?.value
            )
        });

    });

    

    document.querySelectorAll('.saldo-awal')
        .forEach(e => saldoAwal[e.dataset.key] = parseID(e.value));

    document.querySelectorAll('.debit-manual')
        .forEach(e => debitManual[e.dataset.key] = parseID(e.value));

    document.querySelectorAll('.kredit-manual')
        .forEach(e => kreditManual[e.dataset.key] = parseID(e.value));

    document.querySelectorAll('#tblDanaLain tbody tr').forEach(tr=>{
        const bidang = tr.querySelector('.dana-bidang')?.value || '';
        const tujuan = tr.querySelector('.dana-tujuan')?.value || '';
        const keperluan = tr.querySelector('.dana-keperluan')?.value || '';
        const nominal = parseID(tr.querySelector('.dana-lain-nominal')?.value);

        if(bidang || tujuan || keperluan || nominal){
            danaLain.push({ bidang, tujuan, keperluan, nominal });
        }
    });

    const pajakBank =
        parseID(document.querySelector('.pajak-giro')?.value);

    document.querySelectorAll('#tblDanaSumbangan tbody tr')
    .forEach(tr => {

        const donatur = tr.querySelector('.sumbangan-donatur')?.value || '';
        const bidang = tr.querySelector('.sumbangan-bidang')?.value || '';
        const tujuan = tr.querySelector('.sumbangan-tujuan')?.value || '';
        const keperluan = tr.querySelector('.sumbangan-keperluan')?.value || '';

        const nominal = parseID(
            tr.querySelector('.sumbangan-nominal')?.value
        );

        if(donatur || bidang || tujuan || keperluan || nominal){
            danaSumbangan.push({
                donatur,
                bidang,
                tujuan,
                keperluan,
                nominal
            });
        }

    });    

    return {
        bulan: {{ $meta['bulan'] }},
        tahun: {{ $meta['tahun'] }},
        cos: parseID(document.getElementById('cos').value),
        pajak_bank: pajakBank,
        is_edit: isEdit ? 1 : 0,
        saldo_awal_json: saldoAwal,
        debit_manual_json: debitManual,
        kredit_manual_json: kreditManual,
        dana_lain_json: danaLain,
        dana_sumbangan_json: danaSumbangan,
        insentif: insentif,
        debit_json: FINAL_DEBIT,
        kredit_json: FINAL_KREDIT,
        total_json: FINAL_TOTAL
    };
}



document.addEventListener('input', e => {

    if(e.target.matches(
        '#cos,.saldo-awal,.debit-manual,.kredit-manual,.dana-lain-nominal,.insentif-nominal,.pajak-giro,.sumbangan-nominal'
    )){
        formatInput(e.target);
    }

    if(e.target.matches(
        '#cos,.saldo-awal,.debit-manual,.kredit-manual,.dana-lain-nominal,.insentif-nominal,.pajak-giro,.sumbangan-nominal,.sumbangan-donatur,.sumbangan-bidang,.sumbangan-tujuan,.sumbangan-keperluan'
    )){
        recalcDebounced();
    }

});

document.addEventListener('change', e=>{

    if(
        e.target.matches(
            '.sumbangan-bidang'
        )
    ){
        IS_DIRTY = true;
        recalcDebounced();
    }

});    

document.addEventListener('DOMContentLoaded', ()=>{

    kasRows = Array.from(
        document.querySelectorAll('#tblRingkasanKas tbody tr')
    ).filter(tr => tr.querySelector('[data-key]'));

    danaLainInputs = Array.from(
        document.querySelectorAll('.dana-lain-nominal')
    );

    insentifInputs = Array.from(
        document.querySelectorAll('.insentif-nominal')
    );

    document.querySelectorAll(
        '#cos,.saldo-awal,.debit-manual,.kredit-manual,.dana-lain-nominal,.insentif-nominal,.pajak-giro,.sumbangan-nominal'
    ).forEach(el=>{

        formatInput(el);

    });

    recalc();

});


</script>
<script>

let IS_SAVING = false;

async function handleSimpanLaporan() {

    if(isReadOnly) return;

    const btn = document.getElementById('btnSimpanLaporan');
    if(IS_SAVING || btn.disabled) return;

    IS_SAVING = true;
    btn.disabled = true;
    
    const spinner = document.getElementById('spinnerSimpan');
    const text = document.getElementById('textSimpan');

  
    spinner.classList.remove('hidden');
    text.innerText = 'Memproses…';

    try {
        setProgress(10, 'Validasi data…');

        const payload = collectRawData();

        if(!payload){
            throw new Error('Payload gagal dibuat');
        }

        document.querySelectorAll(
            '#desktopAutoScale input,' +
            '#desktopAutoScale button,' +
            '#desktopAutoScale select,' +
            '#desktopAutoScale textarea'
        ).forEach(el => {

            if(el.id === 'btnSimpanLaporan') return;

            el.disabled = true;
        });

        setProgress(30, 'Menghitung laporan…');

        const res = await fetch(
            "{{ route('laporan-bendahara.store') }}",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(payload)
            }
        );

        setProgress(70, 'Mengunggah PDF…');

        if(!res.ok){
            throw new Error('Server error');
        }
        
        const json = await res.json();

        if (!json.success) {
            throw new Error(json.message || 'Gagal menyimpan laporan');
        }

        setProgress(100, 'Selesai ✅');

        showToast('Laporan bendahara berhasil disimpan & PDF final dibuat');
        
        if(json.success){
            IS_DIRTY = false;   
        }

        setTimeout(() => {
            document.getElementById('progressBox').classList.add('hidden');
        }, 1200);

    } catch (err) {
        console.error(err);       
        document.getElementById('progressBox').classList.add('hidden');
        showToast(err.message || 'Terjadi kesalahan','error');

    } finally {
        IS_SAVING = false;
        btn.disabled = false;
                document.querySelectorAll(
            '#desktopAutoScale input,' +
            '#desktopAutoScale button,' +
            '#desktopAutoScale select,' +
            '#desktopAutoScale textarea'
        ).forEach(el => {

            if(el.id === 'btnSimpanLaporan') return;
            el.disabled = false;
        });

        spinner.classList.add('hidden');
        text.innerText = isEdit ? 'Update Laporan' : 'Simpan Laporan';
    }
}
</script>
<script>
/* ── MODAL TOLAK LAPORAN ─────────────────────────────────── */
function openModalTolakLaporan(){
    document.getElementById('catatanTolakLaporan').value = '';
    document.getElementById('modalTolakLaporan').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModalTolakLaporan(){
    document.getElementById('modalTolakLaporan').style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('modalTolakLaporan')?.addEventListener('click', e => {
    if (e.target.id === 'modalTolakLaporan') closeModalTolakLaporan();
});

document.getElementById('formTolakLaporan')?.addEventListener('submit', async function(e){
    e.preventDefault();

    const btn  = document.getElementById('btnKirimTolakLaporan');
    const spin = document.getElementById('spinTolakLaporan');
    const text = document.getElementById('textTolakLaporan');
    const catatan = document.getElementById('catatanTolakLaporan').value.trim();

    if (!catatan) return;

    btn.disabled = true;
    spin.classList.remove('hidden');
    text.innerText = 'Mengirim...';

    const routeUrl = @json($userRole) === 'Sekretaris'
        ? "{{ route('laporan-bendahara.reject-sekretaris') }}"
        : "{{ route('laporan-bendahara.reject-ketua') }}";

    try {
        const res = await fetch(routeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ bulan, tahun, catatan })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal menolak laporan');

        showToast(json.message, 'success');
        setTimeout(() => {
            window.location.href = "{{ route('laporan-bulanan.index') }}";
        }, 900);

    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan', 'error');
    } finally {
        btn.disabled = false;
        spin.classList.add('hidden');
        text.innerText = 'Kirim Penolakan';
    }
});

/* ── APPROVE LAPORAN ─────────────────────────────────────── */
async function handleApproveLaporan(){
    const btn = document.getElementById('btnSetujuiLaporan');
    if (!btn || btn.disabled) return;

    const ok = await showConfirmLaporan(
        'Laporan akan diteruskan ke tahap approval berikutnya dan tidak dapat dibatalkan.',
        { title: @json($approveLabel) + ' laporan ini?', okText: 'Ya, ' + @json($approveLabel) }
    );
    if (!ok) return;

    const spin = document.getElementById('spinnerSetujui');
    const text = document.getElementById('textSetujui');

    btn.disabled = true;
    spin.classList.remove('hidden');
    text.innerText = 'Memproses...';

    const routeUrl = @json($userRole) === 'Sekretaris'
        ? "{{ route('laporan-bendahara.approve-sekretaris') }}"
        : "{{ route('laporan-bendahara.approve-ketua') }}";

    try {
        const res = await fetch(routeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ bulan, tahun })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal memproses persetujuan');

        showToast(json.message, 'success');
        setTimeout(() => {
            window.location.href = "{{ route('laporan-bulanan.index') }}";
        }, 900);

    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan', 'error');
        btn.disabled = false;
        spin.classList.add('hidden');
        text.innerText = @json($approveLabel);
    }
}
</script>
<script>
function openModalBukaKembali(){
    document.getElementById('alasanBukaKembali').value = '';
    document.getElementById('modalBukaKembali').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModalBukaKembali(){
    document.getElementById('modalBukaKembali').style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('modalBukaKembali')?.addEventListener('click', e => {
    if (e.target.id === 'modalBukaKembali') closeModalBukaKembali();
});

document.getElementById('formBukaKembali')?.addEventListener('submit', async function(e){
    e.preventDefault();

    const btn  = document.getElementById('btnKirimBukaKembali');
    const spin = document.getElementById('spinBukaKembali');
    const text = document.getElementById('textBukaKembali');
    const alasan = document.getElementById('alasanBukaKembali').value.trim();

    if (!alasan) return;

    btn.disabled = true;
    spin.classList.remove('hidden');
    text.innerText = 'Memproses...';

    try {
        const res = await fetch("{{ route('laporan-bendahara.reopen') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ bulan, tahun, alasan })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal membuka kembali laporan');

        showToast(json.message, 'success');
        setTimeout(() => {
            window.location.href = "{{ route('laporan-bulanan.index') }}";
        }, 900);

    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan', 'error');
    } finally {
        btn.disabled = false;
        spin.classList.add('hidden');
        text.innerText = 'Buka Kembali';
    }
});
</script>

<script>
let IS_DIRTY = false;

if(!document.getElementById('toastWrap')){

    const toastRoot = document.createElement('div');

    toastRoot.id = 'toastWrap';

    toastRoot.className = `
        fixed top-5 left-1/2 -translate-x-1/2
        flex flex-col items-center gap-2
        z-[999999]
        pointer-events-none
    `;

    document.body.appendChild(toastRoot);
}

function showToast(msg,type='success'){

    const colors = {
        success:'bg-green-600',
        error:'bg-red-600',
        info:'bg-gray-800',
        warning:'bg-yellow-500'
    };

    const toast=document.createElement('div');

    toast.className=`
        ${colors[type]}
        text-white text-xs
        px-4 py-2 rounded shadow-lg
        animate-fadeIn
    `;

    toast.innerText=msg;
    toast.style.whiteSpace='pre-line';

    const wrap=document.getElementById('toastWrap');

    if(!wrap) return;

    wrap.appendChild(toast);

    const normalToasts = [
        ...wrap.children
    ].filter(el =>
        !el.classList.contains('toast-confirm-back')
    );

    if(normalToasts.length >= 4){
        normalToasts[0].remove();
    }

    setTimeout(()=>{

        toast.style.opacity='0';
        toast.style.transform='translateY(-10px)';

        setTimeout(()=>toast.remove(),300);

    },3000);
}

document.addEventListener('input', e=>{

    if(
        e.target.matches(
            '#cos,.saldo-awal,.debit-manual,.kredit-manual,.dana-lain-nominal,.insentif-nominal,.pajak-giro,.dana-bidang,.dana-tujuan,.dana-keperluan,.sumbangan-donatur,.sumbangan-bidang,.sumbangan-tujuan,.sumbangan-keperluan,.sumbangan-nominal'
        )
    ){
        IS_DIRTY = true;
    }

});

function handleBackButton(){

    if(!IS_DIRTY){
        goBackNow();
        return;
    }

    showConfirmBackToast();
}

function showConfirmBackToast(){

    document.querySelectorAll('.toast-confirm-back')
        .forEach(t => t.remove());

    const toast = document.createElement('div');

    toast.className = `
        toast-confirm-back
        bg-yellow-500 text-white text-xs
        px-4 py-3 rounded shadow-xl
        animate-fadeIn
        flex flex-col gap-2
        min-w-[300px]
    `;

    toast.innerHTML = `
        <div class="font-semibold">
            Perubahan belum disimpan
        </div>

        <div>
            Yakin ingin kembali?
        </div>

        <div class="flex justify-end gap-2 mt-1">

            <button
                type="button"
                class="px-3 py-1 bg-white/20 rounded hover:bg-white/30"
                onclick="closeConfirmToast(this)">
                Batal
            </button>

            <button
                type="button"
                class="px-3 py-1 bg-white text-yellow-700 font-semibold rounded"
                onclick="confirmBack()">
                Ya, Kembali
            </button>

        </div>
    `;

    document.getElementById('toastWrap')
        .appendChild(toast);
}

function closeConfirmToast(btn){

    btn.closest('.toast-confirm-back')?.remove();

}

function confirmBack(){

    IS_DIRTY = false;
    goBackNow();

}

function goBackNow(){

    window.location.href =
        "{{ route('laporan-bulanan.index') }}";

}

window.addEventListener('beforeunload', function (e) {

    if(!IS_DIRTY) return;

    e.preventDefault();
    e.returnValue = '';

});
</script>

<script>
function autoScaleDesktop(){

    const baseWidth = 1200;
    const screenWidth = window.innerWidth;

    let scale = screenWidth / baseWidth;

    if(scale > 1) scale = 1;
    if(scale < 0.25) scale = 0.25;

    const el = document.getElementById('desktopAutoScale');

    el.style.transform = `scale(${scale})`;
    el.style.width = baseWidth + 'px';

    document.body.style.minHeight =
    (el.scrollHeight * scale) + 'px';
}

window.addEventListener('load', autoScaleDesktop);
window.addEventListener('resize', autoScaleDesktop);
</script>
{{-- ── MODAL CONFIRM RINGAN ────────────────────────────────── --}}
<div id="modalConfirmLaporan"
    style="position:fixed;inset:0;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);display:none;align-items:center;
           justify-content:center;z-index:8200;padding:20px;">

    <div id="modalConfirmLaporanBox"
        style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;
                box-shadow:0 20px 50px rgba(0,0,0,.2);
                width:100%;max-width:400px;padding:28px;
                transform:scale(.96);opacity:0;transition:transform .18s ease,opacity .18s ease;">

        <div style="margin-bottom:20px;">
            <h2 id="confirmLaporanTitle"
                style="font-size:0.95rem;font-weight:600;color:#111827;margin:0 0 6px;">
                Konfirmasi
            </h2>
            <p id="confirmLaporanMessage"
                style="font-size:0.8125rem;color:#6b7280;margin:0;line-height:1.5;">
            </p>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" id="confirmLaporanBtnCancel"
                style="padding:8px 16px;border-radius:8px;border:1px solid #d1d5db;
                       background:#fff;color:#374151;cursor:pointer;">
                Batal
            </button>
            <button type="button" id="confirmLaporanBtnOk"
                style="padding:8px 16px;border-radius:8px;border:none;
                       background:#2563eb;color:#fff;cursor:pointer;">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
const _modalConfirmLaporan    = document.getElementById('modalConfirmLaporan');
const _modalConfirmLaporanBox = document.getElementById('modalConfirmLaporanBox');
const _confirmLaporanTitle    = document.getElementById('confirmLaporanTitle');
const _confirmLaporanMessage  = document.getElementById('confirmLaporanMessage');
const _confirmLaporanBtnOk    = document.getElementById('confirmLaporanBtnOk');
const _confirmLaporanBtnCancel= document.getElementById('confirmLaporanBtnCancel');

let _confirmLaporanResolver = null;

function showConfirmLaporan(message, options = {}) {
    const { title = 'Konfirmasi', okText = 'Ya, Lanjutkan' } = options;

    _confirmLaporanTitle.textContent   = title;
    _confirmLaporanMessage.textContent = message;
    _confirmLaporanBtnOk.textContent   = okText;

    _modalConfirmLaporanBox.style.transform = 'scale(.96)';
    _modalConfirmLaporanBox.style.opacity = '0';

    _modalConfirmLaporan.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            _modalConfirmLaporanBox.style.transform = 'scale(1)';
            _modalConfirmLaporanBox.style.opacity = '1';
        });
    });

    return new Promise(resolve => { _confirmLaporanResolver = resolve; });
}

function _closeConfirmLaporan(result) {
    _modalConfirmLaporanBox.style.transform = 'scale(.96)';
    _modalConfirmLaporanBox.style.opacity = '0';

    setTimeout(() => {
        _modalConfirmLaporan.style.display = 'none';
        document.body.style.overflow = '';
    }, 150);

    if (_confirmLaporanResolver) {
        _confirmLaporanResolver(result);
        _confirmLaporanResolver = null;
    }
}

_confirmLaporanBtnOk.addEventListener('click', () => _closeConfirmLaporan(true));
_confirmLaporanBtnCancel.addEventListener('click', () => _closeConfirmLaporan(false));
_modalConfirmLaporan.addEventListener('click', e => {
    if (e.target === _modalConfirmLaporan) _closeConfirmLaporan(false);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    ['modalTolakLaporan', 'modalConfirmLaporan','modalBukaKembali'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
});
</script>
</div>

@endsection
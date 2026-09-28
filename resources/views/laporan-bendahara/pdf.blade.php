<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" href="{{ public_path('favicon.png') }}">
<title>Laporan Bendahara</title>

<style>
    /* ================= PAGE ================= */
@page {
    margin: 2mm 10mm 10mm 10mm;
}

/* ================= GLOBAL ================= */
body {
    font-family: "Times New Roman", serif;
    font-size: 10pt;
    padding-top: 32mm;     /* tinggi header */
    padding-bottom: 10mm;  /* tinggi footer */
}

/* ================= HEADER ================= */
.pdf-header {
    position: fixed;
    top: 0;
    left: 0mm;
    right: 0mm;
    height: 35mm;
    text-align: center;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
}

.header-table td {
    border: none;
    vertical-align: middle;
}

/* TD LOGO DIKUNCI */
.header-logo_spee {
    width: 110px;
}

.header-logo_fspmi {
    width: 110px;
}

/* TEKS */
.header-text {
    text-align: center;
    z-index: 1;
    font-family: "Times New Roman", serif;
}

.header-text .judul {
    font-weight: bold;
    font-size: 11pt;
    line-height: 1.15;
}

.header-text .alamat {
    font-size: 9pt;
    white-space: nowrap;
}

/* ================= LOGO OVERLAY ================= */
.logo-abs {
    position: absolute;
    top: 6mm;
    z-index: 5; /* LOGO DI DEPAN TEKS */
}

.logo-spee {
    top: 12px;
    left: 0mm;
    width: 120px; /* 🔥 BEBAS DIUBAH */
}

.logo-fspmi {
    right: 0mm;
    width: 100px; /* 🔥 BEBAS DIUBAH */
}

/* GARIS */
.header-line-1 {
    border-top: 3px solid #000;
    margin-top: 2mm;
}

.header-line-2 {
    border-top: 1px solid #000;
    margin-top: 1mm;
}
    h1, h2 {
        text-align: center;
        margin: 0;
    }

    h1 {
        font-size: 13pt;
        font-weight: bold;
    }

    h2 {
        font-size: 11pt;
        margin-bottom: 6px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    th, td {
        border: 1px solid #000;
        padding: 2px;
        vertical-align: top;
    }

    th {
        background: #eee;
        text-align: center;
    }

    .text-right {
        text-align: right;
        white-space: nowrap;
    }

    .ringkasan {
        margin-bottom: 8px;
        font-size: 9pt;
    }

    .judul-section {
        margin: 8px 0 6px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 9pt;
        text-align: center;
        letter-spacing: 1px;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 3px 0;
        page-break-after: avoid;
    }


    .judul-bidang {
        margin: 6px 0 2px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 8.5pt;
        page-break-after: avoid;
    }

    .tbl-kecil {
        font-size: 6pt;
        margin-bottom: 4px;
        table-layout: fixed;
        width: 100%;
    }


    .tbl-kecil th,
    .tbl-kecil td {
        padding: 2px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        vertical-align: top;
    }

    .total-row th {
        background: #ddd;
        font-weight: bold;
    }

   /*=====Tanda tangan====*/
    .ttd-table {
        width: 240px;
        margin-left: auto;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 9px;
        text-align: center;
        table-layout: fixed;
    }

    .ttd-table td {
        width: 80px;
        border: 1px solid #000;
        padding: 3px;
        overflow: hidden;
    }

    /* ===== ANTI PAGE BREAK TTD ===== */
    .ttd-wrapper {
        position: relative;
        page-break-inside: avoid;
    }

    .stamp-img {
        position: absolute !important;
        width: 90px !important;
        height: 90px !important;
        top: -10px;
        left: 410px;      
        z-index: 999;
        opacity: 0.85;
        transform: rotate(9deg);
        transform-origin: center center;
    }

    .ttd-table {
        page-break-inside: avoid;
    }

    /* Judul kolom */
    .ttd-title td {
        font-weight: bold;
        padding: 2px;
    }

    /* Area tanda tangan */
    .ttd-space td {
        height: 55px;
        vertical-align: bottom;
    }

    .ttd-img {
        height: 35px;
        width: auto;
        display: block;
        margin: 0 auto;
    }

    .ttd-tanggal {
        font-size: 8px;
        margin-top: 2px;
    }

    /* Nama jabatan */
    .ttd-name td {
        padding: 2px;
    }


</style>
</head>
<body>
 
<!-- ================= HEADER ================= -->
<div class="pdf-header">
    <table class="header-table">
        <tr>
            <td class="logo header-logo_spee"></td>

            <td class="header-text">
                <div class="judul">PIMPINAN UNIT KERJA (PUK)</div>
                <div class="judul">SERIKAT PEKERJA ELEKTRONIK – ELEKTRIK (SPEE)</div>
                <div class="judul">FEDERASI SERIKAT PEKERJA METAL INDONESIA (FSPMI)</div>
                <div class="judul">PT. OMRON MANUFACTURING OF INDONESIA</div>
                <div class="alamat">
                    Kawasan EJIP Plot 5C Cikarang Bekasi – Telp./Fax. (021) 8975110
                    Email: <u>puk.spee.omron@outlook.com</u>
                </div>
            </td>

            <td class="logo header-logo_fspmi"></td>
        </tr>
    </table>

    <!-- LOGO ABSOLUTE (OVERLAY) -->
    <img class="logo-abs logo-spee" src="{{ public_path('assets/logo-spee.png') }}">
    <img class="logo-abs logo-fspmi" src="{{ public_path('assets/logo-fspmi.png') }}">

    <div class="header-line-1"></div>
    <div class="header-line-2"></div>
</div>

<div class="content">

{{-- =========================
     JUDUL
========================= --}}
<h1>LAPORAN BENDAHARA</h1>
<h2>
    BULAN {{ strtoupper(\Carbon\Carbon::create()->month($laporan['meta']['bulan'])->translatedFormat('F')) }}
    {{ $laporan['meta']['tahun'] }}
</h2>

<p><strong>COS :</strong>
{{ number_format($laporan['saved_raw']['cos'] ?? 0,0,',','.') }}
</p>

{{-- =========================
     RINGKASAN KAS
========================= --}}
<table class="ringkasan">
<thead>
<tr>
    <th>No</th>
    <th>Keterangan</th>
    <th>Saldo Awal</th>
    <th>Debit</th>
    <th>Kredit</th>
    <th>Total</th>
</tr>
</thead>
<tbody>

@php
$sumSaldoAwal = 0;
$sumDebit = 0;
$sumKredit = 0;
$sumTotal = 0;
@endphp

@foreach($laporan['ringkasan_kas'] as $r)
@php
    $sumSaldoAwal += $r['saldo_awal'];
    $sumDebit     += $r['debit'];
    $sumKredit    += $r['kredit'];
    $sumTotal     += $r['total'];
@endphp
<tr>
    <td align="center">{{ $r['no'] }}</td>
    <td>{{ strtoupper($r['jenis']) }}</td>
    <td class="text-right">{{ number_format($r['saldo_awal'],0,',','.') }}</td>
    <td class="text-right">{{ number_format($r['debit'],0,',','.') }}</td>
    <td class="text-right">{{ number_format($r['kredit'],0,',','.') }}</td>
    <td class="text-right">{{ number_format($r['total'],0,',','.') }}</td>
</tr>
@endforeach

<tr class="total-row">
    <th colspan="2">TOTAL</th>
    <th class="text-right">{{ number_format($sumSaldoAwal,0,',','.') }}</th>
    <th class="text-right">{{ number_format($sumDebit,0,',','.') }}</th>
    <th class="text-right">{{ number_format($sumKredit,0,',','.') }}</th>
    <th class="text-right">{{ number_format($sumTotal,0,',','.') }}</th>
</tr>

</tbody>
</table>

{{-- =========================
     RINCIAN PENGELUARAN
========================= --}}
<div class="judul-section">RINCIAN PENGELUARAN</div>

@php
$grandTotal = 0;

$tanggalLaporan = \Carbon\Carbon::create(
    $laporan['meta']['tahun'], $laporan['meta']['bulan'], 1
)->translatedFormat('F Y');

$bagian = [];
foreach ($laporan['rincian'] as $sec) {
    $rows = $sec['items'];
    if (!empty($sec['insentif'])) {
        $rows = array_merge($rows, $laporan['insentif_sekretaris'] ?? []);
    }
    if (!empty($sec['punya_pajak'])) {
        $rows[] = [
            'tanggal'   => $tanggalLaporan,
            'tujuan'    => strtoupper($sec['tujuan_pajak'] ?? ''),
            'keperluan' => strtoupper($sec['label_pajak'] ?? ''),
            'nominal'   => $sec['pajak'],
        ];
    }
    $bagian[] = ['nama' => strtoupper($sec['label']), 'lain' => false, 'data' => $rows];
}
$bagian[] = ['nama' => 'DANA LAIN-LAIN', 'lain' => true, 'data' => $laporan['dana_lain'] ?? []];
@endphp

@foreach ($bagian as $b)
    @php
        $nama = $b['nama'];
        $lain = $b['lain'];
        $data = $b['data'];
        $totalBidang = 0;
    @endphp


    @if (!empty($data))
        <div class="judul-bidang">
            {{ $nama === 'DANA LAIN-LAIN' ? 'Pengeluaran Dana Operasional Lainnya' : $nama }}
        </div>

        <table class="tbl-kecil">
            <thead>
            <tr>
                @if ($nama === 'DANA LAIN-LAIN')
                    <th style="width:15%">Bidang</th>
                    <th style="width:20%">Tujuan</th>
                    <th style="width:55%">Keperluan</th>
                    <th style="width:15%">Nominal</th>
                @else
                    <th style="width:15%">Tanggal</th>
                    <th style="width:20%">Tujuan</th>
                    <th style="width:55%">Keperluan</th>
                    <th style="width:15%">Nominal</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach ($data as $row)
            @php
                $nilai = $row['nominal'] ?? $row['aktual'] ?? 0;
                $totalBidang += $nilai;
                $grandTotal += $nilai;
            @endphp
            <tr>
                @if ($nama === 'DANA LAIN-LAIN')
                    <td style="width:15%">{{ $row['bidang'] ?? '-' }}</td>
                    <td style="width:20%">{{ $row['tujuan'] ?? '-' }}</td>
                    <td style="width:55%">{{ $row['keperluan'] ?? '-' }}</td>
                    <td style="width:15%" class="text-right">
                        {{ number_format($row['nominal'] ?? 0,0,',','.') }}
                    </td>
                @else
                    <td style="width:15%">{{ $row['tanggal'] ?? '-' }}</td>
                    <td style="width:20%">{{ $row['tujuan'] ?? '_' }}</td>
                    <td style="width:55%">{{ $row['keperluan'] ?? '_' }}</td>
                    <td style="width:15%" class="text-right">
                        {{ number_format($row['nominal'] ?? $row['aktual'],0,',','.') }}
                    </td>
                @endif
            </tr>

            @endforeach
            <tr class="total-row">
                <th colspan="3">TOTAL</th>
                <th class="text-right">{{ number_format($totalBidang,0,',','.') }}</th>
            </tr>
            </tbody>
        </table>
    @endif

@endforeach

{{-- =========================
     DANA SUMBANGAN
========================= --}}
@php
$danaSumbangan = $laporan['dana_sumbangan'] ?? [];
$totalDanaSumbangan = collect($danaSumbangan)->sum('nominal');
@endphp

@if(count($danaSumbangan))
    <div class="judul-bidang">
        Dana Sumbangan
    </div>

    <table class="tbl-kecil">
        <thead>
        <tr>
            <th style="width:18%">Donatur</th>
            <th style="width:18%">Alokasi Dana</th>
            <th style="width:18%">Tujuan</th>
            <th style="width:31%">Keperluan</th>
            <th style="width:15%">Nominal</th>
        </tr>
        </thead>

        <tbody>
        @foreach($danaSumbangan as $row)
        <tr>
            <td style="width:18%">
                {{ $row['donatur'] ?? '-' }}
            </td>

            <td style="width:18%">
                {{ $row['bidang'] ?? '-' }}
            </td>

            <td style="width:18%">
                {{ $row['tujuan'] ?? '-' }}
            </td>

            <td style="width:31%">
                {{ $row['keperluan'] ?? '-' }}
            </td>

            <td style="width:15%" class="text-right">
                {{ number_format($row['nominal'] ?? 0,0,',','.') }}
            </td>
        </tr>
        @endforeach

        <tr class="total-row">
            <th colspan="4">TOTAL</th>
            <th class="text-right">
                {{ number_format($totalDanaSumbangan,0,',','.') }}
            </th>
        </tr>

        </tbody>
    </table>
@endif

{{-- =========================
     TTD
========================= --}}
<br><br>
<div class="ttd-wrapper">
    <table class="ttd-table">
        <tr class="ttd-title">
            <td>Issued</td>
            <td>Checked</td>
            <td>Approved</td>
        </tr>
    <tr class="ttd-space">
        <td>
            @if($laporan['meta']['bendahara_ttd_img'] ?? null)
                <img src="{{ $laporan['meta']['bendahara_ttd_img'] }}" class="ttd-img">
            @endif
            @if($laporan['meta']['bendahara_ttd_at'] ?? null)
                <div class="ttd-tanggal">{{ \Carbon\Carbon::parse($laporan['meta']['bendahara_ttd_at'])->locale('id')->translatedFormat('d M Y') }}</div>
            @endif
        </td>
        <td>
            @if($laporan['meta']['sekretaris_ttd_img'] ?? null)
                <img src="{{ $laporan['meta']['sekretaris_ttd_img'] }}" class="ttd-img">
            @endif
            @if($laporan['meta']['sekretaris_ttd_at'] ?? null)
                <div class="ttd-tanggal">{{ \Carbon\Carbon::parse($laporan['meta']['sekretaris_ttd_at'])->locale('id')->translatedFormat('d M Y') }}</div>
            @endif
        </td>
        <td>
            @if($laporan['meta']['ketua_ttd_img'] ?? null)
                <img src="{{ $laporan['meta']['ketua_ttd_img'] }}" class="ttd-img">
            @endif
            @if($laporan['meta']['ketua_ttd_at'] ?? null)
                <div class="ttd-tanggal">{{ \Carbon\Carbon::parse($laporan['meta']['ketua_ttd_at'])->locale('id')->translatedFormat('d M Y') }}</div>
            @endif
        </td>
    </tr>
    <tr class="ttd-name">
        <td>{{ $laporan['meta']['bendahara_nama'] ?? 'Bendahara' }}</td>
        <td>{{ $laporan['meta']['sekretaris_nama'] ?? 'Sekretaris' }}</td>
        <td>{{ $laporan['meta']['ketua_nama'] ?? 'Ketua' }}</td>
    </tr>
</table>

@php
    $stampPath = public_path('assets/STAMP_PUK.png');
    $tampilkanStamp = ($laporan['meta']['status'] ?? null) === 'selesai'
        && file_exists($stampPath);
@endphp

@if($tampilkanStamp)
    <img src="{{ $stampPath }}" class="stamp-img" width="70" height="70">
@endif



</div>

</body>
</html>
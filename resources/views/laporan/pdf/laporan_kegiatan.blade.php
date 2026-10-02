<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Kegiatan</title>

    <style>
        /* ================= PAGE ================= */
        @page {
            margin: 2mm 10mm 10mm 10mm;
        }

        /* ================= GLOBAL ================= */
        body {
            font-family: "Times New Roman", serif;
            font-size: 10pt;
            padding-top: 25mm;
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
            z-index: 5;
            /* LOGO DI DEPAN TEKS */
        }

        .logo-spee {
            top: 12px;
            left: 0mm;
            width: 95px;
            /* 🔥 BEBAS DIUBAH */
        }

        .logo-fspmi {
            right: 0mm;
            width: 100px;
            /* 🔥 BEBAS DIUBAH */
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

        /* ================= CONTENT ================= */

        .table-info {
            width: 100%;
            margin-top: 10px;
        }

        .table-info td {
            border: none;
            padding: 2px;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }

        .table-anggaran {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .table-anggaran th,
        .table-anggaran td {
            border: 1px solid #000;
            padding: 4px;
        }

        .table-anggaran th {
            background: #eee;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- ================= HEADER ================= -->
    <div class="pdf-header">

        <table class="header-table">
            <tr>

                <td></td>

                <td class="header-text">
                    <div class="judul">PIMPINAN UNIT KERJA (PUK)</div>
                    <div class="judul">SERIKAT PEKERJA ELEKTRONIK – ELEKTRIK (SPEE)</div>
                    <div class="judul">FEDERASI SERIKAT PEKERJA METAL INDONESIA (FSPMI)</div>
                    <div class="judul">PT. OMRON MANUFACTURING OF INDONESIA</div>

                    <div class="alamat">
                        Kawasan EJIP Plot 5C Cikarang Bekasi –
                        Telp./Fax. (021) 8975110
                        Email: <u>puk.spee.omron@outlook.com</u>
                    </div>

                </td>

                <td></td>

            </tr>
        </table>

        <img class="logo-abs logo-spee"  src="{{ public_path('assets/logo-spee-pdf.jpg') }}">
        <img class="logo-abs logo-fspmi" src="{{ public_path('assets/logo-fspmi-pdf.jpg') }}">

        <div class="header-line-1"></div>
        <div class="header-line-2"></div>

    </div>


    {{-- ================= DATA SURAT ================= --}}

    <table class="table-info">
        <tr>
            <td width="60%">
                Nomor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: - <br>
                Perihal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: Laporan Kegiatan <br>
                Lampiran&nbsp;&nbsp;: 1 Lembar
            </td>

            <td align="right">

                Cikarang,
                {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}

            </td>
        </tr>
    </table>


    <br>

    Kepada Yth.<br>
    Ketua PUK<br>
    PT Omron Manufacturing Of Indonesia<br>
    EJIP Industrial Park Plot 5C<br>
    Cikarang Selatan - Bekasi 17550


    <div class="section">

        <div class="section-title">Nama Kegiatan</div>
        {{ $laporan->nama_kegiatan }}

    </div>


    <div class="section">

        <div class="section-title">Tujuan</div>
        {{ $laporan->tujuan }}

    </div>


    <div class="section">

        <div class="section-title">Pelaksanaan</div>

        Hari/tgl &nbsp;&nbsp;:
        {{$laporan->tanggal ?? "-"}} <br>

        Jam &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
        {{ $laporan->jam ?? '-' }} <br>

        Tempat &nbsp;&nbsp;:
        {{ $laporan->tempat ?? '-' }}

    </div>


    <div class="section">

        <div class="section-title">
            Anggaran diajukan :
            @php
            $anggaran = $pengajuan->Biaya ?? $pengajuan->biaya ?? 0;
            @endphp

            Rp {{ number_format($anggaran,0,',','.') }}
        </div>

    </div>


    <div class="section">

        <div class="section-title">Anggaran Aktual</div>

        <table class="table-anggaran">

            <tr>
                <th width="8%">No</th>
                <th>Item</th>
                <th width="25%">Biaya</th>
            </tr>

            @php
            $no=1;
            @endphp

            @foreach($items as $row)


            <tr>
                <td align="center">{{ $no++ }}</td>
                <td>{{ $row['item'] }}</td>
                <td class="text-right">
                    Rp {{ number_format($row['nominal'],0,',','.') }}
                </td>
            </tr>


            @endforeach
            @if(count($items) == 0)
            <tr>
                <td colspan="3" align="center">Belum ada data yg di input</td>
            </tr>
            @else
            <tr>
                <th colspan="2">Total</th>
                <th class="text-right">
                    Rp {{ number_format($totalAktual,0,',','.') }}
                </th>
            </tr>
            @endif



        </table>

    </div>


    <div class="section">

        @php
        $anggaran = $pengajuan->Biaya ?? $pengajuan->biaya ?? 0;
        $sisa = $anggaran - $totalAktual;
        @endphp

        Sisa dana = Dana Anggaran - Anggaran Aktual <br>

        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        = Rp {{ number_format($anggaran,0,',','.') }}
        - Rp {{ number_format($totalAktual,0,',','.') }} <br>

        <strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            = Rp {{ number_format($sisa,0,',','.') }}
        </strong>

    </div>


    <br><br>

    Demikian Laporan ini kami sampaikan, untuk dipergunakan sebagaimana mestinya.<br>
    Terima Kasih

</body>

</html>
@extends('emails.layout')

@section('content')

    <h2 style="color:#16a34a;margin-bottom:10px;text-align:center">📄 Laporan Kegiatan Telah Diunggah</h2>

    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:600px;">
        <tr><td><b>Nama</b></td><td>{{ $p->nama }}</td></tr>
        <tr><td><b>Alokasi Dana</b></td><td>{{ $p->bidang }}</td></tr>
        <tr><td><b>Tujuan</b></td><td>{{ $p->tujuan }}</td></tr>
        <tr><td><b>Keperluan</b></td><td>{{ $p->keperluan }}</td></tr>
        <tr><td><b>Biaya</b></td><td>Rp{{ number_format($p->biaya,0,',','.') }}</td></tr>
        <tr><td><b>Tanggal Pengajuan</b></td><td>{{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('d F Y') }}</td></tr>
	<tr><td><b>Aktual Biaya</b></td><td>Rp{{ number_format($p->aktual_biaya,0,',','.') }}</td></tr>
    </table>


    @if($statusDana == 'sisa')

    <p style="margin-top:16px;color:#16a34a;font-weight:bold">
        💰 Terdapat sisa dana sebesar 
        Rp. {{ number_format($selisih,0,',','.') }} 
        yang harus difollow-up ke {{$p->nama}}.
    </p>

    @elseif($statusDana == 'kurang')

    <p style="margin-top:16px;color:#dc2626;font-weight:bold">
        ⚠️ Terdapat kekurangan dana sebesar 
        Rp. {{ number_format($selisih,0,',','.') }} 
        yang perlu ditransfer ke {{$p->nama}}.
    </p>

    @else

    <p style="margin-top:16px;color:#2563eb;font-weight:bold">
        ✔️ Aktual dana sesuai dengan biaya yang diajukan.
    </p>

    @endif

    <p style="margin-top:16px">
	<p style="margin-top:16px;">
	    📎 File laporan kegiatan telah dilampirkan pada email ini.
	</p>
    </p>

@endsection

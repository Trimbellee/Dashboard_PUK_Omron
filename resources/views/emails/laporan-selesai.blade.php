@extends('emails.layout')

@section('content')
    <h2 style="color:#16a34a;margin-bottom:10px;text-align:center">🎉 Laporan Bendahara Disetujui Penuh</h2>

    <p>Halo, Laporan Bendahara berikut telah disetujui penuh oleh Sekretaris dan Ketua:</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:600px;">
        <tr><td><b>Periode</b></td><td>{{ \Carbon\Carbon::create()->month($laporan->bulan)->translatedFormat('F') }} {{ $laporan->tahun }}</td></tr>
        <tr><td><b>Disetujui Sekretaris</b></td><td>{{ $laporan->sekretaris_nama }}</td></tr>
        <tr><td><b>Disetujui Ketua</b></td><td>{{ $laporan->ketua_nama }}</td></tr>
        <tr><td><b>Status</b></td><td><strong>Selesai</strong></td></tr>
    </table>
@endsection
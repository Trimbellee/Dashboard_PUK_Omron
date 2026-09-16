@extends('emails.layout')

@section('content')
    <h2 style="color:#2563eb;margin-bottom:10px;text-align:center">📩 Laporan Bendahara Baru</h2>

    <p>Halo, Bendahara telah mengajukan Laporan Bendahara untuk periode berikut. Mohon segera diperiksa:</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:600px;">
        <tr><td><b>Periode</b></td><td>{{ \Carbon\Carbon::create()->month($laporan->bulan)->translatedFormat('F') }} {{ $laporan->tahun }}</td></tr>
        <tr><td><b>Diajukan oleh</b></td><td>{{ $laporan->bendahara_nama }}</td></tr>
        <tr><td><b>Tanggal Diajukan</b></td><td>{{ \Carbon\Carbon::parse($laporan->bendahara_ttd_at)->translatedFormat('d F Y H:i') }}</td></tr>
        <tr><td><b>Status</b></td><td><strong>Menunggu Persetujuan Sekretaris</strong></td></tr>
    </table>
@endsection
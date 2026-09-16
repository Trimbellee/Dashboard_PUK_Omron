@extends('emails.layout')

@section('content')
    <h2 style="color:#dc2626;margin-bottom:10px;text-align:center">❌ Laporan Bendahara Ditolak</h2>

    <p>Halo, Laporan Bendahara berikut ditolak oleh <strong>{{ $role }}</strong> dan dikembalikan untuk direvisi:</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:600px;">
        <tr><td><b>Periode</b></td><td>{{ \Carbon\Carbon::create()->month($laporan->bulan)->translatedFormat('F') }} {{ $laporan->tahun }}</td></tr>
        <tr><td><b>Ditolak oleh</b></td><td>{{ $laporan->ditolak_oleh }} ({{ $role }})</td></tr>
        <tr><td><b>Alasan</b></td><td>{{ $catatan }}</td></tr>
        <tr><td><b>Status</b></td><td><strong>Dikembalikan ke Draft</strong></td></tr>
    </table>

    <p>Mohon segera lakukan revisi dan ajukan kembali.</p>
@endsection
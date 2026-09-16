@extends('emails.layout')

@section('content')

    <h2 style="color:#2563eb;margin-bottom:10px;text-align:center">📌 Status Pengajuan: {{ $status }}</h2>

    <p>Rincian pengajuan:</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:600px;">
        <tr><td><b>Nama</b></td><td>{{ $p->nama }}</td></tr>
        <tr><td><b>Alokasi Dana</b></td><td>{{ $p->bidang }}</td></tr>
        <tr><td><b>Tujuan</b></td><td>{{ $p->tujuan }}</td></tr>
        <tr><td><b>Keperluan</b></td><td>{{ $p->keperluan }}</td></tr>
        <tr><td><b>Biaya</b></td><td>Rp{{ number_format($p->biaya,0,',','.') }}</td></tr>
        <tr><td><b>Tanggal Pengajuan</b></td><td>{{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('d F Y') }}</td></tr>
        <tr><td><b>Catatan</b></td><td>{{ $p->catatan ?? '-' }}</td></tr>
    </table>

@endsection

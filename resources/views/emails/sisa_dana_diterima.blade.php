@extends('emails.layout')

@section('content')

<h2 style="text-align:center;color:#2563eb;margin-bottom:20px;">
    Konfirmasi Pengembalian Sisa Dana Diterima
</h2>

<p>Halo {{ $p->nama }},</p>

<p>Sisa dana sebesar <b style="color:#16a34a;">Rp {{ number_format($p->dikembalikan,0,',','.') }}</b> dari pengajuan berikut sudah diterima oleh bendahara.</p>

<table cellpadding="6" style="border-collapse:collapse;width:100%;max-width:600px">
<tr><td><b>PIC</b></td><td>{{ $p->nama }}</td></tr>
<tr><td><b>Tujuan</b></td><td>{{ $p->tujuan }}</td></tr>
<tr><td><b>Keperluan</b></td><td>{{ $p->keperluan }}</td></tr>
<tr><td><b>Biaya Pengajuan</b></td><td>Rp {{ number_format($p->biaya,0,',','.') }}</td></tr>
<tr><td><b>Aktual Biaya</b></td><td>Rp {{ number_format($p->aktual_biaya,0,',','.') }}</td></tr>
</table>

<br>
<br>
<p>Terima kasih.</p>

@endsection
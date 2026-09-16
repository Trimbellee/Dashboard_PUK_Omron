@extends('emails.layout')

@section('content')

<h2 style="text-align:center;color:#2563eb;margin-bottom:20px;">
    Informasi Selisih Dana
</h2>

<p>Hallo <b>{{ $p->nama }}</b>,</p>

<p>Terima kasih telah mengupload laporan kegiatan 🙏.</p>

@if($statusDana == 'sisa')

<p>
Berdasarkan laporan kegiatan yang Anda kirimkan, terdapat sisa dana sebesar:
<b style="color:#16a34a;">Rp {{ number_format($selisih,0,',','.') }}</b>.
</p>

<p>Mohon segera untuk <b>mengembalikan</b> sisa dana ke Bendahara.</p>

@elseif($statusDana == 'kurang')

<p>
Berdasarkan laporan kegiatan yang Anda kirimkan, terdapat kekurangan dana sebesar:
<b style="color:#dc2626;">Rp {{ number_format($selisih,0,',','.') }}</b>.
</p>

<p>
Kekurangan dana akan ditransfer oleh Bendahara. Apabila dalam waktu
<b>1 x 24 jam</b> dana belum diterima, silakan melakukan follow-up kepada Bendahara.
</p>

@endif

<br>

<p>Berikut rincian kegiatannya:</p>

<table cellpadding="6" style="border-collapse:collapse;width:100%;max-width:600px">
<tr><td><b>PIC</b></td><td>{{ $p->nama }}</td></tr>
<tr><td><b>Tujuan</b></td><td>{{ $p->tujuan }}</td></tr>
<tr><td><b>Keperluan</b></td><td>{{ $p->keperluan }}</td></tr>
<tr><td><b>Biaya Pengajuan</b></td><td>Rp {{ number_format($p->biaya,0,',','.') }}</td></tr>
<tr><td><b>Aktual Biaya</b></td><td>Rp {{ number_format($p->aktual_biaya,0,',','.') }}</td></tr>
</table>

<br>

<p>Terima kasih.</p>

@endsection
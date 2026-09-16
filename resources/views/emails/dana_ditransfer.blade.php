@extends('emails.layout')

@section('content')

<h2>Dana Pengajuan Sudah Ditransfer</h2>

<p>Halo <b>{{ $p->nama }}</b>,</p>

<p>Dana untuk pengajuan berikut sudah berhasil ditransfer:</p>

<ul>
<li><b>Alokasi Dana:</b> {{ $p->bidang }}</li>
<li><b>Tujuan:</b> {{ $p->tujuan }}</li>
<li><b>Keperluan:</b> {{ $p->keperluan }}</li>
<li><b>Jumlah Dana:</b> Rp {{ number_format($p->biaya,0,',','.') }}</li>
<li><b>Tanggal Transfer:</b> {{ \Carbon\Carbon::parse($p->tanggal_transfer)->format('d M Y') }}</li>
</ul>

<p>Silakan gunakan dana sesuai keperluan kegiatan dan jangan lupa mengupload laporan setelah kegiatan selesai.</p>

<p>Terima kasih.</p>

@endsection
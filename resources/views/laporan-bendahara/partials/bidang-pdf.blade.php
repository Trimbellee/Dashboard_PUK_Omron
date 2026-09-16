@if(!empty($data))
<p style="font-weight:bold; margin:4px 0;">
    {{ $judul }}
</p>

<table width="100%" style="font-size:8.5pt;">
<thead>
<tr>
    <th>Tgl</th>
    <th>Tujuan</th>
    <th>Keperluan</th>
    <th>Realisasi</th>
</tr>
</thead>
<tbody>

@php $total = 0; @endphp

@foreach($data as $r)
@php
    $nominal = $r['aktual'] ?? $r['nominal'] ?? 0;
    $total += $nominal;
@endphp
<tr>
    <td>{{ $r['tanggal'] ?? '' }}</td>
    <td>{{ $r['tujuan'] ?? ''}}</td>
    <td>{{ $r['keperluan'] ?? '' }}</td>
    <td align="right">{{ number_format($nominal,0,',','.') }}</td>
</tr>
@endforeach

<tr style="font-weight:bold;background:#eee;">
    <td colspan="3">TOTAL</td>
    <td align="right">{{ number_format($total,0,',','.') }}</td>
</tr>

</tbody>
</table>
@endif


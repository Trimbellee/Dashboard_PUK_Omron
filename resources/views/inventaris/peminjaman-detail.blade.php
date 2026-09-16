<div class="detail-item-modal">
    <span class="detail-label-modal">Barang</span>
    <span class="detail-value-modal">{{ $peminjaman->barang->nama_barang }} ({{ $peminjaman->barang->type }})</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Kode Barang</span>
    <span class="detail-value-modal">{{ $peminjaman->barang->kode_barang ?? '-' }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Peminjam</span>
    <span class="detail-value-modal">{{ $peminjaman->peminjam_nama }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Kontak</span>
    <span class="detail-value-modal">{{ $peminjaman->peminjam_kontak ?? '-' }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Tanggal Pinjam</span>
    <span class="detail-value-modal">{{ $peminjaman->tanggal_pinjam?->translatedFormat('d M Y') ?? '-' }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Tanggal Kembali</span>
    <span class="detail-value-modal">{{ $peminjaman->tanggal_kembali?->translatedFormat('d M Y') ?? '-' }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Status</span>
    <span class="detail-value-modal">
        @php
            $statusColors = ['aktif' => 'warning', 'selesai' => 'success', 'terlambat' => 'danger', 'hilang' => 'danger'];
            $statusIcons = ['aktif' => '🟢', 'selesai' => '✅', 'terlambat' => '🔴', 'hilang' => '❓'];
        @endphp
        <span class="badge badge-{{ $statusColors[$peminjaman->status] ?? 'secondary' }}">
            {{ $statusIcons[$peminjaman->status] ?? '' }} {{ ucfirst($peminjaman->status) }}
        </span>
    </span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Petugas Pinjam</span>
    <span class="detail-value-modal">{{ $peminjaman->petugas->username }}</span>
</div>
<div class="detail-item-modal">
    <span class="detail-label-modal">Diterima Oleh</span>
    <span class="detail-value-modal">{{ $peminjaman->penerima->username ?? '-' }}</span>
</div>
@if($peminjaman->keterangan)
<div class="detail-item-modal">
    <span class="detail-label-modal">Keterangan</span>
    <span class="detail-value-modal">{{ $peminjaman->keterangan }}</span>
</div>
@endif
@if($peminjaman->kondisi_kembali)
<div class="detail-item-modal">
    <span class="detail-label-modal">Kondisi Kembali</span>
    <span class="detail-value-modal">
        @php
            $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'diperbaiki' => 'warning'];
            $kondisiIcons = ['baik' => '✅', 'rusak' => '⚠️', 'diperbaiki' => '🔧'];
        @endphp
        <span class="badge badge-{{ $kondisiColors[$peminjaman->kondisi_kembali] ?? 'secondary' }}">
            {{ $kondisiIcons[$peminjaman->kondisi_kembali] ?? '' }} {{ ucfirst($peminjaman->kondisi_kembali) }}
        </span>
    </span>
</div>
@endif
@if($peminjaman->status === 'hilang')
<div class="detail-item-modal">
    <span class="detail-label-modal">Kronologi Kehilangan</span>
    <span class="detail-value-modal" style="white-space:pre-wrap;word-break:break-word;">{{ $peminjaman->catatan_kembali }}</span>
</div>
@elseif($peminjaman->catatan_kembali)
<div class="detail-item-modal">
    <span class="detail-label-modal">Catatan Kembali</span>
    <span class="detail-value-modal" style="white-space:pre-wrap;word-break:break-word;">{{ $peminjaman->catatan_kembali }}</span>
</div>
@endif
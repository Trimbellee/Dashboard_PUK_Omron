@extends('layouts.app')

@section('title', 'Detail Barang | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/show.css'])
@endpush

@section('content')
<div class="inv-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">👁️</div>
            <div>
                <h1 class="inv-title">Detail Barang</h1>
                <p class="inv-subtitle">Informasi lengkap unit inventaris</p>
            </div>
        </div>
        <div class="inv-header-actions">
            <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">← Kembali</a>
            @if(in_array(session('user')['role'] ?? null, ['Ketua', 'Sekretaris']))
            <a href="{{ route('inventaris.edit', $barang->id) }}" class="btn btn-primary">✏️ Edit</a>
            @endif
        </div>
    </div>

    <div class="inv-detail-grid">
        <!-- Informasi Barang -->
        <div class="inv-detail-card">
            <h2 class="inv-card-title">📋 Informasi Barang</h2>
            <div class="detail-item">
                <span class="detail-label">Nama Barang</span>
                <span class="detail-value">{{ $barang->nama_barang }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Type</span>
                <span class="detail-value">{{ $barang->type }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Kategori</span>
                <span class="detail-value">{{ $barang->kategori }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Kode Barang</span>
                <span class="detail-value" @if($barang->kode_barang) data-copy="{{ $barang->kode_barang }}" @endif>{{ $barang->kode_barang ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Kondisi</span>
                <span class="detail-value">
                    @php $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'diperbaiki' => 'warning']; @endphp
                    <span class="badge badge-{{ $kondisiColors[$barang->kondisi] ?? 'secondary' }}">{{ ucfirst($barang->kondisi) }}</span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    @php
                        $statusColors = ['tersedia' => 'success', 'dispose' => 'secondary', 'dijual' => 'info', 'hilang' => 'danger'];
                        $statusIcons = ['tersedia' => '✅', 'dispose' => '🗑️', 'dijual' => '💰', 'hilang' => '❓'];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$barang->status] ?? 'secondary' }}">
                        {{ $statusIcons[$barang->status] ?? '' }} {{ ucfirst($barang->status) }}
                    </span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Tanggal Pembelian</span>
                <span class="detail-value">{{ $barang->tanggal_pembelian ? $barang->tanggal_pembelian->format('d M Y') : '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Harga Perolehan</span>
                <span class="detail-value">{{ $barang->harga_perolehan ? 'Rp ' . number_format($barang->harga_perolehan, 0, ',', '.') : '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Dibuat oleh</span>
                <span class="detail-value">{{ $barang->creator->username }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Keterangan</span>
                <span class="detail-value">{{ $barang->keterangan ?? '-' }}</span>
            </div>
            @if(
                $barang->isTersedia()
            )
                <div class="inv-detail-actions">
                    <a href="{{ route('inventaris.pinjam', $barang->id) }}"
                    class="btn btn-success btn-block">
                        📋 Pinjam Barang
                    </a>
                </div>
            @endif
        </div>

        <!-- Riwayat Peminjaman -->
        <div class="inv-detail-card">
            <h2 class="inv-card-title">📜 Riwayat Peminjaman</h2>
            @if($riwayatPeminjaman->isEmpty())
                <div class="empty-state" style="padding:32px 12px;">
                    <div class="empty-icon">🗂️</div>
                    <div class="empty-text">Belum ada riwayat</div>
                    <div class="empty-hint">Riwayat peminjaman unit ini akan muncul di sini.</div>
                </div>
            @else
                <div class="riwayat-list">
                    @foreach($riwayatPeminjaman as $riwayat)
                    <div class="riwayat-item" data-status="{{ $riwayat->status }}">
                        <div class="riwayat-header">
                            <span class="riwayat-peminjam">{{ $riwayat->peminjam_nama }}</span>
                            @php $statusColors = ['aktif' => 'warning', 'selesai' => 'success', 'terlambat' => 'danger' ,  'hilang' => 'danger']; @endphp
                            <span class="badge badge-{{ $statusColors[$riwayat->status] ?? 'secondary' }}">{{ ucfirst($riwayat->status) }}</span>
                        </div>
                        <div class="riwayat-details">
                            <span>📅 {{ $riwayat->tanggal_pinjam->format('d M Y') }}</span>
                            <span>→ {{ $riwayat->tanggal_kembali ? $riwayat->tanggal_kembali->format('d M Y') : 'Belum kembali' }}</span>
                            <span>👤 Dipinjamkan oleh {{ $riwayat->petugas->username }}</span>
                            @if($riwayat->penerima)
                                @if($riwayat->status === 'hilang')
                                <span>✅ Diproses oleh {{ $riwayat->penerima->username }}</span>
                                @elseif($riwayat->catatan_kembali)
                                <span>✅ Diterima oleh {{ $riwayat->penerima->username }}</span>
                                @endif
                            @endif
                        </div>
                        @if($riwayat->keterangan)
                            <div class="riwayat-keterangan">
                                <strong>Keterangan:</strong> {{ $riwayat->keterangan }}
                            </div>
                        @endif
                        @if($riwayat->kondisi_kembali)
                            <div class="riwayat-keterangan" style="margin-top:4px;">
                                <strong>Kondisi Kembali:</strong>
                                @php
                                    $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'diperbaiki' => 'warning'];
                                    $kondisiIcons = ['baik' => '✅', 'rusak' => '⚠️', 'diperbaiki' => '🔧'];
                                @endphp
                                <span class="badge badge-{{ $kondisiColors[$riwayat->kondisi_kembali] ?? 'secondary' }}">
                                    {{ $kondisiIcons[$riwayat->kondisi_kembali] ?? '' }} {{ ucfirst($riwayat->kondisi_kembali) }}
                                </span>
                            </div>
                        @endif
                        @if($riwayat->status === 'hilang')
                            <div class="riwayat-keterangan" style="margin-top:4px;">
                                <strong>Catatan Hilang:</strong> {{ $riwayat->catatan_kembali }}
                            </div>
                        @elseif($riwayat->catatan_kembali)
                            <div class="riwayat-keterangan" style="margin-top:4px;">
                                <strong>Catatan Kembali:</strong> {{ $riwayat->catatan_kembali }}
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/show.js'])
@endpush

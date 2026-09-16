@extends('layouts.app')

@section('title', 'Inventaris Barang | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/index.css'])
@endpush

@section('content')
<div class="inv-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <!-- Header -->
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">📦</div>
            <div>
                <h1 class="inv-title">Inventaris Barang</h1>
                <p class="inv-subtitle">Setiap unit dicatat terpisah dengan kode barangnya masing-masing</p>
            </div>
        </div>
        <div class="inv-header-actions">
            <a href="{{ route('inventaris.peminjaman') }}" class="btn btn-ghost">
                <span>📋</span> Peminjaman
            </a>
            @if(in_array(session('user')['role'] ?? null, ['Ketua', 'Sekretaris']))
            <a href="{{ route('inventaris.create') }}" class="btn btn-primary">
                <span>➕</span> Tambah Barang
            </a>
            @endif
        </div>
    </div>

    <!-- Statistik -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['total'] }}</div>
                <div class="stat-label">Total Unit</div>
            </div>
        </div>
        <div class="stat-card stat-tersedia">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['tersedia'] }}</div>
                <div class="stat-label">Tersedia</div>
            </div>
        </div>
        <div class="stat-card stat-dipinjam">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['dipinjam'] }}</div>
                <div class="stat-label">Sedang Dipinjam</div>
            </div>
        </div>
        <div class="stat-card stat-rusak">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['rusak'] }}</div>
                <div class="stat-label">Kondisi Rusak</div>
            </div>
        </div>
        <div class="stat-card stat-perbaikan">
            <div class="stat-icon">🔧</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['diperbaiki'] }}</div>
                <div class="stat-label">Diperbaiki</div>
            </div>
        </div>
        <div class="stat-card stat-dispose">
            <div class="stat-icon">🗑️</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['dispose'] }}</div>
                <div class="stat-label">Dispose</div>
            </div>
        </div>
        <div class="stat-card stat-dijual">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['dijual'] }}</div>
                <div class="stat-label">Dijual</div>
            </div>
        </div>
        <div class="stat-card stat-hilang">
            <div class="stat-icon">❓</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['hilang'] }}</div>
                <div class="stat-label">Hilang</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="filter-bar">
        <div class="filter-left">
            <div class="search-field">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari nama, type, atau kode barang...">
            </div>
            <select id="filterKategori" class="form-input">
                <option value="">Semua Kategori</option>
                <option value="elektronik">Elektronik</option>
                <option value="furniture">Furniture</option>
                <option value="alat tulis">Alat Tulis</option>
                <option value="perlengkapan">Perlengkapan</option>
                <option value="lainnya">Lainnya</option>
            </select>
            <select id="filterStatus" class="form-input">
                <option value="">Semua Status</option>
                <option value="tersedia">Tersedia</option>
                <option value="dipinjam">Sedang Dipinjam</option>
                <option value="dispose">Dispose</option>
                <option value="dijual">Dijual</option>
                <option value="hilang">Hilang</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th class="sticky-col">No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Type</th>
                        <th>Kategori</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barang as $index => $item)
                    <tr data-row
                        data-nama="{{ strtolower($item->nama_barang) }}"
                        data-type="{{ strtolower($item->type) }}"
                        data-kode="{{ strtolower($item->kode_barang ?? '') }}"
                        data-kategori="{{ strtolower($item->kategori) }}"
                        data-status="{{ strtolower($item->status) }}"
                        data-dipinjam="{{ $item->isSedangDipinjam() ? '1' : '0' }}">
                        <td data-label="No" class="sticky-col">{{ $index + 1 }}</td>
                        <td data-label="Kode"><span class="kode-badge">{{ $item->kode_barang ?? '-' }}</span></td>
                        <td data-label="Nama Barang"><div class="barang-nama">{{ $item->nama_barang }}</div></td>
                        <td data-label="Type"><span class="type-badge">{{ $item->type }}</span></td>
                        <td data-label="Kategori"><span class="kategori-badge">{{ $item->kategori }}</span></td>
                        <td data-label="Kondisi">
                            @php
                                $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'diperbaiki' => 'warning'];
                            @endphp
                            <span class="badge badge-{{ $kondisiColors[$item->kondisi] ?? 'secondary' }}">
                                {{ ucfirst($item->kondisi) }}
                            </span>
                        </td>
                        <td data-label="Status">
                            @php
                                $statusColors = ['tersedia' => 'success', 'dispose' => 'secondary', 'dijual' => 'info', 'hilang' => 'danger'];
                                $statusIcons = ['tersedia' => '✅', 'dispose' => '🗑️', 'dijual' => '💰', 'hilang' => '❓'];
                            @endphp
                            <span class="badge badge-{{ $statusColors[$item->status] ?? 'secondary' }}">
                                {{ $statusIcons[$item->status] ?? '' }} {{ ucfirst($item->status) }}
                            </span>
                            @if($item->isSedangDipinjam())
                                <span class="badge badge-warning" style="margin-left:4px;">📋 Dipinjam</span>
                            @endif
                        </td>
                        <td data-label="Aksi" class="td-actions">
                            <div class="action-buttons">
                                <a href="{{ route('inventaris.show', $item->id) }}" class="btn-icon tone-blue" title="Detail">👁️</a>
                                @if(in_array(session('user')['role'] ?? null, ['Ketua', 'Sekretaris']))
                                <a href="{{ route('inventaris.edit', $item->id) }}" class="btn-icon tone-amber" title="Edit">✏️</a>
                                @endif
                                @if($item->isTersedia())
                                <a href="{{ route('inventaris.pinjam', $item->id) }}" class="btn-icon tone-green" title="Pinjam">📋</a>
                                @endif
                                @if(in_array(session('user')['role'] ?? null, ['Ketua', 'Sekretaris']))
                                <form action="{{ route('inventaris.destroy', $item->id) }}" method="POST" class="d-inline js-confirm-delete" data-nama-barang="{{ $item->nama_barang }} ({{ $item->kode_barang }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon tone-red" title="Hapus">🗑️</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-bar" id="inv-pagination"></div>
        @if($barang->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div class="empty-text">Belum ada data barang</div>
            <div class="empty-hint">Tambahkan barang pertama Anda untuk mulai mengelola inventaris.</div>
        </div>
        @else
        <div class="empty-state" style="display:none;">
            <div class="empty-icon">🔍</div>
            <div class="empty-text">Tidak ada barang yang cocok</div>
            <div class="empty-hint">Coba ubah kata kunci pencarian atau filter.</div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/index.js'])
@endpush

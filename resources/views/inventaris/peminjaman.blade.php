@extends('layouts.app')

@section('title', 'Manajemen Peminjaman | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/peminjaman.css'])
@endpush

@section('content')
<div class="inv-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <!-- Header -->
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">📋</div>
            <div>
                <h1 class="inv-title">Manajemen Peminjaman</h1>
                <p class="inv-subtitle">Kelola semua transaksi peminjaman barang</p>
            </div>
        </div>
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">← Kembali ke Inventaris</a>
    </div>

    <!-- Statistik -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['total'] }}</div>
                <div class="stat-label">Total Peminjaman</div>
            </div>
        </div>
        <div class="stat-card stat-aktif">
            <div class="stat-icon">🟢</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['aktif'] }}</div>
                <div class="stat-label">Aktif</div>
            </div>
        </div>
        <div class="stat-card stat-selesai">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['selesai'] }}</div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>
        <div class="stat-card stat-terlambat">
            <div class="stat-icon">🔴</div>
            <div class="stat-info">
                <div class="stat-value">{{ $statistik['terlambat'] }}</div>
                <div class="stat-label">Terlambat</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="filter-bar">
        <div class="tab-group">
            <button type="button" class="tab-btn active" data-status="semua">📋 Semua</button>
            <button type="button" class="tab-btn" data-status="aktif">🟢 Aktif</button>
            <button type="button" class="tab-btn" data-status="selesai">✅ Selesai</button>
            <button type="button" class="tab-btn" data-status="terlambat">🔴 Terlambat</button>
            <button type="button" class="tab-btn" data-status="hilang">❓ Hilang</button>
        </div>
        <div class="search-field">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchPeminjaman" class="form-input" placeholder="Cari peminjam atau barang...">
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-scroll">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th class="sticky-col">No</th>
                        <th>Barang</th>
                        <th>Peminjam</th>
                        <th>Kontak</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Kondisi Kembali</th>
                        <th>Catatan</th>
                        <th>Petugas Pinjam</th>
                        <th>Diterima/Diproses</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="peminjamanBody">
                    @forelse($peminjaman as $index => $item)
                    <tr data-status="{{ $item->status }}">
                        <td data-label="No" class="sticky-col">{{ $index + 1 }}</td>
                        <td data-label="Barang">
                            <div class="barang-info">
                                <div class="barang-nama">{{ $item->barang->nama_barang }}</div>
                                <div class="barang-type">{{ $item->barang->type }} · <span class="kode-badge">{{ $item->barang->kode_barang ?? '-' }}</span></div>
                            </div>
                        </td>
                        <td data-label="Peminjam"><div class="peminjam-nama">{{ $item->peminjam_nama }}</div></td>
                        <td data-label="Kontak">{{ $item->peminjam_kontak ?? '-' }}</td>
                        <td data-label="Tgl Pinjam">{{ $item->tanggal_pinjam->translatedFormat('d M Y') ?? '-'  }}</td>
                        <td data-label="Tgl Kembali">
                            @if($item->tanggal_kembali)
                                {{ $item->tanggal_kembali->translatedFormat('d M Y') ?? '-'  }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            @php
                                $statusColors = ['aktif' => 'warning', 'selesai' => 'success', 'terlambat' => 'danger', 'hilang' => 'danger'];
                                $statusIcons = ['aktif' => '🟢', 'selesai' => '✅', 'terlambat' => '🔴', 'hilang' => '❓'];
                            @endphp
                            <span class="badge badge-{{ $statusColors[$item->status] ?? 'secondary' }}">
                                {{ $statusIcons[$item->status] ?? '' }} {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td data-label="Kondisi Kembali">
                            @if($item->kondisi_kembali)
                                @php
                                    $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'diperbaiki' => 'warning'];
                                    $kondisiIcons = ['baik' => '✅', 'rusak' => '⚠️', 'diperbaiki' => '🔧'];
                                @endphp
                                <span class="badge badge-{{ $kondisiColors[$item->kondisi_kembali] ?? 'secondary' }}">
                                    {{ $kondisiIcons[$item->kondisi_kembali] ?? '' }} {{ ucfirst($item->kondisi_kembali) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td data-label="Catatan">
                            @if($item->catatan_kembali)
                                <span class="catatan-tooltip" 
                                      data-tooltip="{{ $item->catatan_kembali }}"
                                      title="{{ $item->catatan_kembali }}">
                                    📝 {{ Str::limit($item->catatan_kembali, 20) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td data-label="Petugas Pinjam">{{ $item->petugas->username }}</td>
                        <td data-label="Diterima Oleh">
                            @if($item->penerima)
                                {{ $item->penerima->username }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td data-label="Aksi" class="td-actions">
                        <div class="action-buttons">
                            @if(in_array($item->status, ['aktif', 'terlambat']))
                                <button type="button" 
                                        class="btn btn-sm btn-success" 
                                        title="Kembalikan"
                                        onclick="showKembaliForm({{ $item->id }}, '{{ $item->barang->nama_barang }}', '{{ $item->peminjam_nama }}')">
                                    🔄 Kembalikan
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-danger" 
                                        title="Tandai Hilang"
                                        onclick="showHilangForm({{ $item->id }}, '{{ $item->barang->nama_barang }}', '{{ $item->peminjam_nama }}')">
                                    ❓ Hilang
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-secondary" title="Detail" onclick="showDetail({{ $item->id }})">👁️ Detail</button>
                        </div>
                    </td>
                    </tr>
                    @empty
                    @endforelse
                    <tr id="peminjamanEmptyRow" style="{{ $peminjaman->isEmpty() ? '' : 'display:none;' }}">
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <div class="empty-text">Belum ada data peminjaman</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination-bar" id="peminjaman-pagination"></div>
    </div>
</div>

<!-- Modal Konfirmasi Pengembalian -->
<div class="inv-modal-backdrop" id="kembaliModal">
    <div class="inv-modal">
        <div class="inv-modal-header">
            <h2 class="inv-modal-title">🔄 Konfirmasi Pengembalian</h2>
            <button type="button" class="inv-modal-close" onclick="closeKembaliModal()">✕</button>
        </div>
        <form id="formKembali" method="POST">
            @csrf
            <div class="inv-modal-body">
                <div style="margin-bottom:16px;">
                    <p style="color:var(--text-secondary);font-size:14px;margin:0;">
                        Pastikan kondisi barang sebelum mengembalikan:
                    </p>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" for="kondisi_kembali">
                        Kondisi Barang Saat Dikembalikan <span class="required">*</span>
                    </label>
                    <select id="kondisi_kembali" name="kondisi_kembali" class="form-input" required>
                        <option value="baik">✅ Baik</option>
                        <option value="rusak">⚠️ Rusak</option>
                        <option value="perbaikan">🔧 Perbaikan</option>
                    </select>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                        Pilih "Rusak" jika barang mengalami kerusakan saat dipinjam.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catatan_kembali">Catatan Tambahan</label>
                    <textarea id="catatan_kembali" name="catatan_kembali" 
                              class="form-input" rows="2" 
                              placeholder="Deskripsikan kondisi kerusakan jika ada..."></textarea>
                </div>

                <div style="margin-top:12px;padding:12px;background:var(--bg-base);border-radius:var(--inv-radius-sm);border:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:var(--text-muted);">Barang:</span>
                        <span style="font-weight:600;" id="kembaliBarangNama">-</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:4px;">
                        <span style="color:var(--text-muted);">Peminjam:</span>
                        <span style="font-weight:600;" id="kembaliPeminjamNama">-</span>
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeKembaliModal()">Batal</button>
                <button type="submit" class="btn btn-success">🔄 Kembalikan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hilang -->
<div class="inv-modal-backdrop" id="hilangModal">
    <div class="inv-modal">
        <div class="inv-modal-header">
            <h2 class="inv-modal-title">❓ Tandai Barang Hilang</h2>
            <button type="button" class="inv-modal-close" onclick="closeHilangModal()">✕</button>
        </div>
        <form id="formHilang" method="POST">
            @csrf
            <div class="inv-modal-body">
                <div style="margin-bottom:16px;">
                    <p style="color:var(--text-secondary);font-size:14px;margin:0;">
                        Barang akan ditandai <strong>hilang</strong> dan tidak lagi muncul sebagai tersedia.
                        Tindakan ini bisa dikoreksi manual lewat halaman Edit kalau barang ditemukan kembali.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catatan_hilang">
                        Kronologi Kehilangan <span class="required">*</span>
                    </label>
                    <textarea id="catatan_hilang" name="catatan_kembali" 
                              class="form-input" rows="3" required
                              placeholder="Jelaskan kapan dan bagaimana barang diketahui hilang..."></textarea>
                </div>

                <div style="margin-top:12px;padding:12px;background:var(--bg-base);border-radius:var(--inv-radius-sm);border:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:var(--text-muted);">Barang:</span>
                        <span style="font-weight:600;" id="hilangBarangNama">-</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:4px;">
                        <span style="color:var(--text-muted);">Peminjam:</span>
                        <span style="font-weight:600;" id="hilangPeminjamNama">-</span>
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeHilangModal()">Batal</button>
                <button type="submit" class="btn btn-danger">❓ Tandai Hilang</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail -->
<div class="inv-modal-backdrop" id="detailModal">
    <div class="inv-modal">
        <div class="inv-modal-header">
            <h2 class="inv-modal-title">📋 Detail Peminjaman</h2>
            <button type="button" class="inv-modal-close" onclick="closeDetail()">✕</button>
        </div>
        <div class="inv-modal-body" id="detailContent">
            <!-- Load via AJAX -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/peminjaman.js'])
@endpush
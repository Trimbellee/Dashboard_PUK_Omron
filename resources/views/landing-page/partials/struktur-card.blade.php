{{-- resources/views/landing-page/partials/struktur-card.blade.php --}}
@php
    $jabatan = is_array($node) ? ($node['jabatan'] ?? null) : ($node->jabatan ?? null);
    $anggota = is_array($node) ? ($node['anggota'] ?? null) : ($node->anggota ?? null);
    $nama = $anggota && is_object($anggota) ? $anggota->nama_lengkap : '—';
    $foto = $anggota && is_object($anggota) ? $anggota->foto : null;
    $departemen = $anggota && is_object($anggota) ? $anggota->departemen : null;
    $jabatanPerusahaan = $anggota && is_object($anggota) ? $anggota->jabatan : null;
    $tanggalMulai = $anggota && is_object($anggota) ? $anggota->tanggal_mulai_bekerja : null;
    $pendidikan = $anggota && is_object($anggota) ? $anggota->pendidikan_terakhir : null;
    $umur = $anggota && is_object($anggota) && $anggota->tanggal_lahir 
        ? \Carbon\Carbon::parse($anggota->tanggal_lahir)->age 
        : null;
    $isActive = $anggota && is_object($anggota) ? $anggota->is_active : false;
    $isKetua = strtoupper(trim($jabatan)) === 'KETUA';
    
    // Format tanggal mulai bekerja
    $tanggalMulaiFormatted = $tanggalMulai 
        ? \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('d F Y') 
        : null;
    
    // Level untuk warna
    $levelClass = '';
    if (strtoupper(trim($jabatan)) === 'KETUA') $levelClass = 'level-0';
    else if (strtoupper(trim($jabatan)) === 'SEKRETARIS' || strtoupper(trim($jabatan)) === 'BENDAHARA') $levelClass = 'level-1';
    else if (preg_match('/^WAKIL KETUA BIDANG/i', trim($jabatan))) $levelClass = 'level-2';
    else if (preg_match('/^WAKIL SEKRETARIS BIDANG/i', trim($jabatan))) $levelClass = 'level-3';
    
    // Escape data untuk JavaScript
    $modalData = json_encode([
        'nama' => $nama,
        'departemen' => $departemen ?? '-',
        'jabatan' => $jabatanPerusahaan ?? '-',
        'umur' => $umur ?? '-',
        'pendidikan' => $pendidikan ?? '-',
        'mulaiBekerja' => $tanggalMulaiFormatted ?? '-',
        'foto' => $foto ? asset('storage/' . $foto) : null,
        'jabatanPuk' => $jabatan ?? ''
    ]);
@endphp

<div class="lp-tree-card {{ $levelClass }} {{ $isKetua ? 'lp-tree-card--ketua' : '' }}"
     @click="window.openModal && window.openModal({{ $modalData }})"
     role="button"
     tabindex="0"
     @keydown.enter="window.openModal && window.openModal({{ $modalData }})"
     aria-haspopup="dialog">
    
    {{-- Level indicator --}}
    <div class="lp-tree-card__level-indicator"></div>
    
    {{-- Badge untuk Ketua --}}
    @if($isKetua)
        <div class="lp-tree-card__badge">★</div>
    @endif
    
    <div class="lp-tree-card__avatar {{ $isActive ? 'lp-tree-card__avatar--active' : '' }}">
        @if($foto)
            <img src="{{ asset('storage/' . $foto) }}" alt="{{ $nama }}">
        @else
            <span class="lp-tree-card__avatar-placeholder">
                {{ substr($nama, 0, 2) }}
            </span>
        @endif
    </div>
    
    <div class="lp-tree-card__info">
        <div class="lp-tree-card__jabatan">{{ $jabatan }}</div>
        <div class="lp-tree-card__nama">{{ $nama }}</div>
        @if($anggota && is_object($anggota))
            <div class="lp-tree-card__details">
                @if($departemen)<span>Bagian : {{ $departemen }}</span>@endif
                @if($jabatanPerusahaan)<span>Posisi : {{ $jabatanPerusahaan }}</span>@endif
                @if($tanggalMulai)<span>Mulai : {{ \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('d F Y') }}</span>@endif
            </div>
        @endif
    </div>
    
    {{-- Mini tooltip --}}
    <div class="lp-tree-card__tooltip">
        Klik untuk detail
    </div>
</div>
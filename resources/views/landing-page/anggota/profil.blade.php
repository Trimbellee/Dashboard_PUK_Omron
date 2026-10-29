@extends('landing-page.layouts.app')

@section('title', 'Profil Saya - PUK OMRON')
@section('meta_description', 'Halaman profil anggota PUK OMRON.')

@section('content')
<section class="lp-section lp-profile-section">
    <div class="lp-container">

        {{-- ===== FLASH MESSAGE ===== --}}
        @if (session('success'))
            <div class="lp-profile-alert lp-profile-alert--success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="lp-profile-alert lp-profile-alert--error">{{ session('error') }}</div>
        @endif

        {{-- ===== HEADER PROFIL ===== --}}
        <div class="lp-profile-header">
            <div class="lp-profile-header__bg" aria-hidden="true"></div>

            <div class="lp-profile-header__content">
                {{-- Avatar --}}
                <div class="lp-profile-header__avatar-wrap">
                    <span class="lp-nav__avatar lp-profile-header__avatar">
                        <img src="{{ $anggota->foto_url }}" alt="{{ $anggota->nama_lengkap }}">
                    </span>
                    <span class="lp-profile-header__status" title="Aktif"></span>
                </div>

                {{-- Info --}}
                <div class="lp-profile-header__info">
                    <h1 class="lp-profile-header__name">{{ $anggota->nama_lengkap }}</h1>

                    <div class="lp-profile-header__meta">
                        <span class="lp-profile-badge">{{ ucwords($anggota->jabatan_puk ?? 'Anggota') }}</span>
                        <span class="lp-profile-header__nik">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="7" width="20" height="14" rx="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            NIK {{ $anggota->nik_karyawan }}
                        </span>
                    </div>

                    <div class="lp-profile-header__actions">
                        <a href="{{ route('anggota.profil.edit') }}" class="lp-btn lp-btn--ghost lp-profile-header__edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Edit Profil
                        </a>
                    </div>
                </div>

                {{-- QR Code --}}
                <div class="lp-profile-header__qrcode">
                    <div class="lp-profile-card lp-profile-card--qrcode">
                        <img
                            src="{{ route('anggota.profil.qrcode') }}"
                            alt="QR Code Absensi {{ $anggota->nama_lengkap }}"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== GRID INFORMASI ===== --}}
        <div class="lp-profile-grid">

            {{-- --- Data Pekerjaan --- --}}
            <div class="lp-profile-card">
                <h3 class="lp-profile-card__title">Data Pekerjaan</h3>
                <dl class="lp-profile-list">
                    <div class="lp-profile-list__item">
                        <dt>Departemen</dt>
                        <dd>{{ $anggota->departemen ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Jabatan</dt>
                        <dd>{{ $anggota->jabatan ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Status Karyawan</dt>
                        <dd>{{ $anggota->status_karyawan ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Tanggal Mulai Bekerja</dt>
                        <dd>{{ $anggota->tanggal_mulai_bekerja?->translatedFormat('d F Y') ?? '-' }}</dd>
                    </div>
                    
                </dl>
            </div>

            {{-- --- Data Pribadi --- --}}
            <div class="lp-profile-card">
                <h3 class="lp-profile-card__title">Data Pribadi</h3>
                <dl class="lp-profile-list">
                    <div class="lp-profile-list__item">
                        <dt>Jenis Kelamin</dt>
                        <dd>{{ $anggota->jenis_kelamin ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Tempat & Tanggal Lahir</dt>
                        <dd>
                            {{ $anggota->tempat_lahir ?? '-' }}, {{ $anggota->tempat_lahir && $anggota->tanggal_lahir ? ', ' : '' }}{{ $anggota->tanggal_lahir?->translatedFormat('d F Y') ?? '' }}
                        </dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Pendidikan Terakhir</dt>
                        <dd>{{ $anggota->pendidikan_terakhir ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Ukuran Kaos</dt>
                        <dd>{{ $anggota->ukuran_kaos ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Ukuran Kemeja</dt>
                        <dd>{{ $anggota->ukuran_kemeja ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Jenis Identitas</dt>
                        <dd>{{ $anggota->jenis_identitas ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Nomor Identitas</dt>
                        <dd>{{ $anggota->nomor_identitas ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- --- Kontak --- --}}
            <div class="lp-profile-card">
                <h3 class="lp-profile-card__title">Kontak</h3>
                <dl class="lp-profile-list">
                    <div class="lp-profile-list__item">
                        <dt>Email</dt>
                        <dd>{{ $anggota->email ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Nomor WhatsApp</dt>
                        <dd>{{ $anggota->nomor_whatsapp ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- --- Domisili --- --}}
            <div class="lp-profile-card">
                <h3 class="lp-profile-card__title">Alamat Domisili</h3>
                <dl class="lp-profile-list">
                    <div class="lp-profile-list__item">
                        <dt>Alamat</dt>
                        <dd>{{ $anggota->alamat_domisili ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kelurahan</dt>
                        <dd>{{ $wilayahDomisili['kelurahan'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kecamatan</dt>
                        <dd>{{ $wilayahDomisili['kecamatan'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kota/Kabupaten</dt>
                        <dd>{{ $wilayahDomisili['kota'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Provinsi</dt>
                        <dd>{{ $wilayahDomisili['provinsi'] ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- --- Alamat KTP --- --}}
            <div class="lp-profile-card">
                <h3 class="lp-profile-card__title">Alamat Sesuai KTP</h3>
                <dl class="lp-profile-list">
                    <div class="lp-profile-list__item">
                        <dt>Alamat</dt>
                        <dd>{{ $anggota->alamat_ktp ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kelurahan</dt>
                        <dd>{{ $wilayahKtp['kelurahan'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kecamatan</dt>
                        <dd>{{ $wilayahKtp['kecamatan'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Kota/Kabupaten</dt>
                        <dd>{{ $wilayahKtp['kota'] ?? '-' }}</dd>
                    </div>
                    <div class="lp-profile-list__item">
                        <dt>Provinsi</dt>
                        <dd>{{ $wilayahKtp['provinsi'] ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

        </div>

        <a href="{{ route('landing.index') }}" class="lp-auth__back">&larr; Kembali ke Beranda</a>
    </div>
</section>
@push('styles')
    @vite(['resources/css/landing-page/anggota/profile.css'])
@endpush
@endsection
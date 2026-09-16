<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard | PUK OMRON')</title>

<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/css/dashboard/app.css', 'resources/js/app.js', 'resources/js/dashboard/app.js'])

@stack('styles')
</head>

<body>

<!-- OVERLAY -->
<div id="overlay"></div>

<!-- SIDEBAR -->
<aside id="sidebar">
    <div class="sidebar-logo">
        <img src="/puk_omron.PNG" alt="PUK OMRON">
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>

        @php
        $menuItems = [
            ['route'=>'dashboard',            'icon'=>'🏠', 'label'=>'Home'],
            ['route'=>'agenda.index',          'icon'=>'📅', 'label'=>'Agenda'],
            ['route'=>'pengajuan.index',       'icon'=>'📄', 'label'=>'Pengajuan Dana'],
            ['route'=>'kode-surat.index',      'icon'=>'🧾', 'label'=>'Kode Penomoran Surat'],
            ['route'=>'filemanager.index',     'icon'=>'📁', 'label'=>'File Manager'],
            ['route'=>'anggota.index',         'icon'=>'👥', 'label'=>'Database Anggota'],  
            ['route'=>'laporan-belanja.index', 'icon'=>'📊', 'label'=>'Pengeluaran Di Koperasi'],
        ];
        @endphp

        @foreach($menuItems as $item)
        <a href="{{ route($item['route']) }}"
            class="nav-item {{ request()->routeIs(str_replace('.index','.*',$item['route'])) ? 'active' : '' }}">
            <span class="nav-icon">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
        </a>
        @endforeach

        
        <div class="nav-section-label" style="margin-top:8px;">Keuangan</div>
        <a href="{{ route('laporan-bulanan.index') }}"
            class="nav-item {{ request()->routeIs('laporan-bendahara.*') ? 'active' : '' }}">
            <span class="nav-icon">📑</span>
            <span>Laporan Keuangan Bulanan</span>
        </a>
        
        
        <!-- TAMBAHKAN INVENTARIS -->
        <div class="nav-section-label" style="margin-top:8px;">Inventaris</div>
        <a href="{{ route('inventaris.index') }}"
            class="nav-item {{ request()->routeIs('inventaris.*') && !request()->routeIs('inventaris.peminjaman') ? 'active' : '' }}">
            <span class="nav-icon">📦</span>
            <span>Inventaris Barang</span>
        </a>
        <a href="{{ route('inventaris.peminjaman') }}"
            class="nav-item {{ request()->routeIs('inventaris.peminjaman*') ? 'active' : '' }}">
            <span class="nav-icon">📋</span>
            <span>Peminjaman Barang</span>
        </a>
        <!-- END INVENTARIS -->

        @if(in_array(session('user')['role'], ['Ketua','Sekretaris']))
        <div class="nav-section-label" style="margin-top:8px;">Administrasi</div>
        <a href="{{ route('users.index') }}"
            class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span class="nav-icon">🔧</span>
            <span>User Management</span>
        </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user-card" onclick="openProfileModal()">
            <div class="avatar">{{ strtoupper(substr(session('user')['username'],0,2)) }}</div>
            <div>
                <div class="user-name">{{ session('user')['username'] }}</div>
                <div class="user-role">{{ session('user')['role'] }}</div>
            </div>
            <span class="chevron-icon">⚙️</span>
        </div>
    </div>
</aside>

<!-- HEADER -->
<header>
    <div class="header-left">
        <button class="menu-btn" id="menuBtn" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="clock-block">
            <div class="clock-day" id="header-day"></div>
            <div class="clock-datetime" id="header-date-time"></div>
        </div>
    </div>

    <div class="header-right">
        <!-- User dropdown -->
        <div style="position:relative;">
            <button class="user-pill" id="userMenuBtn">
                <div class="avatar" style="width:30px;height:30px;border-radius:7px;font-size:12px;">
                    {{ strtoupper(substr(session('user')['username'],0,2)) }}
                </div>
                <div class="user-pill-info">
                    <div class="user-pill-name">{{ session('user')['username'] }}</div>
                    <div class="user-pill-role">{{ session('user')['role'] }}</div>
                </div>
                <span style="color:var(--text-muted);font-size:11px;margin-left:2px;">▾</span>
            </button>

            <div class="dropdown" id="userDropdown">
                <button onclick="openProfileModal()" class="dropdown-item">
                    <span>✏️</span> Edit Profil
                </button>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" class="dropdown-item danger">
                    <span>🚪</span> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main>
    @yield('content')
</main>

<!-- MODAL EDIT PROFIL -->
<div class="modal-backdrop" id="profileModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Edit Profil</h2>
            <button class="modal-close" onclick="closeProfileModal()">✕</button>
        </div>

        <form id="profileForm"
            method="POST"
            action="{{ route('profile.update', session('user')['username']) }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" readonly
                    class="form-input"
                    autocomplete="username"
                    value="{{ session('user')['username'] }}">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" required
                    class="form-input"
                    autocomplete="email"
                    value="{{ session('user')['email'] ?? '' }}"
                    placeholder="nama@email.com">
            </div>

            <div class="form-group">
                <label class="form-label">Password baru <span style="color:var(--text-muted);font-weight:400;">(opsional)</span></label>
                <input type="password" name="password"
                    class="form-input"
                    autocomplete="new-password"
                    placeholder="Kosongkan jika tidak ingin ganti">
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closeProfileModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                    <span id="saveText">Simpan</span>
                    <div id="saveSpinner" class="spinner" style="display:none;"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TOAST -->
<div id="toast">
    <div class="toast-icon">✓</div>
    <span id="toastMsg">Profil berhasil diperbarui</span>
</div>
@stack('scripts')
</body>
</html>
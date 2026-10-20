<!DOCTYPE html>
<html lang="id"
      x-data="{
        dark: localStorage.getItem('puk-theme') === 'dark',
        scrolled: false,
        mobileOpen: false,
        activeSection: 'beranda'
      }"
      x-init="
        $watch('dark', v => {
          localStorage.setItem('puk-theme', v ? 'dark' : 'light');
          document.documentElement.setAttribute('data-theme', v ? 'dark' : 'light');
        });
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');

        window.addEventListener('scroll', () => {
          scrolled = window.scrollY > 40;
          const sections = ['beranda', 'tentang', 'visi-misi', 'struktur','berita', @if(session('anggota'))  'kegiatan', 'agenda', 'inventaris','keuangan', @endif 'galeri', 'faq', 'kontak'];
          let current = 'beranda';
          const offset = 120;
          for (const id of sections) {
            const el = document.getElementById(id);
            if (el) {
              const rect = el.getBoundingClientRect();
              if (rect.top <= offset) {
                current = id;
              }
            }
          }
          activeSection = current;
        });
        window.dispatchEvent(new Event('scroll'));
      ">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="@yield('meta_description', 'PUK OMRON - Pimpinan Unit Kerja yang memperjuangkan hak dan kesejahteraan pekerja.')" />
    <title>@yield('title', 'PUK OMRON - Pimpinan Unit Kerja')</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/landing-page/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="lp-body">

    {{-- ===== BREAKING NEWS TICKER (global, semua halaman publik) ===== --}}
    @php
        $breakingTicker = \App\Models\News::published()
            ->where('is_breaking', true)
            ->latest('published_at')
            ->take(6)
            ->get();
    @endphp
    @php
        $footerInformasi = \App\Models\Information::published()
            ->orderBy('urutan')->orderBy('judul')->get();
    @endphp
    @if($breakingTicker->isNotEmpty())
        <div class="lp-ticker">
            <div class="lp-ticker__label">
                <span aria-hidden="true">🚨</span>
                <span>Breaking</span>
            </div>
            <div class="lp-ticker__track-wrapper"
                 x-data="{ paused: false }"
                 @mouseenter="paused = true"
                 @mouseleave="paused = false">
                <div class="lp-ticker__track" :class="{ 'lp-ticker__track--paused': paused }">
                    @foreach ($breakingTicker as $item)
                        <a href="{{ route('berita.show', $item->slug) }}" class="lp-ticker__item">{{ $item->judul }}</a>
                    @endforeach
                    {{-- duplikat supaya animasi loop mulus tanpa jeda --}}
                    @foreach ($breakingTicker as $item)
                        <a href="{{ route('berita.show', $item->slug) }}" class="lp-ticker__item" aria-hidden="true" tabindex="-1">{{ $item->judul }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== NAVBAR ===== --}}
    <header class="lp-nav" :class="{ 'lp-nav--scrolled': scrolled }">
        <div class="lp-container lp-nav__inner">

            {{-- ===== HAMBURGER (dipindah ke paling kiri untuk mobile) ===== --}}
            <button type="button"
                    class="lp-hamburger"
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen.toString()"
                    aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>

            <a href="{{ route('landing.index') }}#beranda" class="lp-nav__brand">
                <span class="lp-nav__brand-text">PUK OMI</span>
            </a>

            {{-- ===== MENU DESKTOP (dikelompokkan jadi dropdown) ===== --}}
            <nav class="lp-nav__menu" aria-label="Menu utama">
                <a href="{{ route('landing.index') }}#beranda" :class="{ active: activeSection === 'beranda' }">Beranda</a>

                {{-- Dropdown: Tentang Kami --}}
                <div class="lp-nav__dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button"
                            class="lp-nav__dropdown-trigger"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            :class="{ active: ['tentang','visi-misi','struktur'].includes(activeSection) }">
                        Tentang Kami
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="lp-nav__dropdown-menu" x-show="open" x-transition x-cloak>
                        <a href="{{ route('landing.index') }}#tentang" :class="{ active: activeSection === 'tentang' }" @click="open = false">Tentang</a>
                        <a href="{{ route('landing.index') }}#visi-misi" :class="{ active: activeSection === 'visi-misi' }" @click="open = false">Visi &amp; Misi</a>
                        <a href="{{ route('landing.index') }}#struktur" :class="{ active: activeSection === 'struktur' }" @click="open = false">Struktur</a>
                    </div>
                </div>

                <a href="{{ route('landing.index') }}#berita" :class="{ active: activeSection === 'berita' }">Berita</a>

                {{-- Dropdown: Area Anggota (hanya untuk anggota login) --}}
                @php
                    $kegiatanCarousel = \App\Models\Kegiatan::where('status', 'dibuka')
                        ->orderBy('tanggal_mulai')
                        ->limit(10)
                        ->get();
                @endphp

                @if (session('anggota'))
                <div class="lp-nav__dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button"
                            class="lp-nav__dropdown-trigger"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            :class="{ active: ['kegiatan','agenda','inventaris','keuangan'].includes(activeSection) }">
                        Area Anggota
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="lp-nav__dropdown-menu" x-show="open" x-transition x-cloak>
                        @if ($kegiatanCarousel->isNotEmpty())
                        <a href="{{ route('landing.index') }}#kegiatan" :class="{ active: activeSection === 'Kegiatan' }" @click="open = false">Pendaftaran kegiatan</a>
                        @endif
                        <a href="{{ route('landing.index') }}#agenda" :class="{ active: activeSection === 'agenda' }" @click="open = false">Agenda</a>
                        <a href="{{ route('landing.index') }}#inventaris" :class="{ active: activeSection === 'inventaris' }" @click="open = false">Inventaris</a>
                        <a href="{{ route('landing.index') }}#keuangan" :class="{ active: activeSection === 'keuangan' }" @click="open = false">Keuangan</a>
                    </div>
                </div>
                @endif

                <a href="{{ route('landing.index') }}#galeri" :class="{ active: activeSection === 'galeri' }">Galeri</a>

                {{-- Dropdown: Info --}}
                <div class="lp-nav__dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button"
                            class="lp-nav__dropdown-trigger"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            :class="{ active: ['faq','kontak'].includes(activeSection) }">
                        Info
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="lp-nav__dropdown-menu" x-show="open" x-transition x-cloak>
                        <a href="{{ route('landing.index') }}#faq" :class="{ active: activeSection === 'faq' }" @click="open = false">FAQ</a>
                        <a href="{{ route('landing.index') }}#kontak" :class="{ active: activeSection === 'kontak' }" @click="open = false">Kontak</a>
                    </div>
                </div>
            </nav>

            <div class="lp-nav__actions">
                <button type="button"
                        class="lp-icon-btn"
                        @click="dark = !dark"
                        :aria-label="dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'">
                    <svg x-show="!dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v1M12 20v1M4.22 4.22l.7.7M18.08 18.08l.7.7M3 12h1M20 12h1M4.22 19.78l.7-.7M18.08 5.92l.7-.7"/><circle cx="12" cy="12" r="5"/></svg>
                    <svg x-show="dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                @if (session('anggota'))
                <div class="lp-nav__member" x-data="{ openMenu: false }" @click.outside="openMenu = false">
                    <button type="button" class="lp-nav__member-btn" @click="openMenu = !openMenu">
                        <span class="lp-nav__avatar">
                            <img src="{{ session('anggota.foto_url') }}" alt="{{ session('anggota.nama') }}">
                        </span>
                       
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div class="lp-nav__member-dropdown" x-show="openMenu" x-transition x-cloak>

                        {{-- ===== HEADER: foto besar + nama ditengah ===== --}}
                        <div class="lp-nav__member-dropdown-header">
                            <div class="lp-nav__avatar-edit-wrap">
                                <span class="lp-nav__avatar lp-nav__avatar--xl">
                                    <img src="{{ session('anggota.foto_url') }}" alt="{{ session('anggota.nama') }}">
                                </span>
                                <a href="{{ route('anggota.profil.edit') }}" class="lp-nav__avatar-edit-btn" aria-label="Edit profil" title="Edit profil">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                    </svg>
                                </a>
                            </div>
                            <div class="lp-nav__member-dropdown-name">{{ session('anggota.nama') }}</div>
                        </div>

                        <div class="lp-nav__member-dropdown-divider"></div>

                        {{-- ===== MENU ITEMS ===== --}}
                        <div class="lp-nav__member-dropdown-menu">
                            <a href="{{ route('anggota.profil') }}">Profil Saya</a>

                            @php
                                $jabatan = strtolower(trim(session('anggota.jabatan_puk', '')));
                            @endphp

                           @if(\App\Helpers\PermissionHelper::can('access_admin'))
                                <a href="{{ route('admin.welcome') }}">Kelola Konten</a>
                            @endif

                            @if (session('anggota.punya_dashboard'))
                                <a href="{{ route('dashboard') }}" target="_blank" rel="noopener">Buka Dashboard PUK</a>
                            @endif

                            <form action="{{ route('anggota.logout') }}" method="POST">
                                @csrf
                                <button type="submit">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                    <a href="{{ route('anggota.login') }}" class="lp-btn lp-btn--primary">Login</a>
                @endif
            </div>
        </div>

        {{-- ===== MENU MOBILE (dikelompokkan jadi accordion) ===== --}}
        <nav class="lp-nav__mobile"
             x-show="mobileOpen"
             x-transition
             x-cloak
             @click.outside="mobileOpen = false"
             aria-label="Menu mobile">

            {{-- ===== PROFIL ANGGOTA (mobile) ===== --}}
            @if (session('anggota'))
                <div class="lp-nav__mobile-profile">
                    <span class="lp-nav__avatar lp-nav__avatar--lg">
                        <img src="{{ session('anggota.foto_url') }}" alt="{{ session('anggota.nama') }}">
                    </span>
                    <div>
                        <div class="lp-nav__mobile-profile-name">{{ session('anggota.nama') }}</div>
                        <a href="{{ route('anggota.profil') }}" class="lp-nav__mobile-profile-link" @click="mobileOpen = false">Lihat Profil</a>
                    </div>
                </div>
            @endif

            <a href="{{ route('landing.index') }}#beranda" @click="mobileOpen = false">Beranda</a>

            {{-- Grup: Tentang Kami --}}
            <div class="lp-nav__mobile-group" x-data="{ open: false }">
                <button type="button" class="lp-nav__mobile-group-trigger" @click="open = !open" :aria-expanded="open.toString()">
                    <span>Tentang Kami</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="lp-nav__mobile-group-items" :class="{ 'lp-nav__mobile-group-items--open': open }">
                    <div class="lp-nav__mobile-group-items-inner">
                        <a href="{{ route('landing.index') }}#tentang" @click="mobileOpen = false">Tentang</a>
                        <a href="{{ route('landing.index') }}#visi-misi" @click="mobileOpen = false">Visi &amp; Misi</a>
                        <a href="{{ route('landing.index') }}#struktur" @click="mobileOpen = false">Struktur</a>
                    </div>
                </div>
            </div>

            <a href="{{ route('landing.index') }}#berita" @click="mobileOpen = false">Berita</a>

            {{-- Grup: Area Anggota --}}

            
            @if (session('anggota'))
            <div class="lp-nav__mobile-group" x-data="{ open: false }">
                <button type="button" class="lp-nav__mobile-group-trigger" @click="open = !open" :aria-expanded="open.toString()">
                    <span>Area Anggota</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="lp-nav__mobile-group-items" :class="{ 'lp-nav__mobile-group-items--open': open }">
                    <div class="lp-nav__mobile-group-items-inner">
                        @if ($kegiatanCarousel->isNotEmpty())
                        <a href="{{ route('landing.index') }}#kegiatan" @click="mobileOpen = false">Pendaftaran kegiatan</a>
                        @endif
                        <a href="{{ route('landing.index') }}#agenda" @click="mobileOpen = false">Agenda</a>
                        <a href="{{ route('landing.index') }}#inventaris" @click="mobileOpen = false">Inventaris</a>
                        <a href="{{ route('landing.index') }}#keuangan" @click="mobileOpen = false">Keuangan</a>
                    </div>
                </div>
            </div>
            @endif

            <a href="{{ route('landing.index') }}#galeri" @click="mobileOpen = false">Galeri</a>

            {{-- Grup: Info --}}
            <div class="lp-nav__mobile-group" x-data="{ open: false }">
                <button type="button" class="lp-nav__mobile-group-trigger" @click="open = !open" :aria-expanded="open.toString()">
                    <span>Info</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="lp-nav__mobile-group-items" :class="{ 'lp-nav__mobile-group-items--open': open }">
                    <div class="lp-nav__mobile-group-items-inner">
                        <a href="{{ route('landing.index') }}#faq" @click="mobileOpen = false">FAQ</a>
                        <a href="{{ route('landing.index') }}#kontak" @click="mobileOpen = false">Kontak</a>
                    </div>
                </div>
            </div>

            @if (session('anggota'))
                @php
                    $jabatanMobile = strtolower(trim(session('anggota.jabatan_puk')));

                    $jabatanPengelolaKontenMobile = [
                        'tim media',
                        'bidang 5',
                        'sekretaris',
                    ];
                @endphp

                @if (in_array($jabatanMobile, $jabatanPengelolaKontenMobile, true))
                    <a href="{{ route('admin.welcome') }}"
                    class="lp-btn lp-btn--ghost lp-nav__mobile-cta">
                        Kelola Konten
                    </a>
                @endif
                @if (session('anggota.punya_dashboard'))
                    <a href="{{ route('login') }}" class="lp-btn lp-btn--ghost lp-nav__mobile-cta" target="_blank" rel="noopener">Buka Dashboard PUK</a>
                @endif
                <form action="{{ route('anggota.logout') }}" method="POST" class="lp-nav__mobile-cta">
                    @csrf
                    <button type="submit" class="lp-btn lp-btn--primary" style="width:100%">Keluar ({{ explode(' ', session('anggota.nama'))[0] }})</button>
                </form>
            @else
                <a href="{{ route('anggota.login') }}" class="lp-btn lp-btn--primary lp-nav__mobile-cta">Login Anggota</a>
            @endif
        </nav>
    </header>

    @include('landing-page.partials.notice-banner')

    {{-- ===== SCRIPT: hitung tinggi ticker+navbar → dipakai scroll-padding-top di CSS ===== --}}
    <script>
        (function () {
            function setScrollOffset() {
                var ticker = document.querySelector('.lp-ticker');
                var nav = document.querySelector('.lp-nav');
                var tickerHeight = ticker ? ticker.offsetHeight : 0;
                var navHeight = nav ? nav.offsetHeight : 0;

                // nav menempel tepat di bawah ticker, bukan numpuk di top:0
                document.documentElement.style.setProperty('--nav-top', tickerHeight + 'px');

                var offset = tickerHeight + navHeight + 16;
                document.documentElement.style.setProperty('--scroll-offset', offset + 'px');
            }
            setScrollOffset();
            
            window.addEventListener('load', setScrollOffset);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(setScrollOffset);
            }
        })();
    </script>

    {{-- ===== KONTEN UTAMA ===== --}}
    <main class="lp-main">
        @yield('content')
    </main>

    {{-- ===== FOOTER ===== --}}
    <footer class="lp-footer">
        <div class="lp-container lp-footer__grid">
            <div class="lp-footer__col">
                <div class="lp-footer__brand">
                    Pimpinan Unit Kerja<br>
                    Serikat Pekerja Elektronik Elektrik<br>
                    Federasi Serikat Pekerja Metal Indonesia<br>
                    PT. Omron Manufacturing of Indonesia
                </div>
                <div class="lp-footer__logos" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
                    <img src="{{ asset('assets/logo-fspmi.png') }}"
                        alt="Logo FSPMI"
                        style="height: 50px; width: auto; object-fit: contain;">
                    <img src="{{ asset('assets/logo-spee.png') }}"
                        alt="Logo SPEE"
                        style="height: 55px; width: auto; object-fit: contain;">
                </div>
            </div>

            <div class="lp-footer__col">
                <h5>Tautan</h5>
                <ul>
                    <li><a href="#tentang">Tentang Kami</a></li>
                    <li><a href="#struktur">Struktur</a></li>
                    <li><a href="#berita">Berita</a></li>
                    <li><a href="#kontak">Kontak</a></li>
                </ul>
            </div>

            <div class="lp-footer__col">
                <h5>Informasi</h5>
                <ul>
                    @forelse ($footerInformasi as $info)
                        <li><a href="{{ route('informasi.show', $info->slug) }}">{{ $info->judul }}</a></li>
                    @empty
                        <li style="color: var(--text-muted); font-size: 13px;">Belum ada informasi</li>
                    @endforelse
                </ul>
            </div>

            <div class="lp-footer__col">
            <h5>Ikuti Kami</h5>
            <div class="lp-footer__social">
                @forelse($socialMedias as $media)
                    @php
                        $platform = strtolower($media->platform);
                        $label = ucfirst($platform);
                        $iconSvg = match($platform) {
                            'facebook' => '<svg viewBox="0 0 320 512" fill="currentColor"><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>',
                            'twitter' => '<svg viewBox="0 0 512 512" fill="currentColor"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>',
                            'instagram' => '<svg viewBox="0 0 448 512"><defs><linearGradient id="ig-grad-'.$loop->index.'" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#FFDC80"/><stop offset="25%" stop-color="#FCAF45"/><stop offset="50%" stop-color="#E1306C"/><stop offset="75%" stop-color="#C13584"/><stop offset="100%" stop-color="#833AB4"/></linearGradient></defs><path fill="url(#ig-grad-'.$loop->index.')" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>',
                            'youtube' => '<svg viewBox="0 0 576 512" fill="currentColor"><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zM232 337.6V174.4L361 256l-129 81.6z"/></svg>',
                            'tiktok' => '<svg viewBox="0 0 448 512" fill="currentColor"><path d="M448 209.9a210.1 210.1 0 0 1-122.8-39.3V349.4A162.6 162.6 0 1 1 185 188.3v88.4a74.6 74.6 0 1 0 52.2 71.2V0h87a121.2 121.2 0 0 0 1.9 22.2 122.2 122.2 0 0 0 53.9 80.2 121.4 121.4 0 0 0 67.9 20.6z"/></svg>',
                            'telegram' => '<svg viewBox="0 0 496 512" fill="currentColor"><path d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zM362 176.7c-3.7 39.2-19.9 134.4-28.1 178.3-3.5 18.6-10.3 24.8-16.9 25.4-14.4 1.3-25.3-9.5-39.3-18.7-21.8-14.3-34.2-23.2-55.3-37.2-24.5-16.1-8.6-25 5.3-39.5 3.7-3.8 67.1-61.5 68.3-66.7 .2-.7 .3-3.1-1.2-4.4s-3.6-.8-5.1-.5q-3.3 .7-104.6 69.1-14.8 10.2-26.9 9.9c-8.9-.2-25.9-5-38.6-9.1-15.5-5-27.9-7.7-26.8-16.3q.8-6.7 18.5-13.7 108.4-47.2 144.6-62.3c68.9-28.6 83.2-33.6 92.5-33.8 2.1 0 6.6 .5 9.6 2.9a10.5 10.5 0 0 1 3.5 6.7A43.8 43.8 0 0 1 362 176.7z"/></svg>',
                            'whatsapp' => '<svg viewBox="0 0 448 512" fill="currentColor"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>',
                            default => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
                        };
                    @endphp
                    <a href="{{ $media->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="{{ $label }}"
                    class="lp-footer__social-icon lp-footer__social-icon--{{ $platform }}">
                        {!! $iconSvg !!}
                    </a>
                @empty
                    <span style="font-size:13px; color:var(--text-muted);">Belum ada tautan media sosial</span>
                @endforelse
            </div>
        </div>
        </div>
        <div class="lp-container lp-footer__bottom">
            <span>&copy; {{ date('Y') }} PUK OMRON. Seluruh hak dilindungi.</span>
        </div>
    </footer>

    {{-- ===== SCRIPT: animasi scroll + smooth-scroll same-page ===== --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const animateElements = document.querySelectorAll('.animate-on-scroll, .stagger-children');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('is-visible');
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
            animateElements.forEach(el => observer.observe(el));
        } else {
            animateElements.forEach(el => el.classList.add('is-visible'));
        }

        // Smooth-scroll untuk link internal (#hash) di halaman yang sama.
        // Kalau menu mobile sedang terbuka, tutup dulu dan tunggu transisinya
        // selesai supaya tinggi header sudah final sebelum menghitung posisi scroll.
        const navLinks = document.querySelectorAll('a[href*="#"]');
        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                let url;
                try {
                    url = new URL(this.href, window.location.origin);
                } catch (err) { return; }
                if (!url.hash) return;

                const target = document.querySelector(url.hash);
                const samePage = url.pathname === window.location.pathname;
                if (!target || !samePage) return;

                e.preventDefault();

                const mobileMenu = document.querySelector('.lp-nav__mobile');
                const isMobileMenuOpen = mobileMenu && window.getComputedStyle(mobileMenu).display !== 'none';

                let scrolled = false;
                const doScroll = () => {
                    if (scrolled) return;
                    scrolled = true;
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    history.pushState(null, '', url.hash);
                };

                if (isMobileMenuOpen) {
                    const hamburger = document.querySelector('.lp-hamburger');
                    if (hamburger) hamburger.click();
                    mobileMenu.addEventListener('transitionend', doScroll, { once: true });
                    setTimeout(doScroll, 350); // fallback kalau transitionend tidak terpicu
                } else {
                    doScroll();
                }
            });
        });
    });

    // ===== SCRIPT: koreksi posisi scroll saat load halaman dengan #hash =====
    // Dipakai baik untuk refresh langsung ke #hash, maupun pindah dari halaman
    // lain (mis. berita.index) ke landing#section. Alih-alih menebak titik waktu
    // tertentu, script ini polling tiap frame sampai posisi target benar-benar
    // berhenti bergeser (layout stabil), baru berhenti mengoreksi.
    (function () {
    if (!window.location.hash) return;
    const hash = window.location.hash;
    const target = document.querySelector(hash);
    if (!target) return;

    const html = document.documentElement;
    const prevBehavior = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';

    let userInteracted = false;
    let done = false;
    ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach(evt =>
        window.addEventListener(evt, () => { userInteracted = true; teardown(); }, { passive: true, once: true })
    );

    function getOffset() {
        const n = parseFloat(getComputedStyle(html).scrollPaddingTop);
        return isNaN(n) ? 96 : n;
    }

    function targetY() {
        const rect = target.getBoundingClientRect();
        return Math.max(window.scrollY + rect.top - getOffset(), 0);
    }

    function correct() {
        if (userInteracted) return;
        const y = targetY();
        if (Math.abs(window.scrollY - y) > 1) {
            window.scrollTo(0, y);
        }
    }

    // ── Fase 1: polling adaptif untuk settle awal ──
    let stableCount = 0;
    let elapsed = 0;
    const STEP = 150;
    const MAX_MS = 6000;

    function poll() {
        if (userInteracted) return;
        correct();
        const y = targetY();
        if (Math.abs(window.scrollY - y) <= 1) {
            stableCount++;
        } else {
            stableCount = 0;
        }
        elapsed += STEP;
        if (stableCount >= 3 || elapsed >= MAX_MS) {
            done = true;
            html.style.scrollBehavior = prevBehavior || '';
            return;
        }
        setTimeout(poll, STEP);
    }

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => setTimeout(poll, 0));
    } else {
        setTimeout(poll, 0);
    }

    // ── Fase 2: pantau layout-shift susulan (chart, gambar telat, dll) ──
    // Berjalan independen dari fase 1 — akan tetap mengoreksi walau
    // polling awal sudah "selesai", selama user belum berinteraksi.
    let shiftObserver = null;
    if ('PerformanceObserver' in window && PerformanceObserver.supportedEntryTypes?.includes('layout-shift')) {
        shiftObserver = new PerformanceObserver(() => {
            if (userInteracted) return;
            correct();
        });
        try {
            shiftObserver.observe({ type: 'layout-shift', buffered: true });
        } catch (e) { /* diamkan kalau browser tidak dukung */ }
    }

    // Sinyal manual dari section async (mis. grafik keuangan selesai render)
    document.addEventListener('lp:section-rendered', () => {
        if (!userInteracted) correct();
    });

    // Address bar mobile show/hide
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', () => {
            if (!userInteracted) correct();
        });
    }

    function teardown() {
        if (shiftObserver) shiftObserver.disconnect();
        html.style.scrollBehavior = prevBehavior || '';
    }

    // Batas waktu wajar: berhenti total setelah 12 detik walau tak ada interaksi,
    // supaya tidak "mengunci" scroll selamanya kalau ada animasi/iklan yang terus shifting.
    setTimeout(teardown, 12000);
})();
    </script>

    @stack('scripts')
</body>
</html>
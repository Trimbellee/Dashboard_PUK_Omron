<!DOCTYPE html>
<html lang="id" x-data="{ dark: localStorage.getItem('puk-theme') === 'dark', sidebarOpen: false }"
      x-init="document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Konten - PUK OMRON')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    @vite([
        'resources/js/app.js',
        'resources/css/app.css',
        'resources/css/admin/admin-panel.css',
    ])
    @stack('page-styles')
</head>
<body class="lp-body lp-admin-body">

    {{-- Overlay sidebar mobile --}}
    <div class="lp-admin-overlay" x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false"></div>

    <div class="lp-admin-shell">
        {{-- ===== SIDEBAR ===== --}}
        <aside class="lp-admin-sidebar" :class="{ 'lp-admin-sidebar--open': sidebarOpen }">
            <a href="{{ route('landing.index') }}" class="lp-admin-sidebar__brand">
                <span class="lp-nav__brand-mark">PUK</span>
                <span>Admin Konten</span>
            </a>

            @php
                use App\Helpers\PermissionHelper;
                $jabatanAdmin = strtolower(trim(session('anggota.jabatan_puk', '')));
            @endphp
            <nav class="lp-admin-sidebar__nav">
                @foreach ($menuItems as $item)
                    @php
                        $targetRoute = ($item->route_name && Route::has($item->route_name))
                            ? $item->route_name
                            : 'admin.halaman.show';
                        $targetParams = $targetRoute === 'admin.halaman.show' ? ['page' => $item->slug] : [];
                    @endphp
                    <a href="{{ route($targetRoute, $targetParams) }}"
                    class="{{ (request()->routeIs($item->route_name . '*') || request()->route('page')?->slug === $item->slug) ? 'active' : '' }}">
                        {!! $item->icon_svg !!}
                        {{ $item->label }}
                    </a>
                @endforeach
            

            {{-- Role & Permission tetap hardcode, khusus Sekretaris, tidak ikut sistem ini --}}
            @if (strtolower(trim(session('anggota.jabatan_puk', ''))) === 'sekretaris')
                <a href="{{ route('admin.role-permission.index') }}" class="{{ request()->routeIs('admin.role-permission.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Peran &amp; Izin
                </a>
            @endif

            @if (strtolower(trim(session('anggota.jabatan_puk', ''))) === 'sekretaris')
                <a href="{{ route('admin.halaman.index') }}" class="{{ request()->routeIs('admin.halaman.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Manajemen Halaman
                </a>
            @endif
            
        </nav>

            <div class="lp-admin-sidebar__footer">
                <button type="button" class="lp-admin-sidebar__theme" @click="dark = !dark; localStorage.setItem('puk-theme', dark ? 'dark' : 'light'); document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')">
                    <span x-show="!dark">🌙 Mode Gelap</span>
                    <span x-show="dark" x-cloak>☀️ Mode Terang</span>
                </button>

                <div class="lp-admin-sidebar__user">
                    <div class="lp-admin-sidebar__avatar">{{ collect(explode(' ', session('anggota.nama', '?')))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</div>
                    <div>
                        <div class="lp-admin-sidebar__name">{{ session('anggota.nama') }}</div>
                        <div class="lp-admin-sidebar__role">{{ session('anggota.jabatan_puk') }}</div>
                    </div>
                </div>
                <form action="{{ route('anggota.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="lp-btn lp-btn--ghost" style="width:100%">Keluar</button>
                </form>
            </div>
        </aside>

        {{-- ===== MAIN ===== --}}
        <div class="lp-admin-content">
            <header class="lp-admin-topbar">
                <button type="button" class="lp-admin-menu-btn" @click="sidebarOpen = true" aria-label="Buka menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>
                <span class="lp-admin-topbar__title" style="color: var(--text-secondary);">@yield('title', 'Admin Konten')</span>
            </header>

            <main class="lp-admin-main">
                @yield('content')
            </main>
        </div>
    </div>

        {{-- ===== Toast container ===== --}}
        <div id="lp-toast-container" class="lp-toast-container"></div>


        {{-- ===== Modal konfirmasi custom (generic: dipakai untuk kirim/setujui/tolak/hapus) ===== --}}
        <div x-data="{ open: false, message: '', formId: null, confirmLabel: 'Ya', variant: 'danger' }"
            x-on:open-confirm.window="
                open = true;
                message = $event.detail.message;
                formId = $event.detail.formId;
                confirmLabel = $event.detail.confirmLabel ?? 'Ya';
                variant = $event.detail.variant ?? 'danger';
            ">
            <div class="lp-confirm-overlay"
                x-show="open"
                x-transition:enter="lp-modal-fade"
                x-transition:enter-start="lp-modal-fade--out"
                x-transition:enter-end="lp-modal-fade--in"
                x-transition:leave="lp-modal-fade"
                x-transition:leave-start="lp-modal-fade--in"
                x-transition:leave-end="lp-modal-fade--out"
                x-cloak
                @click="open = false"></div>
            <div class="lp-confirm-modal"
                x-show="open"
                x-transition:enter="lp-modal-fade"
                x-transition:enter-start="lp-modal-fade--out"
                x-transition:enter-end="lp-modal-fade--in"
                x-transition:leave="lp-modal-fade"
                x-transition:leave-start="lp-modal-fade--in"
                x-transition:leave-end="lp-modal-fade--out"
                x-cloak
                role="alertdialog" aria-modal="true">
                <p x-text="message"></p>
                <div class="lp-confirm-modal__actions">
                    <button type="button" class="lp-btn lp-btn--ghost" @click="open = false">Batal</button>
                    <button type="button"
                            class="lp-btn"
                            :class="'lp-btn--' + variant"
                            @click="document.getElementById(formId).submit(); open = false"
                            x-text="confirmLabel"></button>
                </div>
            </div>
        </div>

    @if (session('success'))
        <script>window.__lpFlash = { type: 'success', message: @json(session('success')) };</script>
    @elseif (session('warning'))
        <script>window.__lpFlash = { type: 'warning', message: @json(session('warning')) };</script>
    @elseif (session('error'))
        <script>window.__lpFlash = { type: 'error', message: @json(session('error')) };</script>
    @endif

    <script>
        (function () {
            function showToast(type, message) {
                const container = document.getElementById('lp-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = 'lp-toast lp-toast--' + type;
                toast.innerHTML = '<span>' + message + '</span><button type="button" aria-label="Tutup">&times;</button>';
                container.appendChild(toast);

                requestAnimationFrame(() => toast.classList.add('lp-toast--show'));

                const remove = () => {
                    toast.classList.remove('lp-toast--show');
                    setTimeout(() => toast.remove(), 200);
                };

                toast.querySelector('button').addEventListener('click', remove);
                setTimeout(remove, 1500);
            }

            window.showToast = showToast;

            document.addEventListener('DOMContentLoaded', () => {
                if (window.__lpFlash) {
                    showToast(window.__lpFlash.type, window.__lpFlash.message);
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
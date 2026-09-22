@extends('landing-page.layouts.app')

@section('title', 'PUK OMRON - Pimpinan Unit Kerja')
@section('meta_description', 'PUK OMRON - Pimpinan Unit Kerja yang memperjuangkan hak dan kesejahteraan pekerja.')
@push('styles')
    @vite(['resources/css/landing-page/index.css'])
@endpush
@section('content')

    {{-- ===== HERO (#beranda) — OPTIMIZED & MODERNIZED ===== --}}
    <section id="beranda"
            class="lp-hero lp-section--hero"
            x-data="heroParallax()"
            @mousemove="handleMouse($event)"
            @mouseleave="reset()"
            x-bind:style="{ '--mx': mx + 'px', '--my': my + 'px' }">

        <div class="lp-hero__mesh" aria-hidden="true">
            <span class="lp-hero__blob lp-hero__blob--1"></span>
            <span class="lp-hero__blob lp-hero__blob--2"></span>
        </div>
        <span class="lp-hero__grid-overlay" aria-hidden="true"></span>

        <div class="lp-container lp-hero__inner">

            {{-- ===== Konten kiri ===== --}}
            <div class="lp-hero__content stagger-children">
                <span class="lp-hero__tag">
                    <span class="lp-hero__tag-dot"></span>
                    Pimpinan Unit Kerja<br>
                    Serikat Pekerja Elektronik-Elektrik<br>
                    Federasi Serikat Pekerja Metal Indonesia<br>
                    PT. Omron Manufacturing of Indonesia
                </span>

                <h1 class="lp-hero__title">
                    Wadah resmi pekerja OMRON untuk <br>
                    <span class="lp-hero__title-rotator"
                        x-data="rotatingWords(['bicara.', 'berunding.', 'berbenah bersama.'])"
                        x-init="start()">
                        <span class="lp-hero__title-gradient" :class="{ 'is-fading': fading }" x-text="current"></span>
                    </span>
                </h1>

                <p class="lp-hero__desc">
                    PUK SPEE FSPMI PT. OMRON mewakili kepentingan pekerja dalam hubungan industrial —
                    mulai dari perundingan perjanjian kerja bersama, perundingan upah, penyelesaian masalah
                    ketenagakerjaan, hingga program kesejahteraan anggota.
                </p>

                <div class="lp-hero__actions">
                    <span class="lp-btn-wrap" x-data="magnetic()" @mousemove="handleMove($event)" @mouseleave="reset()"
                        x-bind:style="{ transform: 'translate(' + tx + 'px,' + ty + 'px)' }">
                        <a href="#tentang" class="lp-btn lp-btn--primary lp-btn--glow">
                            <span>Kenali PUK OMRON</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lp-btn__arrow">
                                <path d="M5 12h14M13 6l6 6-6 6"/>
                            </svg>
                        </a>
                    </span>
                    <a href="#kontak" class="lp-btn lp-btn--ghost">Hubungi Pengurus</a>
                </div>
            </div>

            {{-- ===== Aside kanan ===== --}}
            <div class="lp-hero__aside animate-on-scroll delay-2">
                <span class="lp-hero__sparkle lp-hero__sparkle--1"></span>
                <span class="lp-hero__sparkle lp-hero__sparkle--2"></span>
                <span class="lp-hero__sparkle lp-hero__sparkle--3"></span>

                <div class="lp-hero__roster-card"
                    x-data="cardTilt()"
                    @mousemove="handleMove($event)"
                    @mouseleave="reset()"
                    x-bind:style="{ transform: 'perspective(900px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)' }">
                    <div class="lp-hero__roster-glow" aria-hidden="true"></div>
                    <div class="lp-hero__roster-shine" aria-hidden="true"></div>

                    <div class="lp-hero__roster-item">
                        <span class="lp-hero__roster-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        <div class="lp-hero__roster-text">
                            <dt>Anggota aktif</dt>
                            <dd class="counter-value"
                                x-data="{ count: 0 }"
                                x-init="() => {
                                    let target = {{ $totalAnggota ?? 1200 }};
                                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                        count = target; return;
                                    }
                                    let start = null;
                                    const duration = 1200;
                                    const animate = (ts) => {
                                        if (!start) start = ts;
                                        const progress = Math.min((ts - start) / duration, 1);
                                        const eased = 1 - Math.pow(1 - progress, 3);
                                        count = Math.floor(eased * target);
                                        if (progress < 1) requestAnimationFrame(animate);
                                        else count = target;
                                    };
                                    requestAnimationFrame(animate);
                                }"
                                x-text="count.toLocaleString('id-ID')">0</dd>
                        </div>
                    </div>

                    <div class="lp-hero__roster-divider"></div>

                    <div class="lp-hero__roster-item">
                        <span class="lp-hero__roster-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                        </span>
                        <div class="lp-hero__roster-text">
                            <dt>Periode kepengurusan</dt>
                            <dd>{{ $periodeKepengurusan ?? '2025–2029' }}</dd>
                        </div>
                    </div>
                </div>

                <div class="lp-hero__floating-card lp-hero__floating-card--1">
                    <span class="lp-hero__floating-icon">🤝</span>
                    <div>
                        <strong>Pendampingan Aktif</strong>
                        <small>Advokasi hubungan industrial</small>
                    </div>
                </div>

                <div class="lp-hero__floating-card lp-hero__floating-card--2">
                    <span class="lp-hero__floating-icon">📋</span>
                    <div>
                        <strong>PKB Terbaru</strong>
                        <small>Perjanjian Kerja Bersama</small>
                    </div>
                </div>

                <div class="lp-hero__floating-card lp-hero__floating-card--3 lp-hero__floating-card--pill">
                    <span class="lp-hero__floating-badge-icon">✓</span>
                    <strong>Terdaftar Resmi di DISNAKER</strong>
                </div>

                <div class="lp-hero__floating-card lp-hero__floating-card--4">
                    <span class="lp-hero__floating-icon">💬</span>
                    <div>
                        <strong>Aspirasi Ditindaklanjuti</strong>
                        <small>Respon cepat pengurus</small>
                    </div>
                </div>
            </div>
        </div>

        <a href="#tentang" class="lp-hero__scroll-cue" aria-label="Gulir ke bawah">
            <span class="lp-hero__scroll-cue-track"><span class="lp-hero__scroll-cue-dot"></span></span>
            <span class="lp-hero__scroll-cue-text">Gulir</span>
        </a>
    </section>

    <script>
    document.addEventListener('alpine:init', () => {
        // Parallax mesh — event-driven, TIDAK ADA requestAnimationFrame loop permanen.
        // Update posisi hanya dipicu saat mouse benar-benar bergerak (throttled 1x/frame).
        Alpine.data('heroParallax', () => ({
            mx: 0, my: 0,
            _raf: null,
            _reduced: false,
            init() {
                this._reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },
            handleMouse(e) {
                if (this._reduced || this._raf) return;
                const clientX = e.clientX, clientY = e.clientY, target = e.currentTarget;
                this._raf = requestAnimationFrame(() => {
                    const rect = target.getBoundingClientRect();
                    this.mx = ((clientX - rect.left) / rect.width - 0.5) * 32;
                    this.my = ((clientY - rect.top) / rect.height - 0.5) * 32;
                    this._raf = null;
                });
            },
            reset() { this.mx = 0; this.my = 0; },
        }));

        // Rotasi kata pada judul — interval tetap dibersihkan bila elemen dilepas.
        Alpine.data('rotatingWords', (words) => ({
            words, index: 0, current: words[0], fading: false, _timer: null,
            start() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                this._timer = setInterval(() => {
                    this.fading = true;
                    setTimeout(() => {
                        this.index = (this.index + 1) % this.words.length;
                        this.current = this.words[this.index];
                        this.fading = false;
                    }, 300);
                }, 2800);
            },
            destroy() { if (this._timer) clearInterval(this._timer); },
        }));

        // Tilt 3D pada kartu roster — event-driven, tanpa loop.
        Alpine.data('cardTilt', () => ({
            rx: 0, ry: 0,
            handleMove(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                const px = (e.clientX - rect.left) / rect.width - 0.5;
                const py = (e.clientY - rect.top) / rect.height - 0.5;
                this.ry = px * 8;
                this.rx = -py * 8;
            },
            reset() { this.rx = 0; this.ry = 0; },
        }));

        // Efek magnetic pada tombol utama — event-driven, tanpa loop.
        Alpine.data('magnetic', () => ({
            tx: 0, ty: 0,
            handleMove(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                this.tx = (e.clientX - rect.left - rect.width / 2) * 0.22;
                this.ty = (e.clientY - rect.top - rect.height / 2) * 0.22;
            },
            reset() { this.tx = 0; this.ty = 0; },
        }));
    });
    </script>

    {{-- ===== TENTANG (#tentang) ===== --}}
    <section id="tentang" class="lp-section lp-section--alt">
        <div class="lp-container">
            <div class="lp-section__head animate-on-scroll">
                <span class="lp-section__eyebrow">Tentang Kami</span>
                <h2 class="lp-section__title">Sejarah dan dasar perjuangan PUK OMRON</h2>
                <p class="lp-section__desc">
                    {{ $sejarahSingkat ?? 'PUK OMRON terbentuk sebagai wadah perjuangan hak-hak pekerja di lingkungan OMRON. Berlandaskan semangat kebersamaan dan keadilan, kami hadir untuk mengawal hubungan industrial yang harmonis dan produktif.' }}
                </p>
            </div>

            <div class="lp-pillars stagger-children">
                <article class="lp-pillar">
                    <div class="lp-pillar__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M2 12h20"/>
                            <path d="M7 5l-3 3 3 3M17 5l3 3-3 3"/>
                        </svg>
                    </div>
                    <h3>Perlindungan Hak Pekerja</h3>
                    <p>Melindungi, membela, dan memperjuangkan hak serta kepentingan pekerja melalui perundingan, advokasi, dan penyelesaian perselisihan sesuai peraturan perundang-undangan.</p>
                </article>

                <article class="lp-pillar">
                    <div class="lp-pillar__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="8" width="18" height="12" rx="2"/>
                            <path d="M8 8V6a4 4 0 0 1 8 0v2"/>
                        </svg>
                    </div>
                    <h3>Hubungan Industrial</h3>
                    <p>Mewakili pekerja dalam perundingan Perjanjian Kerja Bersama (PKB), membangun hubungan industrial yang harmonis, serta mendorong terciptanya keadilan di tempat kerja.</p>
                </article>

                <article class="lp-pillar">
                    <div class="lp-pillar__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3z"/>
                        </svg>
                    </div>
                    <h3>Kesejahteraan Anggota</h3>
                    <p>Mengupayakan peningkatan kesejahteraan anggota dan keluarganya melalui pendidikan, pengembangan kapasitas, perlindungan sosial, serta program organisasi yang bermanfaat.</p>
                </article>
            </div>

            <div class="lp-legal animate-on-scroll">
                <span class="lp-legal__label">Landasan hukum</span>
                <div class="lp-legal__badges">
                    <span class="lp-legal__badge">UU No. 13 Tahun 2003</span>
                    <span class="lp-legal__badge">UU Cipta Kerja</span>
                    <span class="lp-legal__badge">Perjanjian Kerja Bersama (PKB)</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== VISI & MISI (#visi-misi) ===== --}}
    <section id="visi-misi" class="lp-section">
        <div class="lp-container">
            <div class="lp-section__head animate-on-scroll">
                <span class="lp-section__eyebrow">Arah Perjuangan</span>
                <h2 class="lp-section__title">Visi &amp; Misi</h2>
            </div>

            <div class="lp-vm">
                <div class="lp-vm__visi animate-on-scroll">
                    <span class="lp-vm__label">Visi</span>
                    <p class="lp-vm__text">
                        {{ $visi ?? 'elum menyimpan Visi' }}
                    </p>
                </div>

                <div class="lp-vm__misi animate-on-scroll delay-2">
                    <span class="lp-vm__label">Misi</span>
                    <ul class="lp-vm__list">
                        @forelse (($misi ?? []) as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>Belum menyimpan Misi</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== STRUKTUR (#struktur) ===== --}}
    <section id="struktur" class="lp-section lp-section--alt">
        <div class="lp-container">
            <div class="lp-section__head animate-on-scroll">
                <span class="lp-section__eyebrow">STRUKTUR ORGANISASI</span>
                <h2 class="lp-section__title">PIMPINAN UNIT KERJA</h2>
                <h2 class="lp-section__title">SERIKAT PEKERJA ELEKTRONIK - ELEKTRIK</h2>
                <h2 class="lp-section__title">FEDERASI SERIKAT PEKERJA METAL INDONESIA</h2>
                <h2 class="lp-section__title">PT OMRON MANUFACTURING OF INDONESIA</h2>
                <p class="lp-section__desc">
                    Periode {{ $periodeKepengurusan ?? '2025–2029' }}
                </p>
            </div>

            @php
                $allNodes = \App\Models\StrukturOrganisasi::with('anggota')->get();

                $ketua = $allNodes->first(function($n) {
                    return strtoupper(trim($n->jabatan)) === 'KETUA';
                });

                $sekretaris = $allNodes->first(function($n) {
                    return strtoupper(trim($n->jabatan)) === 'SEKRETARIS';
                });

                $anakSekretaris = collect();
                if ($sekretaris) {
                    $anakSekretaris = $allNodes->where('parent_id', $sekretaris->id);
                }

                $bendahara = $anakSekretaris->first(function($n) {
                    return strtoupper(trim($n->jabatan)) === 'BENDAHARA';
                });

                $wakil = $anakSekretaris->filter(function($n) {
                    return preg_match('/^WAKIL KETUA BIDANG \d+$/i', trim($n->jabatan));
                })->sortBy(function($n) {
                    preg_match('/\d+/', $n->jabatan, $matches);
                    return intval($matches[0] ?? 0);
                })->values();

                $groups = $wakil->map(function($node) use ($allNodes) {
                    $children = $allNodes->where('parent_id', $node->id)->sortBy('urutan')->values();
                    return [
                        'parent' => $node,
                        'children' => $children
                    ];
                });

                // Build tree untuk tampilan expandable di bawah
                $tree = \App\Models\StrukturOrganisasi::getTree();
                $treeArray = $tree->toArray();
                // Tambahkan level
                $addLevel = function($nodes, $level = 0) use (&$addLevel) {
                    foreach ($nodes as &$node) {
                        $node['level'] = $level;
                        if (!empty($node['children'])) {
                            $node['children'] = $addLevel($node['children'], $level + 1);
                        }
                    }
                    return $nodes;
                };
                $treeArray = $addLevel($treeArray);
            @endphp

            {{-- ===== TAMPILAN SINGKAT (seperti sebelumnya) ===== --}}
            <div class="lp-struktur-tree">
                {{-- LEVEL 0: KETUA --}}
                @if($ketua)
                    <div class="lp-tree-level lp-tree-level--single">
                        @include('landing-page.partials.struktur-card', ['node' => $ketua])
                    </div>
                @endif

                {{-- CONNECTOR --}}
                @if($ketua && $sekretaris)
                    <div class="lp-tree-connector"></div>
                @endif

                {{-- LEVEL 1: SEKRETARIS + BENDAHARA --}}
                @if($sekretaris)
                    <div class="lp-tree-level lp-tree-level--dual">
                        <div class="lp-tree-level__sekretaris-wrapper">
                            @include('landing-page.partials.struktur-card', ['node' => $sekretaris])
                        </div>
                        @if($bendahara)
                            <div class="lp-tree-level__bendahara-wrapper">
                                <div class="lp-tree-level__line"></div>
                                @include('landing-page.partials.struktur-card', ['node' => $bendahara])
                            </div>
                        @endif
                    </div>
                @endif

                {{-- CONNECTOR KE LEVEL 2 --}}
                @if($groups->isNotEmpty())
                    <div class="lp-tree-connector"></div>
                @endif

                {{-- LEVEL 2: WAKIL KETUA BIDANG (sejajar) --}}
                @if($groups->isNotEmpty())
                    <div class="lp-tree-level lp-tree-level--parents">
                        @foreach($groups as $group)
                            <div class="lp-tree-level__parent-item">
                                @include('landing-page.partials.struktur-card', ['node' => $group['parent']])
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- CONNECTOR KE LEVEL 3 --}}
                @if($groups->isNotEmpty() && $groups->some(fn($g) => $g['children']->isNotEmpty()))
                    <div class="lp-tree-connector lp-tree-connector--to-children"></div>
                @endif

                {{-- LEVEL 3: WAKIL SEKRETARIS BIDANG --}}
                @if($groups->isNotEmpty() && $groups->some(fn($g) => $g['children']->isNotEmpty()))
                    <div class="lp-tree-level lp-tree-level--children">
                        @foreach($groups as $group)
                            <div class="lp-tree-level__child-column">
                                @if($group['children']->isNotEmpty())
                                    @foreach($group['children'] as $child)
                                        <div class="lp-tree-level__child-item">
                                            @include('landing-page.partials.struktur-card', ['node' => $child])
                                        </div>
                                    @endforeach
                                @else
                                    <div class="lp-tree-level__child-item lp-tree-level__child-item--empty">
                                        <span class="lp-empty-child">—</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

                        {{-- ===== LEGENDA BIDANG ===== --}}
            <div class="lp-bidang-legend animate-on-scroll">
                <div class="lp-bidang-legend__title">Keterangan Bidang</div>
                <div class="lp-bidang-legend__grid">
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--1">1</span>
                        Bidang Pendidikan
                    </div>
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--2">2</span>
                        Bidang Organisasi
                    </div>
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--3">3</span>
                        Bidang Hukum &amp; Pembelaan
                    </div>
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--4">4</span>
                        Bidang Hubungan Industrial, PKB &amp; K3
                    </div>
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--5">5</span>
                        Bidang Informasi, Komunikasi &amp; Sosial Ekonomi
                    </div>
                    <div class="lp-bidang-legend__item">
                        <span class="lp-bidang-legend__badge lp-bidang-legend__badge--6">6</span>
                        Bidang Pemberdayaan Perempuan
                    </div>
                </div>
            </div>

            {{-- ===== TOMBOL LIHAT SELENGKAPNYA ===== --}}
            <div style="text-align: center; margin-top: 40px;">
                <button type="button"
                        id="btnLihatSelengkapnya"
                        class="lp-btn lp-btn--primary"
                        style="padding: 12px 40px; font-size: 15px; border-radius: 60px; box-shadow: 0 4px 16px rgba(26,95,180,0.25);"
                        onclick="document.getElementById('struktur-full').scrollIntoView({ behavior: 'smooth' }); document.getElementById('struktur-full').style.display = 'block'; this.style.display = 'none';">
                    📋 Lihat Selengkapnya
                </button>
            </div>

            {{-- ===== STRUKTUR LENGKAP (seperti admin) ===== --}}
            <div id="struktur-full" style="display: none; margin-top: 48px; padding-top: 32px; border-top: 2px solid var(--border-color);">
                <h3 style="font-family: var(--font-display); font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 24px; color: var(--text-primary);">
                    📋 Struktur Lengkap PUK OMRON
                </h3>

                {{-- Container dengan scroll --}}
                <div class="lp-struktur-scroll" 
                    id="strukturScrollContainer"
                    style="background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 16px 2px; box-shadow: var(--shadow-sm); max-height: 550px; overflow-y: auto; position: relative;">
                    
                    {{-- Scroll gradient bottom --}}
                    <div class="scroll-gradient-bottom" style="position: sticky; bottom: 0; left: 0; right: 0; height: 30px; background: linear-gradient(to bottom, transparent, var(--bg-surface)); pointer-events: none; z-index: 5; border-radius: 0 0 var(--radius-md) var(--radius-md);"></div>
                    
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @foreach($treeArray as $node)
                            @include('landing-page.partials.struktur-node-public', ['node' => $node])
                        @endforeach
                    </ul>
                </div>

                {{-- Tombol scroll to top --}}
                <button class="scroll-to-top" 
                        id="scrollToTopBtn"
                        onclick="document.getElementById('strukturScrollContainer').scrollTo({ top: 0, behavior: 'smooth' })"
                        style="display: none; position: sticky; bottom: 16px; left: 50%; transform: translateX(-50%); z-index: 10; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; background: var(--accent); color: #fff; border: none; cursor: pointer; box-shadow: var(--shadow-md); transition: all 0.3s ease; font-size: 20px;"
                    aria-label="Scroll ke atas">
                    ↑
                </button>

                {{-- Tombol tutup --}}
                <div style="text-align: center; margin-top: 24px;">
                    <button type="button"
                            class="lp-btn lp-btn--ghost"
                            onclick="document.getElementById('struktur-full').style.display = 'none'; document.getElementById('btnLihatSelengkapnya').style.display = 'inline-flex';"
                            style="padding: 10px 28px; border-radius: 60px;">
                        ✕ Tutup
                    </button>
                </div>
            </div>

            {{-- Script untuk mengelola scroll --}}
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('strukturScrollContainer');
                const toTopBtn = document.getElementById('scrollToTopBtn');
                
                if (container && toTopBtn) {
                    // Cek apakah perlu scroll
                    function checkScroll() {
                        if (container.scrollHeight > container.clientHeight) {
                            toTopBtn.style.display = 'flex';
                        } else {
                            toTopBtn.style.display = 'none';
                        }
                    }
                    
                    // Tampilkan/sembunyikan tombol berdasarkan posisi scroll
                    container.addEventListener('scroll', function() {
                        if (this.scrollTop > 100) {
                            toTopBtn.style.display = 'flex';
                        } else {
                            toTopBtn.style.display = 'flex';
                        }
                    });
                    
                    // Check awal
                    setTimeout(checkScroll, 100);
                    
                    // Check ulang saat ukuran berubah
                    window.addEventListener('resize', checkScroll);
                }
            });
            </script>
        </div>
    </section>


    {{-- ===== BERITA (#berita) ===== --}}
    <section id="berita" class="lp-section lp-section--alt">
        <div class="lp-container">
            <div class="lp-section__head-row animate-on-scroll">
                <div class="lp-section__head lp-section__head--left">
                    <span class="lp-section__eyebrow">📰 Berita &amp; Pengumuman</span>
                    <h2 class="lp-section__title" style="font-size: 32px;">Kabar Terbaru dari PUK OMRON</span></h2>
                    <p class="lp-section__desc" style="max-width: 600px; margin-top: 4px; font-size: 16px;">
                        Informasi terkini seputar perjuangan, program, dan pengumuman resmi PUK OMRON.
                    </p>
                </div>
                <a href="{{ route('berita.index') }}" class="lp-btn lp-btn--ghost" style="gap: 8px;">
                    Lihat Semua Berita
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>

            @php
                $beritaTampil = $beritaTerbaru ?? [];
                $headline = $beritaTampil->isNotEmpty() ? $beritaTampil->shift() : null;
                $remaining = $beritaTampil;
            @endphp

            <div class="lp-news-portal">
                {{-- HEADLINE UTAMA --}}
                @if($headline)
                    <div class="lp-news-headline">
                        <a href="{{ route('berita.show', $headline->slug) }}" class="lp-news-headline__link">
                            <div class="lp-news-headline__image">
                                @if (!empty($headline->gambar))
                                    <img src="{{ asset('storage/' . $headline->gambar) }}" alt="{{ $headline->judul }}">
                                @else
                                    <span class="lp-news-headline__placeholder">PUK</span>
                                @endif
                                <div class="lp-news-headline__overlay">
                                    <span class="lp-news-headline__kategori">{{ $headline->kategori }}</span>
                                    <span class="lp-news-headline__badge">Utama</span>
                                </div>
                            </div>
                            <div class="lp-news-headline__content">
                                <h3 class="lp-news-headline__title">{{ $headline->judul }}</h3>
                                <p class="lp-news-headline__ringkasan">{{ $headline->ringkasan }}</p>
                                <div class="lp-news-headline__meta">
                                    <time datetime="{{ $headline->published_at->toDateString() }}">
                                        {{ $headline->published_at->translatedFormat('d F Y') }}
                                    </time>
                                    <span class="lp-news-headline__readmore">Baca Selengkapnya →</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- GRID BERITA LAINNYA --}}
                @if($remaining->isNotEmpty())
                    <div class="lp-news-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px;">
                        @foreach($remaining as $index => $berita)
                            <article class="lp-news-card" style="animation-delay: {{ $index * 0.1 }}s;">
                                <a href="{{ route('berita.show', $berita->slug) }}" class="lp-news-card__link">
                                    <div class="lp-news-card__thumb">
                                        @if (!empty($berita->gambar))
                                            <img src="{{ asset('storage/' . $berita->gambar) }}" alt="{{ $berita->judul }}">
                                        @else
                                            <span>PUK</span>
                                        @endif
                                        @if($loop->first)
                                            <span class="lp-news-card__badge">Terbaru</span>
                                        @endif
                                    </div>
                                    <div class="lp-news-card__body">
                                        <span class="lp-news-card__kategori">{{ $berita->kategori }}</span>
                                        <h4 class="lp-news-card__judul">{{ $berita->judul }}</h4>
                                        <p class="lp-news-card__ringkasan">{{ $berita->ringkasan }}</p>
                                        <time class="lp-news-card__meta" datetime="{{ $berita->published_at->toDateString() }}">
                                            {{ $berita->published_at->translatedFormat('d F Y') }}
                                        </time>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif

                {{-- Jika tidak ada berita --}}
                @if(!$headline && $remaining->isEmpty())
                    <div class="lp-empty-state">
                        <span class="lp-empty-state__icon">📭</span>
                        <p class="lp-empty-state__text">Belum ada berita yang dipublikasikan.</p>
                        <p class="lp-empty-state__sub">Pantau terus halaman ini untuk informasi terbaru.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('landing-page.partials.kegiatan-section')
    @include('landing-page.partials.agenda-section')
    @include('landing-page.partials.inventaris-section')
    @include('landing-page.partials.keuangan-section')

    {{-- ===== GALERI ===== --}}
    <section class="lp-section" id="galeri">
        <div class="lp-container">
            <div class="lp-section__head-row">
                <div>
                    <span class="lp-hero__badge">Galeri</span>
                    <h2 class="lp-section__title">Dokumentasi Kegiatan</h2>
                </div>
                <a href="{{ route('galeri.index') }}" class="lp-btn lp-btn--ghost" style="gap: 8px;">
                    Lihat Semua Galeri
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="lp-galeri-grid">
                @forelse ($galeriTerbaru as $foto)
                    @php
                        $isYoutube = $foto->source === 'youtube';
                        $playSrc = $isYoutube ? null : $foto->embed_url;
                    @endphp
                    <div class="lp-galeri-item"
                        @if ($isYoutube)
                            x-data="lpHomeYtPlayer('yt-home-player-{{ $foto->id }}', '{{ $foto->external_id }}')"
                        @else
                            x-data="{ playing: false }"
                        @endif>

                        {{-- State diam --}}
                        <template x-if="!playing">
                            <div class="lp-thumb-static"
                                @click="{{ $isYoutube ? 'start()' : 'playing = true' }}"
                                role="button" tabindex="0"
                                @keydown.enter="{{ $isYoutube ? 'start()' : 'playing = true' }}">
                                @if ($foto->thumbnail_url)
                                    <img src="{{ $foto->thumbnail_url }}" alt="{{ $foto->judul ?? 'Media' }}" loading="lazy">
                                @else
                                    <div class="lp-thumb-placeholder">
                                        {{ $foto->type === 'video' ? '🎬' : '🖼️' }}
                                    </div>
                                @endif
                                @if ($foto->type === 'video')
                                    <span class="lp-video-play-icon" aria-hidden="true">▶</span>
                                @endif
                                <div class="lp-galeri-item__caption">
                                    {{ $foto->judul ?? 'Media' }}
                                </div>
                            </div>
                        </template>

                        {{-- State aktif: video langsung expand di tempat --}}
                        <template x-if="playing">
                            <div class="lp-thumb-playing">
                                @if ($isYoutube)
                                    @if ($foto->external_id)
                                        <div id="yt-home-player-{{ $foto->id }}"></div>
                                    @else
                                        <div class="lp-thumb-playing__empty">Video tidak ditemukan.</div>
                                    @endif
                                @elseif ($playSrc)
                                    <iframe src="{{ $playSrc }}" frameborder="0"
                                            allow="autoplay; encrypted-media; fullscreen"
                                            allowfullscreen loading="lazy"></iframe>
                                @else
                                    <div class="lp-thumb-playing__empty">Sumber media tidak tersedia.</div>
                                @endif

                                <button type="button" class="lp-thumb-close"
                                        @click.stop="{{ $isYoutube ? 'close()' : 'playing = false' }}"
                                        aria-label="Tutup preview">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                @empty
                    <div class="lp-empty-state">
                        <span class="lp-empty-state__icon">🖼️</span>
                        <p class="lp-empty-state__text">Belum ada media galeri.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('lpHomeYtPlayer', (playerId, videoId) => ({
                playing: false,
                player: null,
                start() {
                    this.playing = true;
                    this.$nextTick(() => this._initPlayer(playerId, videoId));
                },
                _initPlayer(playerId, videoId) {
                    const create = () => {
                        this.player = new YT.Player(playerId, {
                            videoId: videoId,
                            playerVars: { autoplay: 1, rel: 0, modestbranding: 1, playsinline: 1 },
                            events: { onReady: (e) => e.target.playVideo() },
                        });
                    };
                    if (window.YT && window.YT.Player) {
                        create();
                    } else {
                        window.__lpYtQueue = window.__lpYtQueue || [];
                        window.__lpYtQueue.push(create);
                        if (!window.__lpYtApiLoading) {
                            window.__lpYtApiLoading = true;
                            window.onYouTubeIframeAPIReady = function () {
                                (window.__lpYtQueue || []).forEach((fn) => fn());
                                window.__lpYtQueue = [];
                            };
                            const tag = document.createElement('script');
                            tag.src = 'https://www.youtube.com/iframe_api';
                            document.head.appendChild(tag);
                        }
                    }
                },
                close() {
                    if (this.player && this.player.destroy) this.player.destroy();
                    this.player = null;
                    this.playing = false;
                },
            }));
        });
    </script>

    @push('scripts')
    <script>
    function galeriLightbox() {
        return {
            lightboxOpen: false,
            currentMedia: { type: 'image', embed: '', thumb: '', title: '' },
            openLightbox(el) {
                this.currentMedia.type = el.dataset.type;
                this.currentMedia.embed = el.dataset.embed;
                this.currentMedia.thumb = el.dataset.thumb;
                this.currentMedia.title = el.dataset.title;
                this.lightboxOpen = true;
            },
            closeLightbox() {
                this.lightboxOpen = false;
            }
        }
    }
    </script>
    @endpush

    {{-- ===== FAQ (#faq) ===== --}}
    <section id="faq" class="lp-section lp-section--alt">
        <div class="lp-container lp-faq-container">
            <div class="lp-section__head animate-on-scroll">
                <span class="lp-section__eyebrow">Pertanyaan Umum</span>
                <h2 class="lp-section__title">FAQ Keanggotaan</h2>
            </div>

            @php
                $faqList = $faq ?? [
                    ['q' => 'Bagaimana cara menjadi anggota PUK OMRON?', 'a' => 'Karyawan aktif dapat mendaftar melalui pengurus di masing-masing unit kerja atau menghubungi sekretariat PUK untuk mengisi formulir keanggotaan.'],
                    ['q' => 'Apa syarat untuk menjadi anggota?', 'a' => 'Syarat utama adalah berstatus karyawan aktif di lingkungan unit kerja OMRON, baik Permanen maupun Kontrak, dan bersedia mengisi formulir keanggotaan.'],
                    ['q' => 'Apakah ada iuran keanggotaan?', 'a' => 'Ya, ada iuran anggota bulanan yang besarannya ditetapkan berdasarkan AD/ART dan Peraturan Organisasi yang digunakan untuk mendukung program kerja serta operasional PUK.'],
                    ['q' => 'Apa hak dan kewajiban sebagai anggota?', 'a' => 'Anggota berhak mendapat pendampingan hubungan industrial, mengikuti program kesejahteraan, dan menyuarakan aspirasi melalui pengurus. Kewajiban anggota antara lain membayar iuran dan mematuhi AD/ART dan Peraturan Organisasi.'],
                ];
            @endphp

            <div class="lp-faq" x-data="{ open: null }">
                @foreach ($faqList as $index => $item)
                    <div class="lp-faq-item animate-on-scroll delay-{{ ($index % 3) + 1 }}">
                        <button type="button"
                                class="lp-faq-item__question"
                                @click="open = (open === {{ $index }}) ? null : {{ $index }}"
                                :aria-expanded="(open === {{ $index }}).toString()">
                            <span>{{ $item['q'] }}</span>
                            <span class="lp-faq-item__icon" :class="{ 'lp-faq-item__icon--open': open === {{ $index }} }" aria-hidden="true">+</span>
                        </button>
                        <div class="lp-faq-item__answer" x-show="open === {{ $index }}" x-transition x-cloak>
                            <p>{{ $item['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== KONTAK (#kontak) ===== --}}
    <section id="kontak" class="lp-section">
        <div class="lp-container">
            <div class="lp-section__head animate-on-scroll">
                <span class="lp-section__eyebrow">Kontak</span>
                <h2 class="lp-section__title">Hubungi Pengurus</h2>
                <p class="lp-section__desc">
                    Ada pertanyaan, masukan, atau aspirasi? Kirim pesan langsung ke pengurus PUK OMRON.
                </p>
            </div>

            <div class="lp-kontak">
            @if (session('anggota') && session('anggota.email'))
                <form action="{{ route('kontak.store') }}" method="POST" class="lp-form animate-on-scroll">
                    @csrf

                    <div class="lp-form__group">
                        <label for="pesan">Pesan</label>
                        <textarea id="pesan" name="pesan" rows="5" placeholder="Tulis pesan atau aspirasi Anda...">{{ old('pesan') }}</textarea>
                        @error('pesan') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Kirim Pesan</button>

                    @if (session('success'))
                        <p class="lp-form__success">{{ session('success') }}</p>
                    @endif
                    @if (session('error'))
                        <p class="lp-form__error" style="margin-top:8px;">{{ session('error') }}</p>
                    @endif
                </form>
            @elseif (session('anggota'))
                <div class="lp-kontak__locked animate-on-scroll">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <p><strong>Email belum terdaftar.</strong><br>Silakan hubungi pengurus untuk memperbarui data akun Anda sebelum dapat mengirim pesan.</p>
                </div>
            @else
                <div class="lp-kontak__locked animate-on-scroll">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <p><strong>Login diperlukan.</strong><br>Silakan login sebagai anggota untuk mengirim pesan ke pengurus.</p>
                    <a href="{{ route('anggota.login') }}" class="lp-btn lp-btn--primary" style="margin-top:12px;">Login Anggota</a>
                </div>
            @endif

                <div class="lp-kontak__info animate-on-scroll delay-2">
                    <div class="lp-kontak__item">
                        <span class="lp-kontak__label">Sekretariat</span>
                        <p>{{ $alamatSekretariat ?? 'Kantor PUK OMRON, area unit kerja OMRON' }}</p>
                    </div>
                    <div class="lp-kontak__item">
                        <span class="lp-kontak__label">Jam Layanan</span>
                        <p>Senin – Jumat, 08.00 – 16.00</p>
                    </div>
                    <div class="lp-kontak__item">
                        <span class="lp-kontak__label">Email</span>
                        <p><a href="mailto:sekretariat@pukomron.id">sekretariat@pukomron.id</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== MODAL DETAIL ANGGOTA ===== --}}
<div x-data="anggotaModal" x-cloak>
    
    {{-- Overlay --}}
    <div x-show="modalOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="lp-modal-overlay"
         @click.self="closeModal()"
         @keydown.escape="closeModal()">
        
        {{-- Modal Content --}}
        <div class="lp-modal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            
            {{-- Close button --}}
            <button class="lp-modal__close" 
                    @click="closeModal()"
                    aria-label="Tutup modal">
                &times;
            </button>
            
            {{-- Content --}}
            <div class="lp-modal__body">
                <div class="lp-modal__avatar">
                    <template x-if="selectedAnggota.foto">
                        <img :src="selectedAnggota.foto" :alt="selectedAnggota.nama">
                    </template>
                    <template x-if="!selectedAnggota.foto">
                        <div class="lp-modal__avatar-placeholder" 
                             x-text="selectedAnggota.nama ? selectedAnggota.nama.substring(0, 2).toUpperCase() : '??'">
                        </div>
                    </template>
                </div>
                
                <div class="lp-modal__info">
                    <div class="lp-modal__jabatan-puk" x-text="selectedAnggota.jabatanPuk || '-'"></div>
                    <h3 class="lp-modal__nama" x-text="selectedAnggota.nama || '-'"></h3>
                    
                    <div class="lp-modal__divider"></div>
                    
                    <dl class="lp-modal__details">
                        <div>
                            <dt>Departemen</dt>
                            <dd x-text="selectedAnggota.departemen || '-'"></dd>
                        </div>
                        <div>
                            <dt>Jabatan</dt>
                            <dd x-text="selectedAnggota.jabatan || '-'"></dd>
                        </div>
                        <div>
                            <dt>Mulai Bekerja</dt>
                            <dd x-text="selectedAnggota.mulaiBekerja || '-'"></dd>
                        </div>
                        <div>
                            <dt>Pendidikan</dt>
                            <dd x-text="selectedAnggota.pendidikan || '-'"></dd>
                        </div>
                        
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
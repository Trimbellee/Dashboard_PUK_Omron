{{--
    resources/views/landing-page/admin/kegiatan/scan.blade.php

    Check-in kehadiran + live list "Sudah Hadir".

    Sinkronisasi antar device pakai POLLING RINGAN (bukan WebSocket):
      - tiap device kirim token versi (?v=…) tiap ±4–5 detik,
      - kalau tidak ada perubahan server membalas JSON kecil tanpa query berat,
      - kalau ada perubahan, payload dibangun SEKALI lalu di-cache untuk semua device.
    Data awal ikut di HTML ($initial) → halaman langsung terisi.
--}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Scan Kehadiran — ' . $kegiatan->judul)

@push('page-styles')
    @vite('resources/css/admin/kegiatan/scan.css')
@endpush

@section('content')
{{-- JANGAN pakai x-init="init()" — Alpine v3 memanggil init() otomatis. --}}
<div class="lp-scan" x-data="scanKehadiran()">

    {{-- ── HEADER ── --}}
    <header class="lp-scan__head">
        <div>
            <a href="{{ route('admin.kegiatan.pendaftar.index', $kegiatan) }}" class="lp-scan__back">
                <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke daftar pendaftar
            </a>
            <h1 class="lp-scan__title">Scan Kehadiran</h1>
            <p class="lp-scan__sub">{{ $kegiatan->judul }}</p>
        </div>

        <div class="lp-scan__tools">
            <span class="lp-scan__live" :class="{ 'is-off': !online }">
                <i></i><span x-text="online ? 'Live' : 'Terputus'"></span>
            </span>
            <button type="button" class="lp-scan__icon-btn" @click="toggleSuara()"
                    :aria-label="suara ? 'Matikan suara' : 'Nyalakan suara'" :title="suara ? 'Suara aktif' : 'Suara mati'">
                <i class="ti" :class="suara ? 'ti-volume' : 'ti-volume-off'" aria-hidden="true"></i>
            </button>
            <button type="button" class="lp-scan__icon-btn" @click="layarPenuh()"
                    :aria-label="fullscreen ? 'Keluar layar penuh' : 'Layar penuh'" title="Layar penuh">
                <i class="ti" :class="fullscreen ? 'ti-minimize' : 'ti-maximize'" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    {{-- ── AREA SCAN ── --}}
    <section class="lp-scan__scan-area">
        <div class="lp-scan__tabs" role="tablist">
            <button type="button" class="lp-scan__tab" role="tab" :aria-selected="mode === 'reader'"
                    :class="{ 'is-active': mode === 'reader' }" @click="gantiMode('reader')">
                <i class="ti ti-keyboard" aria-hidden="true"></i> Reader USB
            </button>
            <button type="button" class="lp-scan__tab" role="tab" :aria-selected="mode === 'kamera'"
                    :class="{ 'is-active': mode === 'kamera' }" :disabled="!aman" @click="gantiMode('kamera')">
                <i class="ti ti-camera" aria-hidden="true"></i> Scan QR
            </button>
            <button type="button" class="lp-scan__tab" role="tab" :aria-selected="mode === 'nfc'"
                    :class="{ 'is-active': mode === 'nfc' }" :disabled="!aman" x-show="nfcSupported" x-cloak
                    @click="gantiMode('nfc')">
                <i class="ti ti-nfc" aria-hidden="true"></i> Tap NFC
            </button>
        </div>

        <div class="lp-scan__panel">
            <template x-if="mode === 'reader'">
                <div>
                    <input type="text" x-ref="readerInput" x-model="kodeInput" class="lp-scan__reader-input"
                           placeholder="Tap kartu / scan barcode…" autocomplete="off" autocapitalize="off" spellcheck="false"
                           @keydown.enter.prevent="kirimKode(kodeInput)" @blur="onBlur($event)">
                    <p class="lp-scan__hint">Belum ada reader? Ketik NIK di sini lalu tekan Enter.</p>
                </div>
            </template>

            <template x-if="mode === 'kamera'">
                <div>
                    <div id="qr-reader"></div>
                    <p class="lp-scan__hint" x-show="kameraMemuat">Menyiapkan kamera…</p>
                </div>
            </template>

            <template x-if="mode === 'nfc'">
                <div>
                    <div class="lp-scan__nfc">
                        <i class="ti ti-nfc" aria-hidden="true"></i>
                        <p x-text="nfcStatus"></p>
                    </div>
                    <p class="lp-scan__hint">Chrome di Android dengan NFC aktif. Tempelkan kartu ke bagian belakang HP.</p>
                </div>
            </template>

            <p class="lp-scan__hint" x-show="!aman" x-cloak>
                Kamera &amp; NFC hanya jalan lewat HTTPS (atau localhost). Buka halaman ini lewat alamat https.
            </p>

            {{-- Kartu hasil scan --}}
            <div class="lp-scan__result" :data-state="feedback ? feedback.status : 'idle'" role="status" aria-live="assertive">
                <i class="ti lp-scan__result-icon" :class="st.ikon" aria-hidden="true"></i>
                <div class="lp-scan__result-body">
                    <div class="lp-scan__result-title" x-text="st.judul"></div>
                    <div class="lp-scan__result-msg" x-text="feedback ? feedback.message : 'Menunggu kartu atau kode berikutnya…'"></div>
                    <div class="lp-scan__result-person" x-show="feedback && feedback.anggota" x-cloak>
                        <span x-text="feedback?.anggota?.nik"></span> · <span x-text="feedback?.anggota?.departemen"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── STATISTIK ── --}}
    <section class="lp-scan__stats" aria-label="Ringkasan kehadiran">
        <div class="lp-scan__stat lp-scan__stat--ok"><span>Hadir</span><strong x-text="stats.hadir"></strong></div>
        <div class="lp-scan__stat"><span>Terdaftar</span><strong x-text="stats.terdaftar"></strong></div>
        <div class="lp-scan__stat"><span>Belum hadir</span><strong x-text="belum"></strong></div>
        <div class="lp-scan__progress">
            <div class="lp-scan__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="persen">
                <i :style="'width:' + persen + '%'"></i>
            </div>
            <span class="lp-scan__pct"><span x-text="persen"></span>% hadir</span>
        </div>
    </section>
    
    {{-- ── DAFTAR HADIR ── --}}
    <section>
        <div class="lp-scan__list-head">
            <h2 class="lp-scan__mono" >Sudah hadir <span class="lp-scan__mono">(<span x-text="stats.hadir"></span>)</span></h2>

            <div class="lp-scan__search">
                <i class="ti ti-search" aria-hidden="true"></i>
                <input type="text" x-model="liveSearch" @input.debounce.400ms="cariLive()" @blur="onBlur($event)"
                       placeholder="Cari nama atau NIK…" autocomplete="off" aria-label="Cari yang sudah hadir">
                <button type="button" class="lp-scan__clear" x-show="liveSearch.length > 0" x-cloak
                        @click="hapusLive()" aria-label="Hapus pencarian">×</button>
            </div>
        </div>

        <div class="lp-scan__table-wrap" style="margin-top: .75rem;">
            <table class="lp-scan__table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Departemen</th>
                        <template x-for="label in fields" :key="label">
                            <th x-text="label"></th>
                        </template>
                        <th>Status</th>
                        <th>Waktu hadir</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="rows.length === 0">
                        <tr>
                            <td class="lp-scan__empty" :colspan="5 + fields.length"
                                x-text="searchResults !== null ? 'Tidak ditemukan yang cocok.' : 'Belum ada yang check-in.'"></td>
                        </tr>
                    </template>
                    <template x-for="row in rows" :key="row.id">
                        <tr :class="{ 'is-new': searchResults === null && row.id === lastAddedId }">
                            <td>
                                <div class="lp-scan__user">
                                    <span class="lp-scan__avatar" x-text="inisial(row.nama)"></span>
                                    <span x-text="row.nama"></span>
                                </div>
                            </td>
                            <td x-text="row.nik"></td>
                            <td x-text="row.departemen"></td>
                            <template x-for="c in row.custom" :key="c.label">
                                <td x-text="c.value"></td>
                            </template>
                            <td><span class="lp-scan__chip">Hadir</span></td>
                            <td class="lp-scan__muted" x-text="row.tgl_hadir"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <p class="lp-scan__foot" style="margin-top: .6rem;" x-show="searchResults === null && stats.hadir > list.length" x-cloak>
            Menampilkan <strong x-text="list.length"></strong> terbaru dari <strong x-text="stats.hadir"></strong> yang hadir.
            Gunakan pencarian untuk menemukan yang lain.
        </p>
    </section>
</div>
@endsection

@push('scripts')
<script>
function scanKehadiran() {
    const API = {
        scan: '{{ route('admin.kegiatan.pendaftar.scan', $kegiatan) }}',
        list: '{{ route('admin.kegiatan.pendaftar.hadir-list', $kegiatan) }}',
    };
    const INITIAL     = @json($initial);
    const POLL_MS     = 4000;
    const COOLDOWN_MS = 2500;   // kode yang sama diabaikan sebentar (kamera membaca QR berkali-kali)
    // Dimuat HANYA saat tab kamera dibuka. Kalau venue tanpa internet, simpan file ini di /public/vendor
    // lalu ganti URL-nya.
    const QR_LIB      = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';

    const STATE = {
        idle:           { ikon: 'ti-scan',           judul: 'Siap scan' },
        berhasil:       { ikon: 'ti-circle-check',   judul: 'Check-in berhasil' },
        sudah_hadir:    { ikon: 'ti-user-check',     judul: 'Sudah check-in' },
        belum_daftar:   { ikon: 'ti-alert-triangle', judul: 'Belum terdaftar' },
        tidak_boleh:    { ikon: 'ti-ban',            judul: 'Tidak diizinkan' },
        tidak_dikenali: { ikon: 'ti-circle-x',       judul: 'Tidak dikenali' },
        error:          { ikon: 'ti-alert-circle',   judul: 'Terjadi kesalahan' },
    };

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const post = async (url, body) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(body),
        });
        return { ok: res.ok, data: await res.json().catch(() => ({})) };
    };

    const getJson = async (url) => {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(res.status);
        return res.json();
    };

    const simpan = {
        get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
        set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
    };

    return {
        // ── Scan ──
        mode: 'reader',
        kodeInput: '',
        feedback: null,
        loading: false,
        suara: simpan.get('scan_suara') !== '0',
        fullscreen: false,
        aman: window.isSecureContext !== false,
        nfcSupported: 'NDEFReader' in window,
        nfcStatus: 'Menyiapkan NFC…',
        kameraMemuat: false,

        // ── Live list ──
        stats: INITIAL.stats,
        fields: INITIAL.fields,
        list: INITIAL.list,
        lastAddedId: null,
        liveSearch: '',
        searchResults: null,      // null = mode normal
        online: true,

        // ── Internal ──
        _version: INITIAL.version,
        _started: false,
        _pollTimer: null,
        _gagal: 0,
        _feedbackTimer: null,
        _highlightTimer: null,
        _lastKode: '',
        _lastAt: 0,
        _seqLive: 0,
        _audio: null,
        _qr: null,
        _qrPromise: null,
        _nfc: null,
        _lock: null,

        get rows()   { return this.searchResults ?? this.list; },
        get belum()  { return Math.max(0, this.stats.terdaftar - this.stats.hadir); },
        get persen() { return this.stats.terdaftar ? Math.min(100, Math.round(this.stats.hadir / this.stats.terdaftar * 100)) : 0; },
        get st()     { return STATE[this.feedback ? (this.feedback.status in STATE ? this.feedback.status : 'error') : 'idle']; },

        init() {
            if (this._started) return;
            this._started = true;

            this.fokus();
            this._jadwal();
            this._wake();

            document.addEventListener('visibilitychange', () => {
                clearTimeout(this._pollTimer);
                if (!document.hidden) {
                    this._tick();
                    this._jadwal();
                    this._wake();
                }
            });
            document.addEventListener('fullscreenchange', () => { this.fullscreen = !!document.fullscreenElement; });
            window.addEventListener('online',  () => { this._gagal = 0; this._tick(); });
            window.addEventListener('offline', () => { this.online = false; });
            window.addEventListener('beforeunload', () => { this.stopKamera(); this.stopNfc(); });

            // Reader USB = "keyboard": kalau fokus sedang tidak di input, arahkan ketikan ke kolom reader.
            document.addEventListener('keydown', (e) => {
                if (this.mode !== 'reader' || e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
                if (/^(INPUT|TEXTAREA|SELECT)$/.test(e.target?.tagName ?? '')) return;
                this.$refs.readerInput?.focus();
            });
        },

        // ══════════════════════════════════════════════════════
        // UTIL
        // ══════════════════════════════════════════════════════

        inisial(nama) { return nama ? nama.substring(0, 2).toUpperCase() : '-'; },

        fokus() {
            if (this.mode !== 'reader') return;
            this.$nextTick(() => this.$refs.readerInput?.focus());
        },

        // Kembalikan fokus ke kolom reader, kecuali user sengaja pindah ke input/tombol lain.
        onBlur(e) {
            const t = e.relatedTarget;
            if (t && /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(t.tagName)) return;
            this.fokus();
        },

        toggleSuara() {
            this.suara = !this.suara;
            simpan.set('scan_suara', this.suara ? '1' : '0');
            if (this.suara) this.bunyi('berhasil');
        },

        layarPenuh() {
            if (document.fullscreenElement) document.exitFullscreen?.();
            else document.documentElement.requestFullscreen?.().catch(() => {});
        },

        async _wake() {   // layar HP tidak mati saat dipakai scan
            try {
                if ('wakeLock' in navigator && !document.hidden) this._lock = await navigator.wakeLock.request('screen');
            } catch {}
        },

        bunyi(status) {
            navigator.vibrate?.(status === 'berhasil' ? 60 : [90, 60, 90]);
            if (!this.suara) return;
            try {
                const AC = window.AudioContext || window.webkitAudioContext;
                this._audio ??= new AC();               // satu AudioContext saja (browser membatasi jumlahnya)
                const ctx = this._audio;
                if (ctx.state === 'suspended') ctx.resume();

                const nada = status === 'berhasil' ? [[880, .10]]
                           : status === 'sudah_hadir' ? [[660, .08], [660, .08]]
                           : [[220, .28]];
                let t = ctx.currentTime;
                nada.forEach(([f, d]) => {
                    const o = ctx.createOscillator(), g = ctx.createGain();
                    o.frequency.value = f;
                    g.gain.setValueAtTime(.15, t);
                    g.gain.exponentialRampToValueAtTime(.001, t + d);
                    o.connect(g); g.connect(ctx.destination);
                    o.start(t); o.stop(t + d);
                    t += d + .06;
                });
            } catch {}
        },

        tampilkan(data) {
            const status = data.status && data.status in STATE ? data.status : 'error';
            this.feedback = { status, message: data.message || 'Terjadi kesalahan.', anggota: data.anggota ?? null };
            this.bunyi(status);

            clearTimeout(this._feedbackTimer);
            this._feedbackTimer = setTimeout(() => { this.feedback = null; }, 3500);
        },

        // ══════════════════════════════════════════════════════
        // SCAN
        // ══════════════════════════════════════════════════════

        async gantiMode(mode) {
            if (this.mode === mode) return;
            if (mode !== 'reader' && !this.aman) {
                this.tampilkan({ status: 'error', message: 'Kamera & NFC membutuhkan HTTPS (atau localhost).' });
                return;
            }

            await this.stopKamera();
            this.stopNfc();
            this.mode = mode;

            if (mode === 'reader') this.fokus();
            else if (mode === 'kamera') this.startKamera();
            else if (mode === 'nfc') this.startNfc();
        },

        async kirimKode(kode) {
            kode = (kode || '').trim();
            if (!kode || this.loading) return;

            const now = Date.now();
            if (kode === this._lastKode && now - this._lastAt < COOLDOWN_MS) return;
            this._lastKode = kode;
            this._lastAt = now;

            this.loading = true;
            try {
                const { data } = await post(API.scan, { kode });

                if (data.live) {                    // update instan dari payload server (bukan +1 di browser)
                    this.apply(data.live);
                    if (data.status === 'berhasil') this._sorot(data.row_id);
                }
                this.tampilkan(data);
            } catch (e) {
                this._lastAt = 0;                   // gagal jaringan → boleh langsung coba lagi
                this.tampilkan({ status: 'error', message: 'Gagal terhubung ke server.' });
            } finally {
                this.loading = false;
                this.kodeInput = '';
                this.fokus();
            }
        },

        // ── Kamera ──
        _muatQr() {
            if (window.Html5Qrcode) return Promise.resolve();
            return this._qrPromise ??= new Promise((ok, gagal) => {
                const s = document.createElement('script');
                s.src = QR_LIB;
                s.onload = ok;
                s.onerror = () => { this._qrPromise = null; gagal(new Error('lib')); };
                document.head.appendChild(s);
            });
        },

        async startKamera() {
            this.kameraMemuat = true;
            try {
                await this._muatQr();
                await this.$nextTick();
                if (this.mode !== 'kamera') return;

                const scanner = new Html5Qrcode('qr-reader', {
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                    experimentalFeatures: { useBarCodeDetectorIfSupported: true },   // pakai detektor native kalau ada → lebih ringan
                    verbose: false,
                });
                this._qr = scanner;

                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    (teks) => this.kirimKode(teks),
                    () => {}
                );

                if (this.mode !== 'kamera') await this.stopKamera();   // user sudah pindah tab saat kamera menyala
            } catch (e) {
                this.tampilkan({ status: 'error', message: 'Kamera tidak bisa dibuka — cek izin kamera & koneksi.' });
                this._qr = null;
                this.mode = 'reader';
                this.fokus();
            } finally {
                this.kameraMemuat = false;
            }
        },

        async stopKamera() {
            const s = this._qr;
            this._qr = null;
            if (!s) return;
            try { await s.stop(); s.clear(); } catch {}
        },

        // ── NFC ──
        async startNfc() {
            if (!this.nfcSupported) {
                this.nfcStatus = 'NFC tidak didukung di perangkat ini.';
                return;
            }
            try {
                this._nfc = new AbortController();
                const reader = new NDEFReader();
                await reader.scan({ signal: this._nfc.signal });

                this.nfcStatus = 'Siap — tempelkan kartu NFC ke HP.';

                reader.onreading = (e) => {
                    if (e.serialNumber) {
                        this.nfcStatus = 'Kartu terbaca, memproses…';
                        this.kirimKode(e.serialNumber).finally(() => {
                            if (this.mode === 'nfc') this.nfcStatus = 'Siap — tempelkan kartu NFC ke HP.';
                        });
                    }
                };
                reader.onreadingerror = () => this.tampilkan({ status: 'error', message: 'Gagal membaca kartu, coba tap ulang.' });
            } catch (e) {
                this.nfcStatus = 'Gagal mengaktifkan NFC.';
                this.tampilkan({ status: 'error', message: 'Tidak bisa akses NFC — pastikan izin diberikan dan NFC menyala.' });
            }
        },

        stopNfc() {
            this._nfc?.abort();
            this._nfc = null;
        },

        // ══════════════════════════════════════════════════════
        // LIVE LIST
        // ══════════════════════════════════════════════════════

        apply(data) {
            if (!data || data.changed === false) return;

            // Sorot baris yang baru muncul dari device lain
            if (this.list.length) {
                const lama = new Set(this.list.map(r => r.id));
                const baru = (data.list ?? []).find(r => !lama.has(r.id));
                if (baru) this._sorot(baru.id);
            }

            this._version = data.version;
            this.stats = data.stats;
            this.fields = data.fields;
            this.list = data.list;
        },

        _sorot(id) {
            this.lastAddedId = id;
            clearTimeout(this._highlightTimer);
            this._highlightTimer = setTimeout(() => { this.lastAddedId = null; }, 2500);
        },

        async _tick() {
            try {
                const data = await getJson(API.list + (this._version ? '?v=' + encodeURIComponent(this._version) : ''));
                this.online = true;
                this._gagal = 0;
                this.apply(data);
            } catch (e) {
                this.online = false;
                this._gagal++;
            }
        },

        _jadwal() {
            clearTimeout(this._pollTimer);
            // jitter + backoff (maks 8×) supaya banyak device tidak menembak server bersamaan
            const dasar = POLL_MS * Math.min(2 ** this._gagal, 8);
            this._pollTimer = setTimeout(async () => {
                if (!document.hidden) await this._tick();
                this._jadwal();
            }, dasar + Math.random() * 1000);
        },

        async cariLive() {
            const q = this.liveSearch.trim();
            const seq = ++this._seqLive;

            if (q.length < 2) {
                this.searchResults = null;
                return;
            }
            try {
                const data = await getJson(API.list + '?q=' + encodeURIComponent(q));
                if (seq === this._seqLive) this.searchResults = data.list ?? [];
            } catch (e) {
                if (seq === this._seqLive) this.searchResults = [];
            }
        },

        hapusLive() {
            this._seqLive++;
            this.liveSearch = '';
            this.searchResults = null;
        },
    };
}
</script>
@endpush
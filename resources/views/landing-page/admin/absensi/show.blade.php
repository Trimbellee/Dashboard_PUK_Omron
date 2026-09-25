@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-absensi.css')
@endpush


@section('title', $sesi->judul)

@section('content')


<div class="abs-wrap" x-data="sesiAbsensi()" x-init="init()">

    <a href="{{ route('admin.sesi-absensi.index') }}" class="abs-back">← Kembali ke Daftar Sesi</a>

    <div class="abs-header">
        <div>
            <h1>{{ $sesi->judul }}</h1>
            <p>
                <span class="abs-status abs-status--{{ $sesi->isBuka() ? 'buka' : 'tutup' }}">
                    <i></i> {{ $sesi->isBuka() ? 'Sesi Buka' : 'Sesi Tertutup' }}
                </span>
                @if($sesi->lokasi)
                    &nbsp;·&nbsp;{{ $sesi->lokasi }}
                @endif
                @if(!$sesi->isBuka() && $sesi->ditutup_at)
                    &nbsp;·&nbsp;ditutup {{ $sesi->ditutup_at->translatedFormat('d F Y, H:i') }}
                @endif
            </p>
        </div>

        <div class="abs-header__actions">
            <a href="{{ route('admin.sesi-absensi.export', $sesi) }}" class="lp-btn lp-btn--ghost">Export Excel</a>

            @if($bisaKelola)
                @if($sesi->isBuka())
                    <a href="{{ route('admin.sesi-absensi.edit', $sesi) }}" class="lp-btn lp-btn--ghost">Edit Sesi</a>

                    <form method="POST" action="{{ route('admin.sesi-absensi.destroy', $sesi) }}" id="hapus-sesi-form" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button" class="lp-btn lp-btn--danger"
                        @click="$dispatch('open-confirm', {
                            message: @js('Sesi \'' . $sesi->judul . '\' beserta seluruh data kehadirannya akan dihapus permanen.'),
                            formId: 'hapus-sesi-form',
                            confirmLabel: 'Hapus',
                            variant: 'danger',
                        })">
                        Hapus Sesi
                    </button>

                    <form method="POST" action="{{ route('admin.sesi-absensi.tutup', $sesi) }}" id="tutup-sesi-form" style="display:none;">
                        @csrf
                    </form>
                    <button type="button" class="lp-btn lp-btn--ghost" style="color:#dc2626;"
                        @click="$dispatch('open-confirm', {
                            message: 'Setelah ditutup, tidak ada lagi yang bisa tap/scan untuk sesi ini. Data yang sudah tercatat tetap tersimpan. Tombol Edit dan Hapus juga tidak bisa dipakai lagi setelah ini.',
                            formId: 'tutup-sesi-form',
                            confirmLabel: 'Ya, Tutup Sesi',
                            variant: 'danger',
                        })">
                        Tutup Sesi
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.sesi-absensi.buka', $sesi) }}" id="buka-sesi-form" style="display:none;">
                        @csrf
                    </form>
                    <button type="button" class="lp-btn lp-btn--primary"
                        @click="$dispatch('open-confirm', {
                            message: 'Sesi akan dibuka kembali dan admin bisa tap/scan absen lagi. Lanjutkan?',
                            formId: 'buka-sesi-form',
                            confirmLabel: 'Ya, Buka Kembali',
                            variant: 'primary',
                        })">
                        Buka Kembali Sesi
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="abs-alert abs-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="abs-alert abs-alert--error">{{ session('error') }}</div>
    @endif

    {{-- ── AREA TAP (hanya kalau sesi masih buka) ────────────────── --}}
    @if($sesi->isBuka())
    <div class="abs-card">

        <div class="abs-scan-tabs">
            <button type="button" class="lp-btn" :class="mode === 'reader' ? 'lp-btn--primary' : 'lp-btn--ghost'" @click="gantiMode('reader')">Reader / Ketik</button>
            <button type="button" class="lp-btn" :class="mode === 'kamera' ? 'lp-btn--primary' : 'lp-btn--ghost'" @click="gantiMode('kamera')">Scan QR</button>
            <button type="button" class="lp-btn" :class="mode === 'nfc' ? 'lp-btn--primary' : 'lp-btn--ghost'" @click="gantiMode('nfc')" x-show="nfcSupported" x-cloak>Tap NFC</button>
        </div>

        <template x-if="mode === 'reader'">
            <input type="text" x-ref="tapInput" x-model="kodeInput"
                   @keydown.enter.prevent="prosesKode(kodeInput)" @blur="fokusInput()"
                   class="lp-form__input" style="text-align:center;letter-spacing:.05em;"
                   placeholder="Tap kartu di reader, atau ketik NIK manual..." autocomplete="off">
        </template>

        <template x-if="mode === 'kamera'">
            <div id="qr-reader" style="border-radius:8px;overflow:hidden;"></div>
        </template>

        <template x-if="mode === 'nfc'">
            <p style="text-align:center;color:var(--text-secondary);font-size:.85rem;padding:20px 0;" x-text="nfcStatus"></p>
        </template>

        <template x-if="feedback" x-cloak>
            <div class="abs-result" :class="feedback.ok ? 'abs-result--ok' : 'abs-result--err'">
                <span x-text="feedback.message"></span>
            </div>
        </template>

    </div>
    @endif

    {{-- ── LIVE LIST ─────────────────────────────────────────────── --}}
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
            <div style="font-size:.95rem;font-weight:700;color:var(--text-primary);">Sudah Hadir (<span x-text="total"></span>)</div>
            @if($sesi->isBuka())
            <span class="abs-live-badge"><span class="abs-live-dot"></span> live</span>
            @endif
        </div>

        <div class="abs-table-wrap">
            <table class="abs-table">
                <thead>
                    <tr>
                        <th class="abs-col-no">No.</th>
                        <th>Jam</th>
                        @foreach($kolomLabels as $key => $label)
                            @if(in_array($key, $sesi->kolom_ditampilkan))
                            <th>{{ $label }}</th>
                            @endif
                        @endforeach
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="list.length === 0">
                        <tr><td class="abs-empty" :colspan="{{ count($sesi->kolom_ditampilkan) + 3 }}">Belum ada yang hadir.</td></tr>
                    </template>
                    <template x-for="(row, index) in list" :key="row.id">
                        <tr>
                            <td class="abs-col-no" x-text="index + 1"></td>
                            <td style="color:var(--text-secondary);font-family:'DM Mono',monospace;" x-text="row.jam"></td>
                            @foreach($sesi->kolom_ditampilkan as $kolom)
                            <td x-text="row.data['{{ $kolom }}'] ?? '-'"></td>
                            @endforeach
                            <td>
                                <template x-if="row.data._lat && row.data._lng">
                                    <div>
                                        <div style="font-size:.82rem;color:var(--text-primary);">
                                            <span x-text="row.data._lokasi_nama || 'Lokasi tidak diketahui'"></span>
                                            <span x-show="row.data._akurasi_buruk" x-cloak
                                                style="color:#dc2626;font-size:.72rem;font-weight:700;" title="Akurasi GPS rendah">
                                                ⚠
                                            </span>
                                        </div>
                                        <a :href="'https://www.google.com/maps?q=' + row.data._lat + ',' + row.data._lng"
                                        target="_blank" rel="noopener"
                                        style="color:var(--accent);text-decoration:none;font-size:.78rem;font-weight:600;">
                                            Lihat di Peta
                                        </a>
                                    </div>
                                </template>
                                <template x-if="!row.data._lat || !row.data._lng">
                                    <span style="color:var(--text-secondary);font-size:.78rem;">-</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function sesiAbsensi() {
    return {
        mode: 'reader',
        isMobile: /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent),
        kodeInput: '',
        feedback: null,
        loading: false,
        list: [],
        total: 0,
        nfcSupported: 'NDEFReader' in window,
        nfcStatus: 'Pilih "Tap NFC" lalu tempelkan kartu.',
        _qrScanner: null,
        _pollTimer: null,
        _gpsWatchId: null,
        _lastPosition: null,
        IDLE_TIMEOUT_MS: 3 * 60 * 1000, 
        _terakhirId: 0,
        _idleTimer: null,

        init() {
            this.fokusInput();
            this.muatDaftar();
            this.startWatchGps();
            @if($sesi->isBuka())
            this._pollTimer = setInterval(() => this.muatDaftar(), 4000);
            @endif

            window.addEventListener('beforeunload', () => this.stopWatchGps());
        },

        fokusInput() {
            if (this.mode !== 'reader') return;
            this.$nextTick(() => this.$refs.tapInput?.focus());
        },

        gantiMode(mode) {
            if (this.mode === mode) return;
            this.stopKamera();
            this.mode = mode;
            if (mode === 'reader') this.fokusInput();
            if (mode === 'kamera') this.startKamera();
            if (mode === 'nfc') this.startNfc();
        },

        startWatchGps() {
            if (!window.isSecureContext || !('geolocation' in navigator)) return;
            if (this._gpsWatchId !== null) return; // sudah jalan, tidak perlu dobel

            this._gpsWatchId = navigator.geolocation.watchPosition(
                (pos) => {
                    this._lastPosition = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        akurasi: pos.coords.accuracy,
                        ts: Date.now(),
                    };
                },
                (err) => {
                    console.warn('GPS watch error', err.code, err.message);
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
            );

            this.resetIdleTimer();
        },

        stopWatchGps() {
            if (this._gpsWatchId !== null) {
                navigator.geolocation.clearWatch(this._gpsWatchId);
                this._gpsWatchId = null;
            }
            if (this._idleTimer) {
                clearTimeout(this._idleTimer);
                this._idleTimer = null;
            }
        },

        resetIdleTimer() {
            if (this._idleTimer) clearTimeout(this._idleTimer);
            this._idleTimer = setTimeout(() => {
                this.stopWatchGps();
            }, this.IDLE_TIMEOUT_MS);
        },

        bunyikanFeedback(ok) {
            if ('vibrate' in navigator) {
                navigator.vibrate(ok ? 80 : [80, 60, 80]);
            }

            const audio = new Audio(ok ? '/sounds/sukses.mp3' : '/sounds/gagal.mp3');
            audio.play().catch(() => {});
        },

        async ambilGps() {
            const MAKS_UMUR_MS = 15000;

            // Posisi dari watchPosition masih segar → langsung pakai, tanpa nunggu
            if (this._lastPosition && (Date.now() - this._lastPosition.ts) < MAKS_UMUR_MS) {
                return this._lastPosition;
            }

            if (!window.isSecureContext || !('geolocation' in navigator)) {
                console.warn('GPS tidak tersedia: butuh HTTPS.');
                return null;
            }

            // Belum ada fix sama sekali (baru buka halaman) → tunggu sebentar
            const ambil = (highAccuracy, timeout) => new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve({
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        akurasi: pos.coords.accuracy,
                    }),
                    (err) => {
                        console.warn('GPS error', err.code, err.message);
                        resolve(null);
                    },
                    { enableHighAccuracy: highAccuracy, timeout, maximumAge: 60000 }
                );
            });

            return (await ambil(true, 8000)) ?? (await ambil(false, 5000));
        },

        async prosesKode(kode, metode = 'scanner') {
            kode = (kode || '').trim();
            if (!kode || this.loading) return;

            this.startWatchGps();
            this.resetIdleTimer();

            this.loading = true;
            try {
                // GPS selalu dicoba (baik HP maupun PC yang mendukung geolocation)
                const gps = await this.ambilGps();

                // Hanya HP yang mewajibkan GPS berhasil. PC tetap lanjut walau gps null.
                if (this.isMobile && !gps) {
                    this.feedback = {
                        ok: false,
                        message: 'Absen gagal: lokasi (GPS) belum terdeteksi. Pastikan izin lokasi aktif lalu coba lagi.',
                    };
                    return;
                }

                const res = await this.fetchDenganRetry('{{ route('admin.sesi-absensi.scan', $sesi) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        kode,
                        metode,
                        lat: gps?.lat ?? null,
                        lng: gps?.lng ?? null,
                        akurasi: gps?.akurasi ?? null,
                    }),
                });
                const data = await res.json();
                this.feedback = { ok: data.status === 'berhasil', message: data.message };
                this.bunyikanFeedback(this.feedback.ok);

                if (data.status === 'berhasil' && data.row) {
                    this.list.unshift(data.row);
                    this.total++;
                    this._terakhirId = Math.max(this._terakhirId, data.row.id);
                }
            } catch (e) {
                this.feedback = { ok: false, message: 'Gagal terhubung ke server.' };
            } finally {
                this.loading = false;
                this.kodeInput = '';
                this.fokusInput();
                setTimeout(() => { this.feedback = null; }, 3000);
            }
        },

        async fetchDenganRetry(url, options, percobaanMaks = 2) {
            let errorTerakhir;
            for (let i = 0; i < percobaanMaks; i++) {
                try {
                    const res = await fetch(url, options);
                    return res; // dapat response (walau status error) → langsung return, biar caller yang handle
                } catch (e) {
                    errorTerakhir = e;
                    if (i < percobaanMaks - 1) {
                        await new Promise(r => setTimeout(r, 800)); // jeda sebelum retry
                    }
                }
            }
            throw errorTerakhir;
        },

        async muatDaftar() {
        try {
            const url = new URL('{{ route('admin.sesi-absensi.entries', $sesi) }}', window.location.origin);
            if (this._terakhirId > 0) {
                url.searchParams.set('sejak_id', this._terakhirId);
            }

            const res = await fetch(url);
            const data = await res.json();

            if (this._terakhirId === 0) {
                // Load pertama kali: isi penuh
                this.list = data.list;
            } else if (data.list.length > 0) {
                // Polling berikutnya: cuma entri baru, filter yang mungkin sudah
                // ditambahkan lokal duluan (dari tap sendiri) sebelum prepend
                const idYangAda = new Set(this.list.map(r => r.id));
                const entriBaru = data.list.filter(r => !idYangAda.has(r.id));
                if (entriBaru.length > 0) {
                    this.list = [...entriBaru, ...this.list];
                }
            }

            this.total = data.total;
            this._terakhirId = Math.max(this._terakhirId, data.terakhir_id);
        } catch (e) {}
    },

        startKamera() {
            this.$nextTick(() => {
                if (typeof Html5Qrcode === 'undefined') return;
                this._qrScanner = new Html5Qrcode('qr-reader');
                this._qrScanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 240 },
                    (decodedText) => this.prosesKode(decodedText, 'web'),
                    () => {}
                ).catch(() => {
                    this.feedback = { ok: false, message: 'Tidak bisa akses kamera.' };
                });
            });
        },

        stopKamera() {
            if (this._qrScanner) {
                this._qrScanner.stop().catch(() => {});
                this._qrScanner = null;
            }
        },

        async startNfc() {
            if (!this.nfcSupported) return;
            try {
                this.nfcStatus = 'Tempelkan kartu ke HP...';
                const reader = new NDEFReader();
                await reader.scan();
                reader.onreading = (event) => {
                    if (event.serialNumber) this.prosesKode(event.serialNumber, 'nfc');
                };
                reader.onreadingerror = () => {
                    this.feedback = { ok: false, message: 'Gagal membaca kartu, coba tap ulang.' };
                };
            } catch (e) {
                this.nfcStatus = 'Gagal mengaktifkan NFC.';
                this.feedback = { ok: false, message: 'Tidak bisa akses NFC — pastikan izin diberikan.' };
            }
        },
    };
}
</script>
@endpush
@endsection
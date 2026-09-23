{{--
    resources/views/landing-page/admin/kegiatan/scan.blade.php

    Halaman check-in kehadiran + list "Sudah Hadir" live.

    Real-time-nya pakai POLLING (fetch ulang tiap 5 detik), bukan
    WebSocket/broadcasting — jadi tidak butuh Reverb/Pusher/infra tambahan.
    HP yang barusan scan langsung nambah row-nya sendiri ke list (optimistic,
    dari response scan()), sementara HP/device lain nangkep perubahan itu
    lewat polling berikutnya (maks ~5 detik delay). Kalau nanti kamu sudah
    pasang Laravel Reverb, bagian polling ini bisa diganti Echo listener
    buat instan tanpa delay sama sekali — strukturnya sudah siap buat itu.
--}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Scan Kehadiran — ' . $kegiatan->judul)

@push('page-styles')
<style>
    .lp-scan { max-width: 960px; margin: 0 auto; padding: 1rem 0; }
    .lp-scan__head { text-align: center; margin-bottom: 1.25rem; }
    .lp-scan__head h1 { font-size: 1.15rem; font-weight: 700; margin: 0 0 4px; }
    .lp-scan__head p { font-size: .85rem; color: var(--text-secondary, #6b7280); margin: 0; }

    .lp-scan__scan-area { max-width: 480px; margin: 0 auto 2rem; }

    .lp-scan__tabs {
        display: flex; gap: 8px; background: var(--bg-surface-alt, #f3f4f6);
        border-radius: var(--radius-md, 12px); padding: 4px; margin-bottom: 1.25rem;
    }
    .lp-scan__tab {
        flex: 1; text-align: center; padding: 10px; border-radius: var(--radius-sm, 8px);
        font-size: .85rem; font-weight: 600; background: none; border: none; cursor: pointer;
        color: var(--text-secondary, #6b7280);
    }
    .lp-scan__tab.is-active { background: var(--bg-surface, #fff); color: var(--accent, #7c3aed); box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,.06)); }

    .lp-scan__panel {
        background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e5e7eb);
        border-radius: var(--radius-md, 12px); padding: 24px; text-align: center;
    }

    .lp-scan__reader-input {
        width: 100%; text-align: center; font-size: 1.1rem; letter-spacing: .05em;
        padding: 14px; border: 2px dashed var(--border-color, #d1d5db); border-radius: var(--radius-sm, 8px);
        background: var(--bg-surface-alt, #f9fafb);
    }
    .lp-scan__reader-input:focus { outline: none; border-color: var(--accent, #7c3aed); border-style: solid; }
    .lp-scan__reader-hint { font-size: .8rem; color: var(--text-secondary, #9ca3af); margin-top: 10px; }

    #qr-reader { width: 100%; border-radius: var(--radius-sm, 8px); overflow: hidden; }

    .lp-scan__feedback {
        margin-top: 1.25rem; padding: 18px; border-radius: var(--radius-md, 12px);
        font-weight: 600; font-size: .95rem;
    }
    .lp-scan__feedback.berhasil { background: #dcfce7; color: #15803d; }
    .lp-scan__feedback.sudah_hadir { background: #e0e7ff; color: #3730a3; }
    .lp-scan__feedback.belum_daftar { background: #fef3c7; color: #92400e; }
    .lp-scan__feedback.tidak_dikenali,
    .lp-scan__feedback.error { background: #fee2e2; color: #b91c1c; }

    .lp-scan__back { display: block; text-align: center; margin-top: 1.25rem; font-size: .85rem; color: var(--text-secondary, #6b7280); }

    .lp-scan__list-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
    .lp-scan__list-head h2 { font-size: 1rem; font-weight: 700; margin: 0; }
    .lp-scan__live-dot {
        display: inline-flex; align-items: center; gap: 6px; font-size: .75rem;
        color: var(--text-secondary, #6b7280);
    }
    .lp-scan__live-dot span {
        width: 7px; height: 7px; border-radius: 50%; background: #16a34a;
        animation: lp-scan-pulse 1.6s ease-in-out infinite;
    }
    @keyframes lp-scan-pulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }

    .lp-scan__table-wrap {
        background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e5e7eb);
        border-radius: var(--radius-md, 12px); overflow-x: auto;
    }
    .lp-scan__table { width: 100%; min-width: 640px; border-collapse: collapse; font-size: .85rem; }
    .lp-scan__table thead th {
        text-align: left; font-size: .72rem; color: var(--text-secondary, #6b7280);
        padding: .7rem .9rem; border-bottom: 1px solid var(--border-color, #e5e7eb); white-space: nowrap;
    }
    .lp-scan__table tbody td { padding: .65rem .9rem; border-bottom: 1px solid var(--border-color, #f1f2f4); }
    .lp-scan__table tbody tr:last-child td { border-bottom: none; }
    .lp-scan__table tbody tr.is-new { animation: lp-scan-highlight 1.8s ease-out; }
    @keyframes lp-scan-highlight { from { background: #dcfce7; } to { background: transparent; } }

    .lp-scan__chip { display: inline-block; font-size: .7rem; padding: .2rem .55rem; border-radius: 999px; background: #dcfce7; color: #15803d; }
    .lp-scan__empty { text-align: center; padding: 2rem; color: var(--text-secondary, #9ca3af); }
</style>
@endpush

@section('content')
<div class="lp-scan" x-data="scanKehadiran()" x-init="init()">

    <div class="lp-scan__head">
        <h1>Scan Kehadiran</h1>
        <p>{{ $kegiatan->judul }}</p>
    </div>

    <div class="lp-scan__scan-area">
        <div class="lp-scan__tabs">
            <button type="button" class="lp-scan__tab" :class="{ 'is-active': mode === 'reader' }" @click="gantiMode('reader')">
                <i class="ti ti-keyboard" aria-hidden="true"></i> Reader USB
            </button>
            <button type="button" class="lp-scan__tab" :class="{ 'is-active': mode === 'kamera' }" @click="gantiMode('kamera')">
                <i class="ti ti-camera" aria-hidden="true"></i> Scan QR
            </button>
            <button type="button" class="lp-scan__tab" :class="{ 'is-active': mode === 'nfc' }" @click="gantiMode('nfc')">
                <i class="ti ti-nfc" aria-hidden="true"></i> Tap NFC
            </button>
        </div>

        <div class="lp-scan__panel">
            <template x-if="mode === 'reader'">
                <div>
                    <input
                        type="text"
                        x-ref="readerInput"
                        x-model="kodeInput"
                        class="lp-scan__reader-input"
                        placeholder="Tap kartu / scan barcode..."
                        @keydown.enter.prevent="kirimKode(kodeInput)"
                        @blur="focusInput()"
                        autocomplete="off"
                    >
                    <p class="lp-scan__reader-hint">
                        Belum ada reader? Ketik NIK manual di sini lalu tekan Enter buat simulasi.
                    </p>
                </div>
            </template>

            <template x-if="mode === 'kamera'">
                <div id="qr-reader"></div>
            </template>

            <template x-if="mode === 'nfc'">
                <div>
                    <div style="padding:32px 16px;text-align:center;">
                        <i class="ti ti-nfc" style="font-size:48px;color:var(--accent, #7c3aed);" aria-hidden="true"></i>
                        <p style="margin-top:12px;font-weight:600;" x-text="nfcStatus"></p>
                    </div>
                    <p class="lp-scan__reader-hint">
                        Hanya berfungsi di Chrome untuk Android dengan NFC aktif. Tempelkan kartu ke bagian belakang HP.
                    </p>
                </div>
            </template>

            <div class="lp-scan__feedback" :class="feedback.status" x-show="feedback" x-cloak x-transition>
                <div x-text="feedback?.message"></div>
            </div>
        </div>

        <a href="{{ route('admin.kegiatan.pendaftar.index', $kegiatan) }}" class="lp-scan__back">&larr; Kembali ke daftar pendaftar</a>
    </div>

    <div class="lp-scan__list-head">
        <h2>Sudah hadir (<span x-text="hadirList.length"></span>)</h2>
        <span class="lp-scan__live-dot"><span></span> live</span>
    </div>

    <div class="lp-scan__table-wrap">
        <table class="lp-scan__table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Departemen</th>
                    <template x-for="label in customFieldLabels" :key="label">
                        <th x-text="label"></th>
                    </template>
                    <th>Status</th>
                    <th>Tgl Hadir</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="hadirList.length === 0">
                    <tr>
                        <td class="lp-scan__empty" :colspan="4 + customFieldLabels.length">Belum ada yang check-in.</td>
                    </tr>
                </template>
                <template x-for="row in hadirList" :key="row.id">
                    <tr :class="{ 'is-new': row.id === lastAddedId }">
                        <td x-text="row.nama"></td>
                        <td x-text="row.nik"></td>
                        <td x-text="row.departemen"></td>
                        <template x-for="c in row.custom" :key="c.label">
                            <td x-text="c.value"></td>
                        </template>
                        <td><span class="lp-scan__chip">Hadir</span></td>
                        <td x-text="row.tgl_hadir"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
function scanKehadiran() {
    return {
        mode: 'reader',
        kodeInput: '',
        feedback: null,
        loading: false,
        _feedbackTimer: null,
        _qrScanner: null,

        hadirList: [],
        customFieldLabels: [],
        lastAddedId: null,
        _pollTimer: null,

        nfcStatus: 'Siapkan HP untuk tap kartu...',
        _nfcController: null,

        init() {
            this.focusInput();
            this.muatDaftarHadir();
            this._pollTimer = setInterval(() => this.muatDaftarHadir(), 5000);

            // Berhenti polling kalau tab disembunyikan, lanjut lagi begitu aktif —
            // hemat request pas laptop/HP di-minimize.
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    clearInterval(this._pollTimer);
                } else {
                    this.muatDaftarHadir();
                    this._pollTimer = setInterval(() => this.muatDaftarHadir(), 5000);
                }
            });
        },

        focusInput() {
            if (this.mode !== 'reader') return;
            this.$nextTick(() => this.$refs.readerInput?.focus());
        },

        gantiMode(mode) {
            if (this.mode === mode) return;

            // Selalu hentikan mode sebelumnya dulu
            this.stopKamera();
            this.stopNfc();

            this.mode = mode;

            if (mode === 'reader') {
                this.focusInput();
            } else if (mode === 'kamera') {
                this.startKamera();
            } else if (mode === 'nfc') {
                this.startNfc();
            }
        },

        async kirimKode(kode) {
            kode = (kode || '').trim();
            if (!kode || this.loading) return;

            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.kegiatan.pendaftar.scan', $kegiatan) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ kode }),
                });
                const data = await res.json();
                this.tampilkanFeedback(data);
            } catch (e) {
                this.tampilkanFeedback({ status: 'error', message: 'Gagal terhubung ke server.' });
            } finally {
                this.loading = false;
                this.kodeInput = '';
                this.focusInput();
            }
        },

        tampilkanFeedback(data) {
            this.feedback = data;
            this.mainkanBeep(data.status === 'berhasil' || data.status === 'sudah_hadir');

            // Optimistic update — begitu SUKSES di HP ini, langsung tambahkan
            // ke list lokal tanpa nunggu polling berikutnya.
            if (data.status === 'berhasil' && data.row) {
                if (!this.hadirList.some(r => r.id === data.row.id)) {
                    this.hadirList.unshift(data.row);
                    this.lastAddedId = data.row.id;
                }
            }

            clearTimeout(this._feedbackTimer);
            this._feedbackTimer = setTimeout(() => { this.feedback = null; }, 2500);
        },

        async muatDaftarHadir() {
            try {
                const res = await fetch('{{ route('admin.kegiatan.pendaftar.hadir-list', $kegiatan) }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.customFieldLabels = data.fields;
                this.hadirList = data.list;
            } catch (e) {}
        },

        mainkanBeep(sukses) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = sukses ? 880 : 220;
                osc.connect(gain);
                gain.connect(ctx.destination);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                osc.start();
                osc.stop(ctx.currentTime + (sukses ? 0.12 : 0.25));
            } catch (e) {}
        },

        startKamera() {
            this.$nextTick(() => {
                if (typeof Html5Qrcode === 'undefined') {
                    this.tampilkanFeedback({ status: 'error', message: 'Library kamera gagal dimuat.' });
                    return;
                }

                this._qrScanner = new Html5Qrcode('qr-reader');
                this._qrScanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 240 },
                    (decodedText) => this.kirimKode(decodedText),
                    () => {}
                ).catch(() => {
                    this.tampilkanFeedback({ status: 'error', message: 'Tidak bisa akses kamera — pastikan izin kamera diberikan.' });
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
            if (!('NDEFReader' in window)) {
                this.nfcStatus = 'NFC tidak didukung — gunakan Chrome di Android dengan NFC aktif.';
                this.tampilkanFeedback({ status: 'error', message: 'Browser/HP ini tidak mendukung Web NFC.' });
                return;
            }

            try {
                this._nfcController = new AbortController();
                const reader = new NDEFReader();

                await reader.scan({ signal: this._nfcController.signal });

                this.nfcStatus = 'Siap — tempelkan kartu NFC ke HP.';

                reader.onreading = (event) => {
                    // event.serialNumber contoh: "04:a3:b2:c1"
                    if (event.serialNumber) {
                        this.nfcStatus = 'Kartu terbaca, memproses...';
                        this.kirimKode(event.serialNumber);
                    }
                };

                reader.onreadingerror = () => {
                    this.tampilkanFeedback({ status: 'error', message: 'Gagal membaca kartu, coba tap ulang.' });
                };
            } catch (e) {
                this.nfcStatus = 'Gagal mengaktifkan NFC.';
                this.tampilkanFeedback({
                    status: 'error',
                    message: 'Tidak bisa akses NFC — pastikan izin diberikan dan NFC menyala di pengaturan HP.',
                });
            }
        },

        stopNfc() {
            if (this._nfcController) {
                this._nfcController.abort();
                this._nfcController = null;
            }
        },
    };
}
</script>
@endpush
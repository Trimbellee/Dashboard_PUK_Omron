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
                    </tr>
                </thead>
                <tbody>
                    <template x-if="list.length === 0">
                        <tr><td class="abs-empty" :colspan="{{ count($sesi->kolom_ditampilkan) + 2 }}">Belum ada yang hadir.</td></tr>
                    </template>
                    <template x-for="(row, index) in list" :key="row.id">
                        <tr>
                            <td class="abs-col-no" x-text="index + 1"></td>
                            <td style="color:var(--text-secondary);font-family:'DM Mono',monospace;" x-text="row.jam"></td>
                            @foreach($sesi->kolom_ditampilkan as $kolom)
                            <td x-text="row.data['{{ $kolom }}'] ?? '-'"></td>
                            @endforeach
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
        kodeInput: '',
        feedback: null,
        loading: false,
        list: [],
        total: 0,
        nfcSupported: 'NDEFReader' in window,
        nfcStatus: 'Pilih "Tap NFC" lalu tempelkan kartu.',
        _qrScanner: null,
        _pollTimer: null,

        init() {
            this.fokusInput();
            this.muatDaftar();
            @if($sesi->isBuka())
            this._pollTimer = setInterval(() => this.muatDaftar(), 4000);
            @endif
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

        async prosesKode(kode, metode = 'scanner') {
            kode = (kode || '').trim();
            if (!kode || this.loading) return;

            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.sesi-absensi.scan', $sesi) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ kode, metode }),
                });
                const data = await res.json();
                this.feedback = { ok: data.status === 'berhasil', message: data.message };

                if (data.status === 'berhasil' && data.row) {
                    this.list.unshift(data.row);
                    this.total++;
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

        async muatDaftar() {
            try {
                const res = await fetch('{{ route('admin.sesi-absensi.entries', $sesi) }}');
                const data = await res.json();
                this.list = data.list;
                this.total = data.total;
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
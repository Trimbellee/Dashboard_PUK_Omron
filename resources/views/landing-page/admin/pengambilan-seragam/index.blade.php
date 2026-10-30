@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-absensi.css')
    <style>
        /* Pemilih "Dibagikan" — ringkas di header */
        .psg-jenis__label { font-size: .75rem; font-weight: 600; color: var(--text-secondary); }
        .psg-jenis { display: inline-flex; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: var(--bg-surface); }
        .psg-jenis__btn {
            border: 0; background: transparent; color: var(--text-secondary); cursor: pointer;
            padding: 5px 12px; font-size: .8rem; font-weight: 600; font-family: inherit; line-height: 1.4;
        }
        .psg-jenis__btn + .psg-jenis__btn { border-left: 1px solid var(--border-color); }
        .psg-jenis__btn.is-active { background: var(--text-primary); color: var(--bg-surface); }
        .psg-jenis__btn:disabled { opacity: .55; cursor: not-allowed; }
        .psg-jenis__btn:focus-visible { outline: 2px solid var(--text-primary); outline-offset: -2px; }

        /* Statistik kepemilikan */
        .psg-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; }
        .psg-stat {
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md);
            padding: 14px 16px; display: flex; flex-direction: column; gap: 10px;
        }
        .psg-stat__head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; color: var(--text-primary); }
        .psg-stat__sub { font-size: .75rem; color: var(--text-secondary); }
        .psg-stat__nums { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .psg-stat__nums div { display: flex; flex-direction: column; }
        .psg-stat__nums b { font-size: 1.4rem; font-weight: 800; line-height: 1.1; color: var(--text-primary); }
        .psg-stat__nums span { font-size: .72rem; color: var(--text-secondary); }
        .psg-bar { height: 6px; border-radius: 999px; background: var(--bg-surface-alt); overflow: hidden; }
        .psg-bar span { display: block; height: 100%; background: #22a35a; border-radius: 999px; transition: width .3s; }
        .psg-stat__rincian { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
        .psg-stat__rincian-label { font-size: .72rem; font-weight: 600; color: var(--text-secondary); }
        .psg-stat__foot { margin-top: auto; display: flex; justify-content: flex-start; }   /* pojok kiri bawah kartu */
        .psg-detailbtn {
            border: 1px solid var(--border-color); background: transparent; color: var(--text-primary); cursor: pointer;
            padding: 5px 12px; border-radius: 8px; font-size: .8rem; font-weight: 600; font-family: inherit;
        }
        .psg-detailbtn:hover { background: var(--bg-surface-alt); }
        .psg-detailbtn:focus-visible { outline: 2px solid var(--text-primary); outline-offset: 2px; }

        /* Modal detail ukuran */
        .psg-modal { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(0, 0, 0, .45); }
        .psg-modal__box {
            width: 100%; max-width: 420px; max-height: 85vh; overflow: auto; padding: 18px;
            background: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-color);
            border-radius: var(--radius-md); box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
        }
        .psg-modal__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .psg-modal__title { margin: 0; font-size: 1rem; font-weight: 700; }
        .psg-modal__sub { margin: 2px 0 0; font-size: .75rem; color: var(--text-secondary); }
        .psg-modal__close { border: 0; background: transparent; cursor: pointer; font-size: 1.4rem; line-height: 1; padding: 2px 8px; border-radius: 6px; color: var(--text-secondary); }
        .psg-modal__close:hover { background: var(--bg-surface-alt); color: var(--text-primary); }
        .psg-sizes { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .psg-sizes th { text-align: left; font-size: .72rem; font-weight: 600; color: var(--text-secondary); padding: 6px 4px; border-bottom: 1px solid var(--border-color); }
        .psg-sizes td { padding: 8px 4px; border-bottom: 1px solid var(--border-color); }
        .psg-sizes th:not(:first-child), .psg-sizes td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
        .psg-sizes td.is-need { font-weight: 700; }
        .psg-sizes tr.is-muted td { color: var(--text-secondary); }
        .psg-sizes tfoot td { font-weight: 700; border-bottom: 0; }

        /* Kartu orang (dipakai di kartu konfirmasi & kartu hasil) */
        .psg-person__nama { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); }
        .psg-person__meta { font-size: .8rem; color: var(--text-secondary); margin-top: 2px; }

        .psg-size {
            display: inline-flex; align-items: center; justify-content: center; min-width: 56px;
            padding: 4px 14px; border-radius: 10px; font-size: 1.5rem; font-weight: 800; letter-spacing: .02em;
            color: var(--text-primary); background: var(--bg-surface-alt); border: 1px solid var(--border-color);
        }
        .psg-chip {
            display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .7rem; font-weight: 700;
            background: var(--bg-surface-alt); color: var(--text-secondary); border: 1px solid var(--border-color);
        }
        .psg-chip--warn { background: #fff7e6; color: #92400e; border-color: #f5d9a6; }
        [data-theme="dark"] .psg-chip--warn { background: #3a2b10; color: #fbbf24; border-color: #5b4316; }

        /* Kartu konfirmasi (sudah punya / ukuran kosong) */
        .psg-pending {
            border: 2px solid #f5b942; background: #fff7e6; border-radius: var(--radius-md);
            padding: 18px; display: flex; flex-direction: column; gap: 14px;
        }
        [data-theme="dark"] .psg-pending { background: #2a2110; border-color: #8a6414; }
        .psg-pending__title { font-size: .75rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #92400e; }
        [data-theme="dark"] .psg-pending__title { color: #fbbf24; }
        .psg-item {
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm);
            padding: 12px 14px; display: flex; flex-direction: column; gap: 8px;
        }
        .psg-item__head { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: .95rem; color: var(--text-primary); }
        .psg-warn { margin: 0; font-size: .85rem; color: #92400e; font-weight: 600; }
        [data-theme="dark"] .psg-warn { color: #fbbf24; }
        .psg-note { margin: 0; font-size: .82rem; color: var(--text-secondary); }
        .psg-pending__actions { display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .psg-pending__actions .lp-btn { min-width: 140px; }

        /* Kartu hasil (setelah tercatat): ukuran dibuat besar supaya petugas tidak salah ambil */
        .psg-hasil {
            border: 2px solid #86d3a2; background: #e7f6ec; border-radius: var(--radius-md);
            padding: 18px; display: flex; flex-direction: column; gap: 12px;
        }
        [data-theme="dark"] .psg-hasil { background: #16301f; border-color: #1f4a30; }
        .psg-hasil__item { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; color: var(--text-primary); }
        .psg-hasil__jenis { font-size: 1rem; font-weight: 700; min-width: 70px; }
        .psg-hasil__total { font-size: .8rem; color: var(--text-secondary); }

        .abs-result.psg-result--info { background: var(--bg-surface-alt); color: var(--text-primary); border: 1px solid var(--border-color); }

        .psg-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .psg-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .psg-pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
            background: var(--bg-surface); border: 1px solid var(--border-color); font-size: .85rem; color: var(--text-secondary);
        }
        .psg-pill b { color: var(--text-primary); font-size: 1rem; }
        .psg-filter { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: var(--text-secondary); }
        .psg-filter .lp-form__input { width: auto; }
    </style>
@endpush

@section('title', 'Pengambilan Seragam')

@section('content')

<div class="abs-wrap" x-data="pengambilanSeragam()" x-init="init()" @keydown.escape.window="detail ? tutupDetail() : (pending && batalPending())">

    <div class="abs-header">
        <div>
            <h1>Pengambilan Seragam</h1>
            <p>Scan kartu RFID, barcode, atau ketik NIK anggota untuk mencatat pengambilan kaos dan kemeja di sekretariat.</p>
        </div>

        <div class="abs-header__actions" style="align-items:center;">
            <span class="psg-jenis__label">Dibagikan</span>
            <div class="psg-jenis" role="group" aria-label="Yang dibagikan">
                <button type="button" class="psg-jenis__btn" :class="{ 'is-active': jenisPilihan === 'kaos' }" :aria-pressed="jenisPilihan === 'kaos'" :disabled="!!pending" @click="gantiJenis('kaos')">Kaos</button>
                <button type="button" class="psg-jenis__btn" :class="{ 'is-active': jenisPilihan === 'kemeja' }" :aria-pressed="jenisPilihan === 'kemeja'" :disabled="!!pending" @click="gantiJenis('kemeja')">Kemeja</button>
                <button type="button" class="psg-jenis__btn" :class="{ 'is-active': jenisPilihan === 'keduanya' }" :aria-pressed="jenisPilihan === 'keduanya'" :disabled="!!pending" @click="gantiJenis('keduanya')">Keduanya</button>
            </div>
        </div>
    </div>

    {{-- ── AREA SCAN ─────────────────────────────────────────────── --}}
    <div class="abs-card">

        <div class="abs-scan-tabs" style="margin-bottom:0;">
            <button type="button" class="lp-btn" :class="mode === 'reader' ? 'lp-btn--primary' : 'lp-btn--ghost'" :disabled="!!pending" @click="gantiMode('reader')">Reader / Ketik</button>
            <button type="button" class="lp-btn" :class="mode === 'kamera' ? 'lp-btn--primary' : 'lp-btn--ghost'" :disabled="!!pending" @click="gantiMode('kamera')">Scan Kamera</button>
        </div>

        <template x-if="mode === 'reader'">
            <input type="text" x-ref="tapInput" x-model="kodeInput"
                   @keydown.enter.prevent="prosesKode(kodeInput)" @blur="onBlur($event)"
                   class="lp-form__input" style="text-align:center;letter-spacing:.05em;"
                   placeholder="Tap kartu di reader, scan barcode, atau ketik NIK lalu Enter..." autocomplete="off">
        </template>

        <template x-if="mode === 'kamera'">
            <div id="qr-reader" style="border-radius:8px;overflow:hidden;"></div>
        </template>

        {{-- Kartu konfirmasi: sudah punya / ukuran belum terisi. Tidak ada yang tercatat sebelum tombol ditekan. --}}
        <template x-if="pending">
            <div class="psg-pending" role="alertdialog" aria-live="assertive" aria-label="Perlu konfirmasi">
                <div class="psg-pending__title">Perlu konfirmasi</div>

                <div class="psg-person">
                    <div class="psg-person__nama" x-text="pending.anggota.nama"></div>
                    <div class="psg-person__meta" x-text="pending.anggota.nik + ' · ' + (pending.anggota.departemen || '-')"></div>
                </div>

                <template x-for="item in pending.items" :key="item.jenis">
                    <div class="psg-item">
                        <div class="psg-item__head">
                            <strong x-text="item.label"></strong>
                            <span class="psg-chip" x-show="item.ukuran" x-text="'Ukuran ' + item.ukuran"></span>
                        </div>

                        <p class="psg-warn" x-show="item.perlu_konfirmasi"
                           x-text="'Sudah punya ' + item.label.toLowerCase() + ' (' + item.jumlah_sebelum + '). Jika tetap diberikan, jumlahnya menjadi (' + (item.jumlah_sebelum + 1) + ').'"></p>

                        <div x-show="item.perlu_ukuran">
                            <p class="psg-warn" style="margin-bottom:6px;">Ukuran belum terisi. Pilih ukuran yang diberikan:</p>
                            <select class="lp-form__input" x-model="pending.ukuran[item.jenis]">
                                <option value="">Pilih ukuran</option>
                                @foreach($ukuranList as $uk)
                                    <option value="{{ $uk }}">{{ $uk }}</option>
                                @endforeach
                            </select>
                        </div>

                        <p class="psg-note" x-show="item.status === 'siap'">Siap dicatat.</p>
                        <p class="psg-note" x-show="item.status === 'baru_saja'" x-text="'Baru saja diambil jam ' + item.jam + ' — tidak dicatat lagi.'"></p>
                    </div>
                </template>

                <div class="psg-pending__actions">
                    <button type="button" x-ref="btnBatalPending" class="lp-btn lp-btn--ghost" @click="batalPending()">Batal</button>
                    <button type="button" class="lp-btn lp-btn--primary" :disabled="loading || !pendingSiap()" @click="konfirmasiPending()" x-text="labelKonfirmasi()"></button>
                </div>
            </div>
        </template>

        {{-- Kartu hasil: ukuran besar supaya petugas mengambil seragam yang benar --}}
        <template x-if="hasil">
            <div class="psg-hasil" role="status" aria-live="polite">
                <div class="psg-person">
                    <div class="psg-person__nama" x-text="hasil.anggota.nama"></div>
                    <div class="psg-person__meta" x-text="hasil.anggota.nik + ' · ' + (hasil.anggota.departemen || '-')"></div>
                </div>
                <template x-for="item in hasil.items.filter(i => i.status === 'dicatat')" :key="item.jenis">
                    <div class="psg-hasil__item">
                        <span class="psg-hasil__jenis" x-text="item.label"></span>
                        <span class="psg-size" x-text="item.ukuran"></span>
                        <span class="psg-hasil__total" x-text="labelJumlah(item.jumlah_sesudah)"></span>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="feedback">
            <div class="abs-result"
                 :class="feedback.tone === 'ok' ? 'abs-result--ok' : (feedback.tone === 'info' ? 'psg-result--info' : 'abs-result--err')"
                 role="status" aria-live="polite">
                <span x-text="feedback.message"></span>
            </div>
        </template>

    </div>

    {{-- ── STATISTIK KEPEMILIKAN (anggota aktif) ─────────────────── --}}
    <template x-if="statistik">
        <div class="psg-stat-grid">
            <template x-for="jenis in ['kaos', 'kemeja']" :key="jenis">
                <div class="psg-stat">
                    <div class="psg-stat__head">
                        <strong x-text="jenis === 'kaos' ? 'Kaos' : 'Kemeja'"></strong>
                        <span class="psg-stat__sub" x-text="persenPunya(jenis) + '% dari ' + statistik.total + ' anggota aktif sudah punya'"></span>
                    </div>

                    <div class="psg-stat__nums">
                        <div><b x-text="statistik[jenis].sudah_punya"></b><span>Sudah punya</span></div>
                        <div><b x-text="statistik[jenis].belum_punya"></b><span>Belum punya</span></div>
                    </div>

                    <div class="psg-bar" aria-hidden="true"><span :style="'width:' + persenPunya(jenis) + '%'"></span></div>

                    <div class="psg-stat__rincian" x-show="statistik[jenis].rincian.length">
                        <span class="psg-stat__rincian-label">Sudah punya:</span>
                        <template x-for="r in statistik[jenis].rincian" :key="r.jumlah">
                            <span class="psg-chip" x-text="r.jumlah + ' buah: ' + r.anggota + ' anggota'"></span>
                        </template>
                    </div>

                    <div class="psg-stat__foot">
                        <button type="button" class="psg-detailbtn" @click="bukaDetailUkuran(jenis)" x-text="'Detail ' + jenis"></button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- ── RIWAYAT ───────────────────────────────────────────────── --}}
    <div>
        <div class="psg-toolbar" style="margin-bottom:10px;">
            <div class="psg-pills">
                <span class="psg-pill">Kaos <b x-text="ringkasan.kaos"></b></span>
                <span class="psg-pill">Kemeja <b x-text="ringkasan.kemeja"></b></span>
            </div>
            <div class="psg-filter">
                <label for="psg-tanggal">Tanggal</label>
                <input type="date" id="psg-tanggal" class="lp-form__input" x-model="tanggal" :max="hariIni" @change="gantiTanggal()">
                <span class="abs-live-badge" x-show="tanggal === hariIni" x-cloak><span class="abs-live-dot"></span> live</span>
            </div>
        </div>

        <div class="abs-table-wrap">
            <table class="abs-table">
                <thead>
                    <tr>
                        <th class="abs-col-no">No.</th>
                        <th>Jam</th>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Jenis</th>
                        <th>Ukuran</th>
                        <th>Dimiliki Sekarang</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="list.length === 0">
                        <tr><td class="abs-empty" colspan="8">Belum ada pengambilan pada tanggal ini.</td></tr>
                    </template>
                    <template x-for="(row, index) in list" :key="row.id">
                        <tr>
                            <td class="abs-col-no" x-text="index + 1"></td>
                            <td style="color:var(--text-secondary);font-family:'DM Mono',monospace;" x-text="row.jam"></td>
                            <td>
                                <span x-text="row.nama"></span>
                                <span class="psg-chip psg-chip--warn" x-show="row.walau_sudah_punya" title="Diberikan walau sudah punya">Tambahan</span>
                            </td>
                            <td style="font-family:'DM Mono',monospace;" x-text="row.nik"></td>
                            <td x-text="row.jenis_label"></td>
                            <td x-text="row.ukuran || '-'"></td>
                            <td x-text="labelJumlah(row.jumlah)"></td>
                            <td>
                                <button type="button" class="abs-undo" @click="batalSeragam(row)" :disabled="loading"
                                        :aria-label="'Batalkan pengambilan ' + row.jenis_label + ' ' + row.nama" title="Batalkan pengambilan">
                                    <i class="ti ti-arrow-back-up" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── MODAL: DETAIL UKURAN ──────────────────────────────────── --}}
    <template x-if="detail">
        <div class="psg-modal" @click.self="tutupDetail()">
            <div class="psg-modal__box" role="dialog" aria-modal="true" aria-labelledby="psg-detail-judul">
                <div class="psg-modal__head">
                    <div>
                        <h2 class="psg-modal__title" id="psg-detail-judul" x-text="'Detail ukuran ' + detail.label"></h2>
                        <p class="psg-modal__sub">Anggota aktif per ukuran yang tercatat. <b>Belum punya</b> = jumlah 0 atau belum diisi.</p>
                    </div>
                    <button type="button" class="psg-modal__close" x-ref="btnTutupDetail" @click="tutupDetail()" aria-label="Tutup">&times;</button>
                </div>

                <p class="psg-note" x-show="detail.loading">Memuat...</p>
                <p class="psg-warn" x-show="detail.galat" x-text="detail.galat"></p>

                <table class="psg-sizes" x-show="!detail.loading && !detail.galat">
                    <thead>
                        <tr>
                            <th>Ukuran</th>
                            <th>Anggota</th>
                            <th>Belum punya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in detail.rincian" :key="r.ukuran">
                            <tr>
                                <td x-text="'Ukuran ' + r.ukuran"></td>
                                <td x-text="r.anggota"></td>
                                <td class="is-need" x-text="r.belum_punya"></td>
                            </tr>
                        </template>
                        <tr class="is-muted" x-show="detail.belum_diisi.anggota > 0">
                            <td>Ukuran belum diisi</td>
                            <td x-text="detail.belum_diisi.anggota"></td>
                            <td x-text="detail.belum_diisi.belum_punya"></td>
                        </tr>
                        <tr class="is-muted" x-show="detail.rincian.length === 0 && detail.belum_diisi.anggota === 0">
                            <td colspan="3">Belum ada data.</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td x-text="detail.total.anggota"></td>
                            <td x-text="detail.total.belum_punya"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </template>

    {{-- Dipakai modal konfirmasi global. Modal memanggil form.submit(), jadi dicegat di init(). --}}
    <form id="batal-seragam-form" method="POST" :action="batalUrl" @submit.prevent="kirimBatal()" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function pengambilanSeragam() {
    return {
        jenisPilihan: 'keduanya',          // 'kaos' | 'kemeja' | 'keduanya'
        mode: 'reader',
        kodeInput: '',
        loading: false,
        feedback: null,                    // { tone: 'ok' | 'info' | 'err', message }
        hasil: null,                       // kartu hasil terakhir yang berhasil dicatat
        pending: null,                     // kartu konfirmasi yang sedang menunggu petugas
        list: [],
        ringkasan: { kaos: 0, kemeja: 0 },
        statistik: null,                   // sebaran kepemilikan anggota aktif (dari server)
        detail: null,                      // modal detail ukuran: { jenis, label, loading, galat, rincian, belum_diisi, total }
        ukuranUrlTemplate: @js(route('admin.pengambilan-seragam.ukuran', '__JENIS__')),
        tanggal: @js($tanggal),
        hariIni: @js($hariIni),
        scanUrl: @js(route('admin.pengambilan-seragam.scan')),
        entriesUrl: @js(route('admin.pengambilan-seragam.entries')),
        batalUrlTemplate: @js(route('admin.pengambilan-seragam.batal', '__ID__')),
        batalUrl: '',
        PENDING_TIMEOUT_MS: 30000,         // konfirmasi yang dibiarkan menggantung dibatalkan otomatis
        _batalRow: null,
        _mutasi: 0,                        // naik tiap list diubah lokal, supaya polling tidak menimpa
        _pollTimer: null,
        _pendingTimer: null,
        _feedbackTimer: null,
        _hasilTimer: null,
        _qrScanner: null,
        _lastKode: '',
        _lastAt: 0,

        init() {
            const form = document.getElementById('batal-seragam-form');
            if (form) {
                form.submit        = () => this.kirimBatal();
                form.requestSubmit = () => this.kirimBatal();
            }
            this.fokusInput();
            this.muatDaftar();
            this._pollTimer = setInterval(() => this.muatDaftar(), 5000);
        },

        // ── Util ───────────────────────────────────────────────────────────────
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        jenisList() {
            return this.jenisPilihan === 'keduanya' ? ['kaos', 'kemeja'] : [this.jenisPilihan];
        },

        gantiJenis(jenis) {
            if (this.pending) return;
            this.jenisPilihan = jenis;
            this.fokusInput();
        },

        persenPunya(jenis) {
            const st = this.statistik;
            if (!st || !st.total) return 0;
            return Math.round((st[jenis].sudah_punya / st.total) * 100);
        },

        labelJumlah(n) {
            if (n === null || n === undefined) return '-';
            return n > 0 ? 'Sudah punya (' + n + ')' : 'Belum punya';
        },

        fokusInput() {
            if (this.mode !== 'reader' || this.pending || this.detail) return;
            this.$nextTick(() => this.$refs.tapInput?.focus({ preventScroll: true }));
        },

        onBlur(e) {
            if (this.pending || this.detail) return;
            const t = e.relatedTarget;
            if (t && /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(t.tagName)) return;
            this.fokusInput();
        },

        bunyikanFeedback(ok) {
            if ('vibrate' in navigator) {
                navigator.vibrate(ok ? 80 : [80, 60, 80]);
            }
            const audio = new Audio(ok ? '/sounds/sukses.mp3' : '/sounds/gagal.mp3');
            audio.play().catch(() => {});
        },

        tampilkanFeedback(tone, message, ms = 4000) {
            this.feedback = { tone, message };
            clearTimeout(this._feedbackTimer);
            this._feedbackTimer = setTimeout(() => { this.feedback = null; }, ms);
        },

        // ── Scan ───────────────────────────────────────────────────────────────
        async prosesKode(kode, metode = 'scanner') {
            kode = (kode || '').trim();
            this.kodeInput = '';

            if (!kode || this.loading) return;

            // Selama kartu konfirmasi terbuka, scan lain diabaikan supaya tidak
            // tercampur dengan orang yang sedang dilayani.
            if (this.pending) return;

            // Kamera membaca kode yang sama berkali-kali dalam satu detik → buang yang beruntun.
            const sekarang = Date.now();
            if (kode === this._lastKode && sekarang - this._lastAt < 1500) return;
            this._lastKode = kode;
            this._lastAt   = sekarang;

            await this.kirim(kode, metode);
        },

        async kirim(kode, metode, konfirmasi = [], ukuran = {}) {
            this.loading = true;
            try {
                const res = await fetch(this.scanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({ kode, metode, jenis: this.jenisList(), konfirmasi, ukuran }),
                });
                const data = await res.json().catch(() => ({}));
                this.tanganiRespons(data, kode, metode);
            } catch (e) {
                this.hasil = null;
                this.tampilkanFeedback('err', 'Gagal terhubung ke server.');
            } finally {
                this.loading = false;
                this.fokusInput();
            }
        },

        tanganiRespons(data, kode, metode) {
            if (data.status === 'perlu_konfirmasi') {
                clearTimeout(this._hasilTimer);
                this.hasil = null;
                this.feedback = null;
                this.pending = {
                    kode, metode,
                    anggota: data.anggota,
                    items: data.items,
                    ukuran: Object.fromEntries(data.items.filter(i => i.perlu_ukuran).map(i => [i.jenis, ''])),
                };
                this.bunyikanFeedback(false);
                clearTimeout(this._pendingTimer);
                this._pendingTimer = setTimeout(() => this.batalPending(true), this.PENDING_TIMEOUT_MS);
                // Fokus awal di "Batal": Enter nyasar dari reader hanya membatalkan, tidak pernah mengonfirmasi.
                this.$nextTick(() => this.$refs.btnBatalPending?.focus());
                return;
            }

            if (data.status === 'berhasil') {
                this.feedback = null;
                this.hasil = { anggota: data.anggota, items: data.items };
                clearTimeout(this._hasilTimer);
                this._hasilTimer = setTimeout(() => { this.hasil = null; }, 10000);

                if (this.tanggal === this.hariIni && Array.isArray(data.rows)) {
                    this._mutasi++;
                    this.list = [...data.rows, ...this.list];
                    data.rows.forEach(r => { this.ringkasan[r.jenis] = (this.ringkasan[r.jenis] || 0) + 1; });
                }
                this.bunyikanFeedback(true);
                this.muatDaftar();   // segarkan statistik kepemilikan
                return;
            }

            this.hasil = null;

            if (data.status === 'sudah_diambil') {
                this.tampilkanFeedback('info', data.message, 5000);
                this.bunyikanFeedback(false);
                return;
            }

            // tidak_dikenali, galat validasi, dsb.
            this.tampilkanFeedback('err', data.message || 'Terjadi kesalahan.');
            this.bunyikanFeedback(false);
        },

        // ── Kartu konfirmasi ───────────────────────────────────────────────────
        pendingSiap() {
            const p = this.pending;
            return !!p && p.items.every(i => !i.perlu_ukuran || !!p.ukuran[i.jenis]);
        },

        labelKonfirmasi() {
            return this.pending && this.pending.items.some(i => i.perlu_konfirmasi)
                ? 'Tetap berikan'
                : 'Catat pengambilan';
        },

        async konfirmasiPending() {
            const p = this.pending;
            if (!p || this.loading || !this.pendingSiap()) return;

            clearTimeout(this._pendingTimer);
            const konfirmasi = p.items.filter(i => i.perlu_konfirmasi).map(i => i.jenis);
            const ukuran     = { ...p.ukuran };

            this.pending = null;
            await this.kirim(p.kode, p.metode, konfirmasi, ukuran);
        },

        batalPending(kedaluwarsa = false) {
            clearTimeout(this._pendingTimer);
            if (!this.pending) return;

            this.pending = null;
            this.tampilkanFeedback(
                'info',
                kedaluwarsa
                    ? 'Konfirmasi kedaluwarsa, tidak ada yang dicatat. Scan ulang kartu bila masih perlu.'
                    : 'Dibatalkan, tidak ada yang dicatat.'
            );
            this.fokusInput();
        },

        // ── Kamera ─────────────────────────────────────────────────────────────
        gantiMode(mode) {
            if (this.mode === mode || this.pending) return;
            this.stopKamera();
            this.mode = mode;
            if (mode === 'reader') this.fokusInput();
            if (mode === 'kamera') this.startKamera();
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
                    this.tampilkanFeedback('err', 'Tidak bisa akses kamera.');
                });
            });
        },

        stopKamera() {
            if (this._qrScanner) {
                this._qrScanner.stop().catch(() => {});
                this._qrScanner = null;
            }
        },

        // ── Daftar & ringkasan ─────────────────────────────────────────────────
        gantiTanggal() {
            if (!this.tanggal) this.tanggal = this.hariIni;
            this.list = [];
            this.muatDaftar();
        },

        async muatDaftar() {
            const mutasiAwal = this._mutasi;
            try {
                const url = new URL(this.entriesUrl, window.location.origin);
                url.searchParams.set('tanggal', this.tanggal);

                const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                // Kalau list berubah lokal selama request berjalan (tap / batal), lewati
                // sekali ini supaya baris yang baru tercatat tidak ikut terhapus. Polling berikutnya menyelaraskan.
                if (mutasiAwal === this._mutasi && data.tanggal === this.tanggal) {
                    this.list = data.list;
                    this.ringkasan = data.ringkasan;
                }

                // Statistik tidak bergantung pada list lokal, jadi selalu dimutakhirkan.
                if (data.statistik) this.statistik = data.statistik;
            } catch (e) {}
        },

        // ── Detail ukuran ──────────────────────────────────────────────────────
        async bukaDetailUkuran(jenis) {
            const nol = () => ({ anggota: 0, belum_punya: 0 });
            this.detail = { jenis, label: jenis, loading: true, galat: '', rincian: [], belum_diisi: nol(), total: nol() };
            this.$nextTick(() => this.$refs.btnTutupDetail?.focus());

            try {
                const res = await fetch(this.ukuranUrlTemplate.replace('__JENIS__', jenis), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('http ' + res.status);
                const data = await res.json();

                // Modal sudah ditutup / diganti jenis lain selama request → abaikan hasilnya.
                if (!this.detail || this.detail.jenis !== jenis) return;

                this.detail = {
                    ...this.detail,
                    loading: false,
                    rincian: data.rincian,
                    belum_diisi: data.belum_diisi,
                    total: data.total,
                };
            } catch (e) {
                if (this.detail && this.detail.jenis === jenis) {
                    this.detail = { ...this.detail, loading: false, galat: 'Gagal memuat detail ukuran.' };
                }
            }
        },

        tutupDetail() {
            this.detail = null;
            this.fokusInput();
        },

        // ── Batal ──────────────────────────────────────────────────────────────
        batalSeragam(row) {
            if (this.loading) return;

            this._batalRow = row;
            this.batalUrl  = this.batalUrlTemplate.replace('__ID__', row.id);

            this.$dispatch('open-confirm', {
                message: `Pengambilan ${row.jenis_label.toLowerCase()} oleh "${row.nama}" (jam ${row.jam}) akan dibatalkan dan jumlah yang dimilikinya dikurangi 1.`,
                confirmLabel: 'Batalkan',
                variant: 'danger',
                formId: 'batal-seragam-form',
            });
        },

        async kirimBatal() {
            const row = this._batalRow;
            if (this.loading || !row) return;

            this.loading = true;
            try {
                const res  = await fetch(this.batalUrl, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                });
                const data = await res.json().catch(() => ({}));

                if (data.status === 'dibatalkan' || data.status === 'sudah_dibatalkan') {
                    this._mutasi++;
                    this.list = this.list.filter(r => r.id !== row.id);
                    if (data.status === 'dibatalkan') {
                        this.ringkasan[row.jenis] = Math.max(0, (this.ringkasan[row.jenis] || 0) - 1);
                    }
                    this.tampilkanFeedback(data.status === 'dibatalkan' ? 'ok' : 'info', data.message);
                    this.muatDaftar();   // selaraskan kolom "Dimiliki Sekarang" baris lain
                } else {
                    this.tampilkanFeedback('err', data.message || 'Gagal membatalkan pengambilan.');
                }
            } catch (e) {
                this.tampilkanFeedback('err', 'Gagal terhubung ke server.');
            } finally {
                this.loading = false;
                this._batalRow = null;
                this.batalUrl = '';
                this.fokusInput();
            }
        },
    };
}
</script>
@endpush
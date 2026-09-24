@extends('layouts.app')

@section('title', 'Pairing Kartu RFID')

@section('content')

{{-- Catatan: JANGAN pakai x-init="init()" — Alpine v3 sudah memanggil init() otomatis. --}}
<div class="rfid-wrap" x-data="rfidPairing()">

    {{-- ── BACK BUTTON ─────────────────────────────────────── --}}
    <a href="{{ route('anggota.index') }}" class="rfid-back" aria-label="Kembali ke daftar anggota">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5"/>
            <path d="m12 19-7-7 7-7"/>
        </svg>
        <span>Kembali</span>
    </a>

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <header class="rfid-header">
        <div class="rfid-header__icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7V5a1 1 0 0 1 1-1h2"/>
                <path d="M20 7V5a1 1 0 0 0-1-1h-2"/>
                <path d="M4 17v2a1 1 0 0 0 1 1h2"/>
                <path d="M20 17v2a1 1 0 0 1-1 1h-2"/>
                <rect x="8" y="9" width="8" height="6" rx="1.5"/>
            </svg>
        </div>
        <div class="rfid-header__text">
            <h1 class="rfid-header__title">Pairing Kartu RFID</h1>
            <p class="rfid-header__subtitle">
                Tap kartu di reader, atau gunakan tombol <strong>Tap NFC</strong> di HP untuk memasangkan kartu ke anggota.
            </p>
        </div>
    </header>

    {{-- ── AREA TAP ────────────────────────────────────────── --}}
    <section class="rfid-card rfid-card--tap" :class="{ 'is-busy': loading }">
        <div class="rfid-tap__input-row">
            <div class="rfid-tap__input-wrap">
                <span class="rfid-tap__input-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="6" width="18" height="12" rx="2"/>
                        <path d="M7 10h.01M11 10h2"/>
                    </svg>
                </span>
                <input type="text" x-ref="tapInput" x-model="kodeInput"
                       @keydown.enter.prevent="prosesKode(kodeInput)"
                       @blur="onBlur($event)"
                       class="form-input rfid-tap__input"
                       placeholder="Tap kartu di reader, atau ketik manual..."
                       autocomplete="off">
                <span class="rfid-tap__pulse" x-show="!loading" aria-hidden="true"></span>
            </div>

            <button type="button" class="btn btn-ghost rfid-tap__nfc"
                    @click="tapNfc()" x-show="nfcSupported" x-cloak>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 8.32a7.43 7.43 0 0 1 0 7.36"/>
                    <path d="M9.46 6.21a11.76 11.76 0 0 1 0 11.58"/>
                    <path d="M12.91 4.1a15.91 15.91 0 0 1 .01 15.8"/>
                    <path d="M16.37 2a20.16 20.16 0 0 1 0 20"/>
                </svg>
                Tap NFC (HP)
            </button>
        </div>

        <p class="rfid-tap__status">
            <span class="rfid-tap__dot" :class="{ 'is-active': !loading }"></span>
            <span x-text="loading ? 'Memproses kode...' : statusText"></span>
        </p>
    </section>

    {{-- ── HASIL: KODE SUDAH TERDAFTAR ─────────────────────── --}}
    <template x-if="mode === 'sudah_terdaftar'">
        <section class="rfid-card rfid-card--warn">
            <div class="rfid-warn__head">
                <span class="rfid-warn__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <path d="M12 9v4M12 17h.01"/>
                    </svg>
                </span>
                <div>
                    <div class="rfid-warn__title">Kartu ini sudah terdaftar</div>
                    <div class="rfid-warn__sub">Pilih tindakan untuk melanjutkan.</div>
                </div>
            </div>

            <div class="rfid-owner">
                <div class="rfid-owner__avatar" x-text="inisial(pemilikSaatIni.nama)"></div>
                <div class="rfid-owner__info">
                    <div class="rfid-owner__name" x-text="pemilikSaatIni.nama"></div>
                    <div class="rfid-owner__nik" x-text="pemilikSaatIni.nik"></div>
                </div>
            </div>

            <div class="rfid-actions">
                <button type="button" class="btn btn-ghost" @click="batalkan()">Batal</button>
                <button type="button" class="btn btn-primary" @click="mode = 'cari_anggota'">
                    Ganti ke Anggota Lain
                </button>
            </div>
        </section>
    </template>

    {{-- ── PENCARIAN ANGGOTA ───────────────────────────────── --}}
    <template x-if="mode === 'cari_anggota'">
        <section class="rfid-card">
            <div class="rfid-search__head">
                <span class="rfid-search__step">Langkah 2</span>
                <div class="rfid-search__title">
                    Pilih anggota untuk kode
                    <code class="rfid-code" x-text="kodeAktif"></code>
                </div>
            </div>

            <div class="rfid-search__field">
                <svg class="rfid-search__icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
                <input type="text" x-model="cari" @input.debounce.300ms="cariAnggota()"
                       class="form-input rfid-search__input"
                       placeholder="Ketik nama atau NIK..." autocomplete="off">
            </div>

            <div class="rfid-search__results">
                <template x-for="item in hasilCari" :key="item.id">
                    <button type="button" @click="pilihAnggota(item)" :disabled="saving" class="rfid-result">
                        <div class="rfid-result__avatar" x-text="inisial(item.nama_lengkap)"></div>
                        <div class="rfid-result__info">
                            <div class="rfid-result__name" x-text="item.nama_lengkap"></div>
                            <div class="rfid-result__meta">
                                <span x-text="item.nik_karyawan"></span>
                                <span class="rfid-result__sep">·</span>
                                <span x-text="item.departemen"></span>
                            </div>
                        </div>
                        <span class="rfid-result__badge" x-show="item.punya_kartu">Sudah ada kartu</span>
                    </button>
                </template>

                <p class="rfid-search__empty" x-show="cari.trim().length >= 2 && hasilCari.length === 0">
                    Tidak ditemukan anggota dengan kata kunci tersebut.
                </p>
                <p class="rfid-search__empty rfid-search__empty--hint" x-show="cari.trim().length < 2">
                    Ketik minimal 2 karakter untuk mulai mencari.
                </p>
            </div>

            <div class="rfid-actions">
                <button type="button" class="btn btn-ghost" @click="batalkan()">Batal</button>
            </div>
        </section>
    </template>

    {{-- ── FEEDBACK ─────────────────────────────────────────── --}}
    <template x-if="feedback">
        <div class="rfid-feedback" :class="feedback.ok ? 'rfid-feedback--ok' : 'rfid-feedback--err'">
            <span class="rfid-feedback__icon" aria-hidden="true">
                <svg x-show="feedback.ok" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                <svg x-show="!feedback.ok" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
            </span>
            <span class="rfid-feedback__msg" x-text="feedback.message"></span>
        </div>
    </template>

    {{-- ── RIWAYAT SESI INI (5 terakhir, badge = jumlah total sesi ini) ── --}}
    <template x-if="riwayat.length > 0">
        <section class="rfid-history">
            <div class="rfid-history__head">
                <span>Dipasangkan sesi ini</span>
                <span class="rfid-history__count" x-text="sesiCount"></span>
            </div>
            <div class="rfid-history__list">
                <template x-for="row in riwayat" :key="row.uid">
                    <div class="rfid-history__item">
                        <span class="rfid-history__name" x-text="row.nama"></span>
                        <span class="rfid-history__code" x-text="row.kode"></span>
                    </div>
                </template>
            </div>
        </section>
    </template>

    {{-- ── LIVE LIST: ANGGOTA YANG SUDAH PUNYA KARTU ────────── --}}
    <section class="rfid-live">
        <div class="rfid-live__head">
            <div class="rfid-live__title">
                <h2>Anggota dengan Kartu RFID</h2>
                <span class="rfid-live__count"><span x-text="pairedTotal"></span> total</span>
            </div>
            <span class="rfid-live__dot"><span></span> live</span>
        </div>

        <div class="rfid-live__search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="text" x-model="liveSearch" @input.debounce.400ms="cariAnggotaLive()"
                   @blur="onBlur($event)"
                   placeholder="Cari nama atau NIK (min. 2 karakter)..." autocomplete="off">
            <button type="button" class="rfid-live__clear" x-show="liveSearch.length > 0"
                    @click="hapusLive()" aria-label="Hapus pencarian">×</button>
        </div>

        <div class="rfid-live__table-wrap">
            <table class="rfid-live__table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Departemen</th>
                        <th>Kode Kartu</th>
                        <th>Dipasangkan</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="rows.length === 0">
                        <tr>
                            <td colspan="5" class="rfid-live__empty"
                                x-text="searchResults !== null
                                    ? 'Tidak ditemukan anggota dengan kata kunci tersebut.'
                                    : 'Belum ada anggota yang dipasangkan kartu RFID.'"></td>
                        </tr>
                    </template>
                    <template x-for="row in rows" :key="row.id">
                        <tr :class="{ 'is-new': searchResults === null && row.id === lastPairedId }">
                            <td>
                                <div class="rfid-live__user">
                                    <span class="rfid-live__avatar" x-text="inisial(row.nama)"></span>
                                    <span x-text="row.nama"></span>
                                </div>
                            </td>
                            <td class="rfid-live__mono" x-text="row.nik"></td>
                            <td x-text="row.departemen"></td>
                            <td><span class="rfid-live__chip" x-text="row.kode"></span></td>
                            <td class="rfid-live__muted" x-text="row.dipasangkan_pretty"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <p class="rfid-live__hint" x-show="searchResults === null && pairedTotal > 20">
            Menampilkan <strong>20</strong> terbaru dari <strong x-text="pairedTotal"></strong> total.
            Gunakan pencarian untuk menemukan yang lain.
        </p>
    </section>

</div>

{{-- ── STYLE ────────────────────────────────────────────────── --}}
@push('styles')
<style>
[x-cloak]{display:none!important}

/* Back */
.rfid-back{display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:8px 14px 8px 12px;font-size:13px;font-weight:600;color:var(--text-secondary);text-decoration:none;background:var(--bg-surface);border:1px solid var(--border);border-radius:999px;transition:color .2s ease,background .2s ease,border-color .2s ease,transform .2s ease,box-shadow .2s ease}
.rfid-back svg{transition:transform .2s ease}
.rfid-back:hover{color:#2563EB;border-color:rgba(37,99,235,.35);background:rgba(37,99,235,.06);transform:translateY(-1px);box-shadow:0 6px 16px -10px rgba(37,99,235,.55)}
.rfid-back:hover svg{transform:translateX(-3px)}
.rfid-back:active{transform:translateY(0);box-shadow:none}
[data-theme="dark"] .rfid-back:hover{color:#93C5FD;border-color:rgba(96,165,250,.35);background:rgba(37,99,235,.15)}

/* Wrapper & header */
.rfid-wrap{max-width:640px;margin:0 auto;display:flex;flex-direction:column;gap:20px;padding:8px 0 32px}
.rfid-header{display:flex;align-items:center;gap:14px;padding:4px 2px 0}
.rfid-header__icon{flex-shrink:0;width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,#2563EB 0%,#3B82F6 100%);box-shadow:0 8px 20px -10px rgba(37,99,235,.55)}
.rfid-header__text{min-width:0}
.rfid-header__title{font-size:20px;font-weight:700;color:var(--text-primary);margin:0 0 2px;letter-spacing:-.01em}
.rfid-header__subtitle{font-size:13px;color:var(--text-muted);margin:0;line-height:1.5}

/* Card */
.rfid-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;padding:22px;box-shadow:0 4px 16px -8px rgba(15,23,42,.08);transition:border-color .2s ease,box-shadow .2s ease}
.rfid-card--tap{background:radial-gradient(120% 120% at 0% 0%,rgba(37,99,235,.06) 0%,transparent 55%),var(--bg-surface);border-color:rgba(37,99,235,.18)}
.rfid-card--tap.is-busy{border-color:rgba(37,99,235,.45);box-shadow:0 0 0 4px rgba(37,99,235,.10)}

/* Input tap */
.rfid-tap__input-row{display:flex;gap:10px;align-items:stretch;flex-wrap:wrap}
.rfid-tap__input-wrap{position:relative;flex:1;min-width:220px;display:flex;align-items:center}
.rfid-tap__input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;display:inline-flex}
.rfid-tap__input{padding-left:38px!important;padding-right:38px!important;text-align:left!important;letter-spacing:.02em!important;font-weight:500}
.rfid-tap__pulse{position:absolute;right:12px;top:50%;transform:translateY(-50%);width:10px;height:10px;border-radius:50%;background:#22C55E;box-shadow:0 0 0 0 rgba(34,197,94,.55);animation:rfid-pulse 2s infinite;pointer-events:none}
@keyframes rfid-pulse{0%{box-shadow:0 0 0 0 rgba(34,197,94,.55)}70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}
.rfid-tap__nfc{display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
.rfid-tap__status{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;color:var(--text-muted);margin:12px 0 0}
.rfid-tap__dot{width:6px;height:6px;border-radius:50%;background:#94A3B8;transition:background .2s ease}
.rfid-tap__dot.is-active{background:#22C55E;box-shadow:0 0 0 3px rgba(34,197,94,.18)}

/* Warn */
.rfid-card--warn{background:linear-gradient(135deg,rgba(251,191,36,.10) 0%,transparent 60%),var(--bg-surface);border-color:rgba(251,191,36,.45)}
.rfid-warn__head{display:flex;align-items:flex-start;gap:12px;margin-bottom:16px}
.rfid-warn__icon{flex-shrink:0;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#FEF3C7;color:#B45309}
.rfid-warn__title{font-size:14px;font-weight:700;color:#92400E;margin-bottom:2px}
.rfid-warn__sub{font-size:12.5px;color:#A16207}

/* Owner */
.rfid-owner{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:var(--bg-surface);border:1px solid rgba(251,191,36,.35);margin-bottom:16px}
.rfid-owner__avatar{flex-shrink:0;width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#92400E;background:#FEF3C7}
.rfid-owner__info{min-width:0}
.rfid-owner__name{font-size:13.5px;font-weight:600;color:var(--text-primary);margin-bottom:2px}
.rfid-owner__nik{font-size:11.5px;color:var(--text-muted);font-family:'DM Mono',monospace}

/* Search */
.rfid-search__head{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
.rfid-search__step{display:inline-block;width:fit-content;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#2563EB;background:rgba(37,99,235,.10);padding:3px 9px;border-radius:999px}
.rfid-search__title{font-size:13.5px;font-weight:600;color:var(--text-primary)}
.rfid-code{font-family:'DM Mono',monospace;font-size:12.5px;background:var(--bg-base);border:1px solid var(--border);padding:2px 8px;border-radius:6px;color:var(--text-primary);margin-left:4px}
.rfid-search__field{position:relative;display:flex;align-items:center;margin-bottom:12px}
.rfid-search__icon{position:absolute;left:12px;color:var(--text-muted);pointer-events:none}
.rfid-search__input{padding-left:38px!important}
.rfid-search__results{display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto;padding-right:4px}
.rfid-search__results::-webkit-scrollbar{width:6px}
.rfid-search__results::-webkit-scrollbar-thumb{background:var(--border);border-radius:999px}
.rfid-search__empty{font-size:12.5px;color:var(--text-muted);text-align:center;padding:16px;margin:0}
.rfid-search__empty--hint{opacity:.75}

/* Result item */
.rfid-result{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:12px;border:1px solid var(--border);background:var(--bg-base);cursor:pointer;text-align:left;font-family:inherit;transition:border-color .15s ease,background .15s ease,transform .15s ease}
.rfid-result:hover:not(:disabled){border-color:rgba(37,99,235,.45);background:rgba(37,99,235,.05);transform:translateY(-1px)}
.rfid-result:disabled{opacity:.6;cursor:wait}
.rfid-result__avatar{flex-shrink:0;width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:700;color:#2563EB;background:rgba(37,99,235,.12)}
.rfid-result__info{flex:1;min-width:0}
.rfid-result__name{font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:2px}
.rfid-result__meta{font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.rfid-result__sep{opacity:.5}
.rfid-result__badge{font-size:10px;font-weight:700;color:#92400E;background:#FEF3C7;padding:3px 8px;border-radius:6px;white-space:nowrap}

/* Actions */
.rfid-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;flex-wrap:wrap}

/* Feedback */
.rfid-feedback{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;font-size:13px;font-weight:500;animation:rfid-fade-in .25s ease}
.rfid-feedback--ok{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46}
.rfid-feedback--err{background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B}
.rfid-feedback__icon{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,.55)}
.rfid-feedback__msg{line-height:1.4}
@keyframes rfid-fade-in{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
[data-theme="dark"] .rfid-feedback--ok{background:#14311f;border-color:#166534;color:#86efac}
[data-theme="dark"] .rfid-feedback--err{background:#3a1d1d;border-color:#7f1d1d;color:#fca5a5}

/* Riwayat */
.rfid-history{background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px}
.rfid-history__head{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:10px}
.rfid-history__count{font-size:11px;font-weight:700;color:#2563EB;background:rgba(37,99,235,.10);padding:2px 9px;border-radius:999px}
.rfid-history__list{display:flex;flex-direction:column;gap:6px}
.rfid-history__item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 12px;border-radius:10px;background:var(--bg-base);border:1px solid var(--border);font-size:12.5px}
.rfid-history__name{color:var(--text-primary);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rfid-history__code{color:var(--text-muted);font-family:'DM Mono',monospace;font-size:11.5px;flex-shrink:0}

/* Live list */
.rfid-live{display:flex;flex-direction:column;gap:12px}
.rfid-live__head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.rfid-live__title{display:flex;align-items:center;gap:10px;min-width:0}
.rfid-live__title h2{font-size:15px;font-weight:700;color:var(--text-primary);margin:0;letter-spacing:-.01em}
.rfid-live__count{font-size:11px;font-weight:700;color:#2563EB;background:rgba(37,99,235,.10);padding:3px 10px;border-radius:999px;white-space:nowrap}
[data-theme="dark"] .rfid-live__count{color:#93C5FD;background:rgba(37,99,235,.22)}
.rfid-live__dot{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:#16A34A}
.rfid-live__dot span{width:7px;height:7px;border-radius:50%;background:#16A34A;animation:rfid-live-pulse 1.6s ease-in-out infinite}
@keyframes rfid-live-pulse{0%,100%{opacity:1}50%{opacity:.3}}
.rfid-live__search{position:relative;display:flex;align-items:center;background:var(--bg-surface);border:1px solid var(--border);border-radius:12px;padding:0 14px;transition:border-color .15s ease,box-shadow .15s ease}
.rfid-live__search:focus-within{border-color:rgba(37,99,235,.45);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.rfid-live__search svg{flex-shrink:0;color:var(--text-muted)}
.rfid-live__search input{flex:1;border:none;outline:none;background:transparent;padding:11px 10px;font-size:13px;font-family:inherit;color:var(--text-primary)}
.rfid-live__clear{border:none;background:transparent;color:var(--text-muted);font-size:20px;line-height:1;cursor:pointer;padding:4px 8px;border-radius:6px;transition:background .15s,color .15s}
.rfid-live__clear:hover{background:var(--bg-base);color:var(--text-primary)}
.rfid-live__table-wrap{background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;overflow-x:auto;box-shadow:0 4px 16px -8px rgba(15,23,42,.06)}
.rfid-live__table{width:100%;min-width:640px;border-collapse:collapse;font-size:13px}
.rfid-live__table thead th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--text-muted);padding:12px 16px;background:var(--bg-base);border-bottom:1px solid var(--border);white-space:nowrap}
.rfid-live__table tbody td{padding:12px 16px;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle}
.rfid-live__table tbody tr:last-child td{border-bottom:none}
.rfid-live__table tbody tr{transition:background .15s ease}
.rfid-live__table tbody tr:hover{background:rgba(37,99,235,.035)}
.rfid-live__table tbody tr.is-new{animation:rfid-live-highlight 2s ease-out}
@keyframes rfid-live-highlight{0%{background:rgba(34,197,94,.18)}100%{background:transparent}}
.rfid-live__user{display:flex;align-items:center;gap:10px;font-weight:600}
.rfid-live__avatar{flex-shrink:0;width:30px;height:30px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#2563EB;background:rgba(37,99,235,.12)}
[data-theme="dark"] .rfid-live__avatar{color:#93C5FD;background:rgba(37,99,235,.22)}
.rfid-live__mono{font-family:'DM Mono',monospace;font-size:12px;color:var(--text-secondary)}
.rfid-live__muted{font-size:12px;color:var(--text-muted);white-space:nowrap}
.rfid-live__chip{display:inline-block;font-family:'DM Mono',monospace;font-size:11px;font-weight:600;color:#1D4ED8;background:rgba(37,99,235,.10);border:1px solid rgba(37,99,235,.20);padding:3px 9px;border-radius:6px;letter-spacing:.02em}
[data-theme="dark"] .rfid-live__chip{color:#93C5FD;background:rgba(37,99,235,.22);border-color:rgba(96,165,250,.30)}
.rfid-live__empty{text-align:center;padding:28px 16px;color:var(--text-muted);font-size:12.5px}
.rfid-live__hint{font-size:11.5px;color:var(--text-muted);text-align:center;margin:4px 0 0}

/* Responsive */
@media (max-width:560px){
.rfid-back{font-size:12.5px;padding:7px 12px 7px 10px}
.rfid-header__icon{width:42px;height:42px;border-radius:12px}
.rfid-header__title{font-size:18px}
.rfid-header__subtitle{font-size:12.5px}
.rfid-card{padding:18px;border-radius:14px}
.rfid-tap__input-wrap{min-width:0}
.rfid-tap__nfc{width:100%;justify-content:center}
.rfid-actions{flex-direction:column-reverse}
.rfid-actions .btn{width:100%;justify-content:center}
.rfid-live__title h2{font-size:14px}
.rfid-live__table{min-width:560px}
.rfid-live__table thead th,.rfid-live__table tbody td{padding:10px 12px}
}
</style>
@endpush

{{-- ── SCRIPT: Alpine state ────────────────────────────────── --}}
<script>
function rfidPairing() {
    const API = {
        check:  '{{ route('anggota.rfid-pairing.check') }}',
        search: '{{ route('anggota.rfid-pairing.search') }}',
        assign: '{{ route('anggota.rfid-pairing.assign') }}',
        list:   '{{ route('anggota.rfid-pairing.paired-list') }}',
    };
    const POLL_MS = 5000;
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // HP ber-NFC pakai tombol Tap NFC → jangan auto-fokus input (biar keyboard tidak muncul terus).
    const autoFokus = !(window.matchMedia?.('(pointer: coarse)').matches && 'NDEFReader' in window);

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

    return {
        // ── Pairing ──
        kodeInput: '',
        kodeAktif: '',
        mode: 'idle',
        statusText: 'Siap menerima kartu...',
        pemilikSaatIni: {},
        cari: '',
        hasilCari: [],
        feedback: null,
        riwayat: [],          // 5 terakhir saja
        sesiCount: 0,         // total pairing di sesi ini
        nfcSupported: 'NDEFReader' in window,
        nfcAktif: false,
        loading: false,
        saving: false,

        // ── Live list ──
        pairedList: [],
        pairedTotal: 0,
        lastPairedId: null,
        liveSearch: '',
        searchResults: null,  // null = mode normal

        // ── Internal ──
        _version: null,
        _pollTimer: null,
        _feedbackTimer: null,
        _seqCari: 0,
        _seqLive: 0,
        _uid: 0,

        get rows() {
            return this.searchResults ?? this.pairedList;
        },

        init() {
            this.fokusInput();
            this.muatPairedList();
            this._jadwalkan();

            document.addEventListener('visibilitychange', () => {
                clearTimeout(this._pollTimer);
                if (!document.hidden) {
                    this.muatPairedList();
                    this._jadwalkan();
                }
            });
        },

        // ══════════════════════════════════════════════════════
        // HELPERS
        // ══════════════════════════════════════════════════════

        inisial(nama) {
            return nama ? nama.substring(0, 2).toUpperCase() : '-';
        },

        setFeedback(ok, message) {
            clearTimeout(this._feedbackTimer);
            this.feedback = { ok, message };
            this._feedbackTimer = setTimeout(() => { this.feedback = null; }, 3000);
        },

        // Kembalikan fokus ke input tap, kecuali user sengaja pindah ke input/tombol lain
        // (mis. kolom cari live list) — tanpa ini kolom cari tidak bisa diketik.
        onBlur(e) {
            const t = e.relatedTarget;
            if (t && /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(t.tagName)) return;
            this.fokusInput();
        },

        fokusInput() {
            if (!autoFokus) return;
            if (this.mode !== 'idle' && this.mode !== 'sudah_terdaftar') return;
            this.$nextTick(() => this.$refs.tapInput?.focus());
        },

        // ══════════════════════════════════════════════════════
        // PAIRING
        // ══════════════════════════════════════════════════════

        async prosesKode(kode) {
            kode = (kode || '').trim();
            if (!kode || this.loading || this.saving) return;

            this.loading = true;
            this.feedback = null;

            try {
                const { ok, data } = await post(API.check, { kode });

                if (!ok) {
                    this.setFeedback(false, data.message || 'Kode tidak valid.');
                    this.mode = 'idle';
                    return;
                }

                this.kodeAktif = data.kode;

                if (data.exists) {
                    this.pemilikSaatIni = data.anggota;
                    this.mode = 'sudah_terdaftar';
                    this.statusText = 'Kartu sudah terdaftar — pilih tindakan di bawah.';
                } else {
                    this.mode = 'cari_anggota';
                    this.cari = '';
                    this.hasilCari = [];
                    this.statusText = 'Kartu baru — cari anggota untuk dipasangkan.';
                }
            } catch (e) {
                this.setFeedback(false, 'Gagal terhubung ke server.');
            } finally {
                this.loading = false;
                this.kodeInput = '';
            }
        },

        async cariAnggota() {
            const q = this.cari.trim();
            const seq = ++this._seqCari;

            if (q.length < 2) {
                this.hasilCari = [];
                return;
            }
            try {
                const data = await getJson(API.search + '?q=' + encodeURIComponent(q));
                if (seq === this._seqCari) this.hasilCari = data;   // abaikan respons yang telat
            } catch (e) {
                if (seq === this._seqCari) this.hasilCari = [];
            }
        },

        async pilihAnggota(item) {
            if (this.saving) return;
            if (item.punya_kartu && !confirm(item.nama_lengkap + ' sudah punya kartu. Kartu lamanya akan diganti dengan kartu ini. Lanjutkan?')) return;
            this.saving = true;

            try {
                const { ok, data } = await post(API.assign, { kode: this.kodeAktif, anggota_id: item.id });

                if (!ok || !data.ok) {
                    this.setFeedback(false, data.message || 'Gagal menyimpan, coba lagi.');
                    return;
                }

                this.setFeedback(true, data.message);

                const a = data.anggota;

                // Riwayat sesi: 5 terakhir + counter terpisah
                this.riwayat = [{ uid: ++this._uid, kode: a.kode, nama: a.nama }, ...this.riwayat].slice(0, 5);
                this.sesiCount++;

                // Update live list instan: pindahkan ke atas, buang pemilik lama kartu ini
                this.pairedList = [
                    a,
                    ...this.pairedList.filter(x => x.id !== a.id && x.id !== data.dilepas_id),
                ].slice(0, 20);
                this.lastPairedId = a.id;

                // Total & versi SELALU dari server — bukan +1 di browser
                this.pairedTotal = data.total;
                this._version = data.version;
            } catch (e) {
                this.setFeedback(false, 'Gagal menyimpan, coba lagi.');
            } finally {
                this.saving = false;
                this.selesai();
            }
        },

        batalkan() {
            this.setFeedback(false, 'Dibatalkan.');
            this.selesai();
        },

        selesai() {
            this.mode = 'idle';
            this.statusText = this.nfcAktif ? 'NFC aktif — tempelkan kartu ke HP...' : 'Siap menerima kartu...';
            this.pemilikSaatIni = {};
            this.cari = '';
            this.hasilCari = [];
            this._seqCari++;
            this.fokusInput();
        },

        async tapNfc() {
            if (!this.nfcSupported) {
                alert('Browser/HP ini tidak mendukung Web NFC. Gunakan Chrome di Android.');
                return;
            }
            if (this.nfcAktif) {
                this.statusText = 'NFC sudah aktif — tempelkan kartu ke HP...';
                return;
            }
            try {
                const reader = new NDEFReader();
                await reader.scan();
                this.nfcAktif = true;
                this.statusText = 'NFC aktif — tempelkan kartu ke HP...';

                reader.onreading = (event) => {
                    if (event.serialNumber) this.prosesKode(event.serialNumber);
                };
                reader.onreadingerror = () => {
                    this.setFeedback(false, 'Gagal membaca kartu, coba tap ulang.');
                };
            } catch (e) {
                this.setFeedback(false, 'Tidak bisa akses NFC — pastikan izin diberikan dan NFC aktif.');
            }
        },

        // ══════════════════════════════════════════════════════
        // LIVE LIST
        // ══════════════════════════════════════════════════════

        _jadwalkan() {
            clearTimeout(this._pollTimer);
            // jitter supaya 10 device tidak menembak server di detik yang sama
            const delay = POLL_MS + Math.random() * 1500;
            this._pollTimer = setTimeout(async () => {
                if (!document.hidden) await this.muatPairedList();
                this._jadwalkan();
            }, delay);
        },

        async muatPairedList() {
            try {
                const data = await getJson(API.list + (this._version ? '?v=' + this._version : ''));

                this.pairedTotal = data.total ?? this.pairedTotal;

                if (!data.changed) return;   // tidak ada perubahan → tidak ada render ulang

                this._version = data.version;
                this.pairedList = data.list ?? [];
            } catch (e) {
                // diamkan — polling berikutnya yang mencoba lagi
            }
        },

        async cariAnggotaLive() {
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

@endsection
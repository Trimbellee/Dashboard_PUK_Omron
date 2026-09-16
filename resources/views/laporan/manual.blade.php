@extends('layouts.app')

@section('title','Input Laporan Kegiatan')

@section('content')
<div class="lk-wrap">

    {{-- HEADER --}}
    <div class="lk-header">
        <div class="lk-header-icon">
            <i class="ti ti-clipboard-text" aria-hidden="true"></i>
        </div>
        <div>
            <div class="lk-header-title">Input laporan kegiatan</div>
            <div class="lk-header-sub">Lengkapi seluruh informasi di bawah sebelum menyimpan</div>
        </div>
    </div>

    {{-- STEP PILLS --}}
    <div class="lk-step-row">
        <span class="lk-step-pill"><span class="lk-dot"></span>Informasi kegiatan</span>
        <span class="lk-step-pill"><span class="lk-dot"></span>Pelaksanaan</span>
        <span class="lk-step-pill"><span class="lk-dot"></span>Anggaran aktual</span>
    </div>

    <form method="POST" action="/laporan/manual/store">
        @csrf
        <input type="hidden" name="pengajuan_id" value="{{ $pengajuan->id }}">

        {{-- CARD 1: INFORMASI KEGIATAN --}}
        <div class="lk-card lk-c1">
            <div class="lk-card-head">
                <div class="lk-card-icon"><i class="ti ti-file-text" aria-hidden="true"></i></div>
                <div>
                    <div class="lk-card-label">Informasi kegiatan</div>
                    <div class="lk-card-desc">Nama dan tujuan pelaksanaan</div>
                </div>
            </div>

            <div class="lk-field">
                <div class="lk-flabel"><i class="ti ti-tag" aria-hidden="true"></i>Nama kegiatan</div>
                <textarea name="nama_kegiatan" rows="2"
                    class="lk-finput auto-expand"
                    placeholder="Masukkan nama kegiatan…">{{ $laporan->nama_kegiatan ?? $pengajuan->keperluan ?? '' }}</textarea>
            </div>

            <div class="lk-field" style="margin-bottom:0">
                <div class="lk-flabel"><i class="ti ti-target" aria-hidden="true"></i>Tujuan</div>
                <input type="text" name="tujuan"
                    value="{{ $laporan->tujuan ?? $pengajuan->tujuan ?? '' }}"
                    class="lk-finput"
                    placeholder="Tujuan pelaksanaan kegiatan">
            </div>
        </div>

        {{-- CARD 2: PELAKSANAAN --}}
        <div class="lk-card lk-c2">
            <div class="lk-card-head">
                <div class="lk-card-icon"><i class="ti ti-calendar-event" aria-hidden="true"></i></div>
                <div>
                    <div class="lk-card-label">Pelaksanaan</div>
                    <div class="lk-card-desc">Waktu dan lokasi kegiatan</div>
                </div>
            </div>

            <div class="lk-g3">
                <div class="lk-field" style="margin-bottom:0">
                    <div class="lk-flabel"><i class="ti ti-calendar" aria-hidden="true"></i>Hari / Tanggal</div>
                    <input type="text" name="tanggal" required
                        value="{{ $laporan->tanggal ?? '' }}"
                        class="lk-finput"
                        placeholder="mis. Senin, 24 Jun 2026">
                </div>
                <div class="lk-field" style="margin-bottom:0">
                    <div class="lk-flabel"><i class="ti ti-clock" aria-hidden="true"></i>Jam</div>
                    <input type="text" name="jam"
                        value="{{ $laporan->jam ?? '' }}"
                        class="lk-finput"
                        placeholder="09.00 – 12.00">
                </div>
                <div class="lk-field" style="margin-bottom:0">
                    <div class="lk-flabel"><i class="ti ti-map-pin" aria-hidden="true"></i>Tempat</div>
                    <input type="text" name="tempat" required
                        value="{{ $laporan->tempat ?? '' }}"
                        class="lk-finput"
                        placeholder="Lokasi pelaksanaan">
                </div>
            </div>
        </div>

        {{-- CARD 3: ANGGARAN --}}
        <div class="lk-card lk-c3">
            <div class="lk-card-head">
                <div class="lk-card-icon"><i class="ti ti-report-money" aria-hidden="true"></i></div>
                <div>
                    <div class="lk-card-label">Anggaran aktual</div>
                    <div class="lk-card-desc">Rincian pengeluaran kegiatan</div>
                </div>
            </div>

            {{-- Budget summary bar --}}
            <div class="lk-budget-bar">
                <div class="lk-bc lk-bc-green">
                    <div class="lk-bc-label"><i class="ti ti-receipt" aria-hidden="true"></i>Anggaran diajukan</div>
                    <div class="lk-bc-val">Rp {{ number_format($anggaran,0,',','.') }}</div>
                </div>
                <div class="lk-bc lk-bc-neutral">
                    <div class="lk-bc-label"><i class="ti ti-sum" aria-hidden="true"></i>Total aktual</div>
                    <div class="lk-bc-val" id="totalAktual">Rp 0</div>
                </div>
                <div class="lk-bc lk-bc-sisa">
                    <div class="lk-bc-label"><i class="ti ti-wallet" aria-hidden="true"></i>Sisa dana</div>
                    <div class="lk-bc-val" id="sisaDanaBar">Rp {{ number_format($anggaran,0,',','.') }}</div>
                </div>
            </div>

            {{-- Table --}}
            <div class="lk-tbl-head">
                <span class="lk-tbl-title">Rincian item pengeluaran</span>
            </div>

            <div class="lk-tbl-wrap">
                <table id="tblAnggaranAktual" class="lk-table">
                    <thead>
                        <tr>
                            <th style="width:20px;text-align:center;">No</th>
                            <th style="width:150px;">Item pengeluaran</th>
                            <th style="width:80px;text-align:right;">Nominal (Rp)</th>
                            <th style="width:20px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($items))
                            @foreach($items as $i => $row)
                            <tr>
                                <td class="lk-td-no row-no">{{ $i + 1 }}</td>
                                <td class="lk-td-item">
                                    <textarea name="aktual[{{ $i }}][item]"
                                        class="mn-inner auto-expand aktual-item"
                                        rows="1">{{ $row['item'] }}</textarea>
                                </td>
                                <td class="lk-td-nom">
                                    <input type="text" inputmode="numeric"
                                        name="aktual[{{ $i }}][nominal]"
                                        class="mn-inner mn-r aktual-nominal"
                                        value="{{ number_format($row['nominal'],0,'.', '.') }}">
                                </td>
                                <td class="lk-td-del">
                                    <button type="button" onclick="removeAktual(this)"
                                        class="lk-del-btn" aria-label="Hapus baris">
                                        <i class="ti ti-x" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="lk-td-no row-no">1</td>
                                <td class="lk-td-item">
                                    <textarea name="aktual[0][item]"
                                        class="mn-inner auto-expand aktual-item"
                                        rows="1"
                                        placeholder="Tulis item pengeluaran…"></textarea>
                                </td>
                                <td class="lk-td-nom">
                                    <input type="text" inputmode="numeric"
                                        name="aktual[0][nominal]"
                                        class="mn-inner mn-r aktual-nominal"
                                        value="0">
                                </td>
                                <td class="lk-td-del">
                                    <button type="button" onclick="removeAktual(this)"
                                        class="lk-del-btn" aria-label="Hapus baris">
                                        <i class="ti ti-x" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="color:var(--text-secondary)">Total</td>
                            <td id="totalTf"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" onclick="addAktual()" class="lk-add-btn">
                <i class="ti ti-plus" aria-hidden="true"></i>Tambah baris
            </button>

            {{-- Sisa dana bottom --}}
            <div class="lk-sisa-bar">
                <div class="lk-sisa-left">
                    <i class="ti ti-cash" aria-hidden="true"></i>
                    <span>Sisa dana yang dikembalikan</span>
                </div>
                <div class="lk-sisa-val" id="sisaDana">Rp {{ number_format($anggaran,0,',','.') }}</div>
            </div>
        </div>

        {{-- FOOTER ACTIONS --}}
        <div class="lk-footer">
            <a href="{{ route('pengajuan.index') }}" class="lk-btn-cancel">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>Batal
            </a>
            <button type="submit" class="lk-btn-save">
                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                {{ $laporan ? 'Update laporan' : 'Simpan laporan' }}
            </button>
        </div>

    </form>
</div>

<script>
const anggaranDiajukan = parseInt('{{ $anggaran }}');
let aktualIndex = document.querySelectorAll('#tblAnggaranAktual tbody tr').length;

function addAktual() {
    const tbody = document.querySelector('#tblAnggaranAktual tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="lk-td-no row-no"></td>
        <td class="lk-td-item">
            <textarea name="aktual[${aktualIndex}][item]"
                class="mn-inner auto-expand aktual-item"
                rows="1"
                placeholder="Tulis item pengeluaran…"></textarea>
        </td>
        <td class="lk-td-nom">
            <input type="text" inputmode="numeric"
                name="aktual[${aktualIndex}][nominal]"
                class="mn-inner mn-r aktual-nominal"
                value="0">
        </td>
        <td class="lk-td-del">
            <button type="button" onclick="removeAktual(this)"
                class="lk-del-btn" aria-label="Hapus baris">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    aktualIndex++;
    tr.querySelector('.aktual-nominal').addEventListener('input', e => {
        formatNominal(e.target); hitungAktual();
    });
    tr.querySelector('.aktual-item').addEventListener('input', e => autoExpand(e.target));
    updateRowNumber(); hitungAktual();
    setTimeout(() => tr.querySelector('.aktual-item').focus(), 30);
}

function removeAktual(btn) {
    btn.closest('tr').remove();
    updateRowNumber(); hitungAktual();
}

function formatNominal(el) {
    let val = el.value.replace(/\D/g, '');
    el.value = val ? val.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '0';
}

function hitungAktual() {
    let total = 0;
    document.querySelectorAll('.aktual-nominal').forEach(input => {
        total += Number(input.value.replace(/\./g, '').replace(/[^0-9]/g, ''));
    });
    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    document.getElementById('totalAktual').textContent = fmt(total);
    document.getElementById('totalTf').textContent     = fmt(total);
    const sisa = anggaranDiajukan - total;
    const col  = sisa < 0 ? '#A32D2D' : '#0F6E56';
    ['sisaDana','sisaDanaBar'].forEach(id => {
        const el = document.getElementById(id);
        el.textContent = fmt(sisa);
        el.style.color = col;
    });
}

function updateRowNumber() {
    document.querySelectorAll('#tblAnggaranAktual tbody tr').forEach((tr, i) => {
        const no = tr.querySelector('.row-no');
        if (no) no.textContent = i + 1;
    });
}

function autoExpand(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('aktual-nominal')) { formatNominal(e.target); hitungAktual(); }
    if (e.target.classList.contains('auto-expand')) autoExpand(e.target);
});

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    const a = document.activeElement;
    if (!a.classList.contains('aktual-item') && !a.classList.contains('aktual-nominal')) return;
    e.preventDefault();
    const tbody = document.querySelector('#tblAnggaranAktual tbody');
    if (a.closest('tr') === tbody.lastElementChild) addAktual();
});

document.addEventListener('DOMContentLoaded', function () {
    hitungAktual();
    document.querySelectorAll('.auto-expand').forEach(el => autoExpand(el));
    updateRowNumber();
});
</script>

<style>
/* ── Wrap ── */
.lk-wrap{max-width:700px;margin:0 auto;padding:28px 20px 40px}

/* ── Header ── */
.lk-header{display:flex;align-items:center;gap:14px;margin-bottom:24px}
.lk-header-icon{width:42px;height:42px;border-radius:12px;background:#EEEDFE;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.lk-header-icon i{font-size:20px;color:#534AB7}
.lk-header-title{font-size:18px;font-weight:500;color:var(--text-primary)}
.lk-header-sub{font-size:12px;color:var(--text-secondary);margin-top:1px}

/* ── Step pills ── */
.lk-step-row{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.lk-step-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:99px;background:#EEEDFE;border:0.5px solid #AFA9EC;font-size:12px;color:#3C3489}
.lk-dot{width:6px;height:6px;border-radius:50%;background:#534AB7}

/* ── Card ── */
.lk-card{background:var(--bg-surface);border:0.5px solid var(--border);border-radius:16px;padding:22px 22px 20px;margin-bottom:14px;position:relative;overflow:hidden}
.lk-card::before{content:'';position:absolute;top:0;left:0;width:3px;height:100%}
.lk-c1::before{background:#534AB7}
.lk-c2::before{background:#0F6E56}
.lk-c3::before{background:#854F0B}

.lk-card-head{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.lk-card-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.lk-card-icon i{font-size:16px}
.lk-c1 .lk-card-icon{background:#EEEDFE}.lk-c1 .lk-card-icon i{color:#534AB7}
.lk-c2 .lk-card-icon{background:#E1F5EE}.lk-c2 .lk-card-icon i{color:#0F6E56}
.lk-c3 .lk-card-icon{background:#FAEEDA}.lk-c3 .lk-card-icon i{color:#854F0B}
.lk-card-label{font-size:13px;font-weight:500;color:var(--text-primary)}
.lk-card-desc{font-size:11px;color:var(--text-secondary);margin-top:1px}

/* ── Fields ── */
.lk-field{margin-bottom:14px}
.lk-flabel{font-size:11px;color:var(--text-secondary);margin-bottom:4px;display:flex;align-items:center;gap:4px}
.lk-flabel i{font-size:13px}
.lk-finput{width:100%;background:var(--bg-elevated);border:0.5px solid var(--border);border-radius:9px;padding:9px 11px;font-size:13px;color:var(--text-primary);font-family:inherit;outline:none;transition:border-color .15s,box-shadow .15s;line-height:1.5}
.lk-finput:focus{border-color:#AFA9EC;box-shadow:0 0 0 3px rgba(83,74,183,.08)}
textarea.lk-finput{resize:none;min-height:52px}
.lk-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
@media(max-width:560px){.lk-g3{grid-template-columns:1fr}}

/* ── Budget bar ── */
.lk-budget-bar{display:flex;border:0.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px}
.lk-bc{flex:1;padding:13px 14px;display:flex;flex-direction:column;gap:3px}
.lk-bc+.lk-bc{border-left:0.5px solid var(--border)}
.lk-bc-label{font-size:11px;color:var(--text-secondary);display:flex;align-items:center;gap:5px}
.lk-bc-label i{font-size:13px}
.lk-bc-val{font-size:15px;font-weight:500;font-family:var(--font-mono,monospace)}
.lk-bc-green .lk-bc-val{color:#0F6E56}
.lk-bc-neutral .lk-bc-val{color:var(--text-primary)}
.lk-bc-sisa .lk-bc-val{color:#0F6E56}

/* ── Table ── */
.lk-tbl-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.lk-tbl-title{font-size:12px;font-weight:500;color:var(--text-secondary)}
.lk-tbl-wrap{
    overflow-x:auto;
    margin-bottom:12px;
    border:0.5px solid var(--border);
    border-radius:10px;

    -webkit-overflow-scrolling:touch;
    scrollbar-width:thin;
}

.lk-table{
    min-width:600px; /* lebar tetap */
    width:600px;
    border-collapse:collapse;
    font-size:12px;
    table-layout:fixed;
}

.lk-td-no{
    width:20px;
    min-width:20px;
    text-align:center;
    padding:6px 4px;
}

.lk-td-item{
    width:150px;
    min-width:150px;
    padding:4px 8px;
}

.lk-td-nom{
    width:80px;
    min-width:80px;
    padding:4px 8px;
}

.lk-td-del{
    width:20px;
    min-width:20px;
    text-align:center;
    padding:4px;
}
.lk-table thead th{padding:8px 10px;font-size:11px;font-weight:500;color:var(--text-secondary);background:var(--bg-elevated);border-bottom:0.5px solid var(--border);text-align:left}
.lk-table tbody td{border-bottom:0.5px solid var(--border);vertical-align:middle;color:var(--text-primary)}
.lk-table tbody tr:last-child td{border-bottom:none}
.lk-table tbody tr:hover td{background:var(--bg-elevated)}
.lk-table tfoot td{padding:10px;font-size:12px;font-weight:500;background:var(--bg-elevated);color:var(--text-primary);border-top:0.5px solid var(--border)}
.lk-table tfoot td:nth-child(3){text-align:right;padding-right:8px;font-family:var(--font-mono,monospace)}

.lk-td-no{width:38px;text-align:center;padding:6px 4px;font-size:11px;color:var(--text-secondary)}
.lk-td-item{padding:4px 8px}
.lk-td-nom{width:148px;padding:4px 8px}
.lk-td-del{width:46px;text-align:center;padding:4px}

/* Inner table inputs */
.mn-inner{border:none;outline:none;background:transparent;color:var(--text-primary);font-family:inherit;font-size:12px;width:100%}
.mn-inner.mn-r{text-align:right;font-family:var(--font-mono,monospace)}
textarea.mn-inner{resize:none;overflow:hidden;line-height:1.4}

/* ── Delete button ── */
.lk-del-btn{width:26px;height:26px;border:none;border-radius:7px;background:#FCEBEB;color:#A32D2D;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:13px;transition:opacity .15s}
.lk-del-btn:hover{opacity:.7}

/* ── Add row button ── */
.lk-add-btn{display:inline-flex;align-items:center;gap:6px;border:0.5px dashed var(--border);border-radius:8px;background:transparent;padding:7px 14px;font-size:12px;color:var(--text-secondary);cursor:pointer;transition:background .15s,border-color .15s,color .15s;font-family:inherit}
.lk-add-btn:hover{background:#EEEDFE;border-color:#AFA9EC;color:#3C3489}

/* ── Sisa bar ── */
.lk-sisa-bar{display:flex;align-items:center;justify-content:space-between;margin-top:18px;padding:13px 16px;background:var(--bg-elevated);border-radius:10px;border:0.5px solid var(--border)}
.lk-sisa-left{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-secondary)}
.lk-sisa-left i{font-size:16px}
.lk-sisa-val{font-size:17px;font-weight:500;font-family:var(--font-mono,monospace);color:#0F6E56}

/* ── Footer ── */
.lk-footer{display:flex;align-items:center;justify-content:space-between;margin-top:24px;gap:12px}
.lk-btn-cancel{display:inline-flex;align-items:center;gap:6px;border:0.5px solid var(--border);border-radius:10px;background:transparent;padding:10px 18px;font-size:13px;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:background .15s;font-family:inherit}
.lk-btn-cancel:hover{background:var(--bg-elevated)}
.lk-btn-save{display:inline-flex;align-items:center;gap:7px;background:#534AB7;color:#EEEDFE;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:opacity .15s}
.lk-btn-save:hover{opacity:.88}
.lk-btn-save i{font-size:16px}
/* =========================
   Mobile Optimization
   ========================= */
@media (max-width: 768px){

    /* Card lebih lega */
    .lk-card{
        padding:16px;
    }

    /* Budget summary jadi vertikal */
    .lk-budget-bar{
        flex-direction:column;
    }

    .lk-bc + .lk-bc{
        border-left:none;
        border-top:0.5px solid var(--border);
    }

    .lk-bc{
        padding:12px;
    }

    .lk-bc-val{
        font-size:14px;
    }

    

    /* Sisa dana */
    .lk-sisa-bar{
        flex-direction:column;
        align-items:flex-start;
        gap:8px;
    }

    .lk-sisa-val{
        width:100%;
        text-align:right;
        font-size:16px;
    }

    /* Footer tombol */
    .lk-footer{
        flex-direction:column-reverse;
        align-items:stretch;
    }

    .lk-btn-cancel,
    .lk-btn-save{
        width:100%;
        justify-content:center;
    }

    .lk-tbl-wrap{
        position:relative;
    }

    .lk-tbl-wrap::after{
        content:'⇆ Geser untuk melihat';
        display:block;
        text-align:center;
        font-size:11px;
        color:var(--text-secondary);
        padding:6px 0;
        background:var(--bg-surface);
        border-top:0.5px solid var(--border);
    }
}

</style>

@endsection
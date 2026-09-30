@extends('layouts.app')
@section('title','Pengaturan Ringkasan Kas')

@section('content')
@include('pengaturan._ui')

<style>
    .tabs{display:flex;gap:4px;padding:4px;margin-bottom:16px;background:var(--ui-seg);border-radius:12px;overflow-x:auto}
    .tab-btn{flex:1;white-space:nowrap;padding:8px 16px;border-radius:9px;border:0;background:transparent;font-size:.875rem;font-weight:600;color:var(--ui-mute);cursor:pointer;transition:all .15s}
    .tab-btn:hover{color:var(--ui-ink)}
    .tab-btn.active{background:var(--ui-surface);color:var(--ui-accent);box-shadow:0 1px 3px var(--ui-shadow)}

    .kas-card{background:var(--ui-surface);border:1px solid var(--ui-line);border-radius:14px;padding:14px;margin-bottom:12px;box-shadow:0 1px 2px var(--ui-shadow)}
    .kas-card.invalid{border-color:var(--ui-bad);background:var(--ui-bad-soft)}
    .kas-head{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:12px}
    .kas-head .f-nama{flex:1 1 200px;font-weight:600}
    .kas-head .f-kode{flex:1 1 160px;max-width:240px}
    .kas-opts{display:flex;flex-wrap:wrap;align-items:center;gap:10px 22px;margin-top:12px;padding-top:12px;border-top:1px dashed var(--ui-line)}
    .kas-opts .sub{display:inline-flex;align-items:center;gap:8px}
    .kas-opts .sub .inp{width:150px}
    .err-box{font-size:.75rem;color:var(--ui-bad);margin-top:8px}
    .err-box:empty{display:none}

    details.vars{margin-bottom:14px;border:1px solid var(--ui-line);border-radius:12px;background:var(--ui-surface);padding:10px 14px}
    details.vars summary{cursor:pointer;font-weight:600;font-size:.875rem}
    .chip{display:inline-block;margin:3px 2px;padding:2px 9px;border:1px solid var(--ui-line);border-radius:999px;background:var(--ui-surface);color:var(--ui-ink);cursor:pointer;
        font:.72rem ui-monospace,SFMono-Regular,Menlo,monospace;transition:all .15s}
    .chip:hover{background:var(--ui-accent-soft);border-color:var(--ui-accent);color:var(--ui-accent)}

    .par-grid{grid-template-columns:200px 1fr 160px 40px}
    .sim-grid-in{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
    .sim-bidang{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:16px}
    .sim-in{width:100%;padding:7px 10px;border:1px solid var(--ui-line);border-radius:9px;text-align:right;font-variant-numeric:tabular-nums;background:var(--ui-surface);color:var(--ui-ink)}
    .sim-in:focus{border-color:var(--ui-accent);box-shadow:0 0 0 3px var(--ui-accent-soft);outline:none}
    .sim-sec{font-weight:700;margin:16px 0 8px}
    .sim-sum{margin-top:12px;padding:12px 14px;border-radius:12px;background:var(--ui-bg);border:1px solid var(--ui-line);font-size:.85rem}

    @media (max-width:767px){
        .par-grid{grid-template-columns:1fr 1fr;padding:14px}
        .par-grid .c-kode,.par-grid .c-nama{grid-column:1/-1}
        .par-grid .c-del{justify-self:end}
        .sim-grid-in{grid-template-columns:1fr}
        .kas-head .f-kode{max-width:none}
        .kas-opts{flex-direction:column;align-items:flex-start}
        .kas-opts .sub{flex-wrap:wrap}
    }
</style>

<div class="ui-page">

    <div class="ui-bar">
        <div>
            @include('pengaturan._back')
            <h1>Pengaturan Ringkasan Kas</h1>
            <p>Mengatur item ringkasan kas, rumus pembagian COS dan parameter. Simulator memakai isian di layar (belum disimpan).</p>
        </div>
        <div class="ui-act float">
            <span id="statusValidasi" class="ui-status"></span>
            <button id="btnSimpan" type="button" onclick="simpan()" class="btn btn-primary">
                Simpan pengaturan <kbd>Ctrl+S</kbd>
            </button>
        </div>
    </div>

    <div id="errGlobal" class="ui-alert hidden"></div>

    <div class="tabs" role="tablist">
        <button type="button" class="tab-btn active" data-tab="kas">Item kas</button>
        <button type="button" class="tab-btn" data-tab="param">Parameter</button>
        <button type="button" class="tab-btn" data-tab="sim">Simulator</button>
    </div>

    <fieldset {{ $bolehUbah ? '' : 'disabled' }} style="border:none;padding:0;margin:0;min-width:0">

        {{-- TAB ITEM KAS --}}
        <div id="tab-kas">
            <details class="vars">
                <summary>Variabel yang bisa dipakai di rumus (klik untuk menyisipkan)</summary>
                <div id="chipWrap" style="margin-top:10px;max-height:200px;overflow:auto"></div>
                <p class="ui-note" style="margin-top:8px">
                    Operator: + - * / ( ) &nbsp;|&nbsp; Fungsi: floor, ceil, round, abs, min, max.
                    Sumbangan otomatis menambah debit kas yang <em>menerima sumbangan</em>, tidak perlu ditulis di rumus.
                </p>
            </details>

            <div id="listKas"></div>
            <button type="button" onclick="tambahKas()" class="btn btn-ghost">+ Tambah item kas</button>
        </div>

        {{-- TAB PARAMETER --}}
        <div id="tab-param" class="hidden">
            <div class="card">
                <div class="grid-head par-grid">
                    <div>Kode</div><div>Nama</div><div style="text-align:right">Nilai</div><div></div>
                </div>
                <div id="tbodyParam"></div>
            </div>
            <p class="ui-note" style="margin-top:8px">Pecahan ditulis dengan titik, mis. 0.2 untuk 20%.</p>
            <button type="button" onclick="tambahParam()" class="btn btn-ghost" style="margin-top:8px">+ Tambah parameter</button>
        </div>

    </fieldset>

    {{-- TAB SIMULATOR --}}
    <div id="tab-sim" class="hidden">
        <div class="sim-grid-in">
            <label><div class="lbl2">COS</div><input class="sim-in" data-g="umum" data-k="cos"></label>
            <label><div class="lbl2">Total insentif</div><input class="sim-in" data-g="umum" data-k="insentif"></label>
            <label><div class="lbl2">Total dana lain-lain</div><input class="sim-in" data-g="umum" data-k="dana_lain"></label>
        </div>

        <div id="simKas"></div>

        <div class="sim-sec">Total realisasi per bidang</div>
        <div id="simBidang" class="sim-bidang"></div>

        <button type="button" onclick="hitungSimulasi()" class="btn btn-ok">Hitung</button>
        <div id="simHasil" style="margin-top:16px"></div>
    </div>
</div>

<script>
const KAS_AWAL     = @json($kas);
const PARAM_AWAL   = @json($params);
const BIDANG_TETAP = @json($bidang->pluck('nama'));
const BOLEH_UBAH   = @json($bolehUbah);

const URL_VALIDASI = "{{ route('kas.validasi') }}";
const URL_SIMULASI = "{{ route('kas.simulasi') }}";
const URL_SIMPAN   = "{{ route('kas.sync') }}";
const H = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': "{{ csrf_token() }}"
};

const opt = (list, sel) => list.map(([v, l]) => `<option value="${v}" ${v === sel ? 'selected' : ''}>${l}</option>`).join('');
const M_SALDO = [['carry_over', 'Bawa dari total bulan lalu'], ['nol', 'Selalu 0']];
const M_ISI   = [['manual', 'Diinput manual'], ['rumus', 'Pakai rumus']];

/* ---------------- tab ---------------- */
document.querySelectorAll('.tab-btn').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(x => x.classList.toggle('active', x === b));
    ['kas', 'param', 'sim'].forEach(t => document.getElementById('tab-' + t).classList.toggle('hidden', t !== b.dataset.tab));
    if (b.dataset.tab === 'sim') bangunSimulator();
}));

/* ---------------- kartu item kas ---------------- */
function kartuKas(k = {}, baru = false) {
    const c = document.createElement('div');
    c.className = 'kas-card';
    c.dataset.row = '1';
    c.innerHTML = `
      <div class="kas-head">
        <span class="mv">
          <button type="button" class="btn-icon" onclick="geser(this,-1)" aria-label="Naikkan">▲</button>
          <button type="button" class="btn-icon" onclick="geser(this,1)" aria-label="Turunkan">▼</button>
        </span>
        <input type="text" class="inp f-nama" placeholder="Nama item kas">
        <input type="text" class="inp mono f-kode" placeholder="kode_unik" ${baru ? '' : 'readonly'}>
        <label class="sw"><input type="checkbox" class="f-aktif"><i></i><span>Aktif</span></label>
        ${baru ? '<button type="button" class="btn-icon btn-del" aria-label="Hapus" onclick="this.closest(\'.kas-card\').remove();jadwalValidasi();segarkanChip()">✕</button>' : ''}
      </div>
      <div class="g3">
        <div><div class="lbl2">Saldo awal</div>
          <select class="sel f-saldo">${opt(M_SALDO, k.saldo_awal_mode || 'carry_over')}</select></div>
        <div><div class="lbl2">Debit</div>
          <select class="sel f-debit-mode">${opt(M_ISI, k.debit_mode || 'manual')}</select>
          <input type="text" class="inp mono rumus f-debit-rumus" style="margin-top:6px" placeholder="rumus debit"></div>
        <div><div class="lbl2">Kredit</div>
          <select class="sel f-kredit-mode">${opt(M_ISI, k.kredit_mode || 'manual')}</select>
          <input type="text" class="inp mono rumus f-kredit-rumus" style="margin-top:6px" placeholder="rumus kredit"></div>
      </div>
      <div class="kas-opts">
        <label class="chk"><input type="checkbox" class="f-sumbangan"> Menerima sumbangan</label>
        <label class="chk"><input type="checkbox" class="f-rincian"> Punya rincian pengeluaran</label>
        <label class="sub">Nama bidang di pengajuan <input type="text" class="inp f-bidang"></label>
        <label class="chk"><input type="checkbox" class="f-pajak"> Punya pajak</label>
        <span class="sub pajak-opsi">
          <label class="sub">Label <input type="text" class="inp f-label-pajak" style="width:120px"></label>
          <label class="sub">Tujuan <input type="text" class="inp f-tujuan-pajak" style="width:110px"></label>
        </span>
      </div>
      <div class="err-box"></div>`;

    q(c, '.f-nama').value = k.nama ?? '';
    q(c, '.f-kode').value = k.kode ?? '';
    q(c, '.f-aktif').checked = k.aktif !== false && k.aktif !== 0;
    q(c, '.f-debit-rumus').value = k.debit_rumus ?? '';
    q(c, '.f-kredit-rumus').value = k.kredit_rumus ?? '';
    q(c, '.f-sumbangan').checked = !!k.menerima_sumbangan;
    q(c, '.f-rincian').checked = !!k.tampil_rincian;
    q(c, '.f-bidang').value = k.nama_bidang ?? '';
    q(c, '.f-pajak').checked = !!k.punya_pajak;
    q(c, '.f-label-pajak').value = k.label_pajak ?? '';
    q(c, '.f-tujuan-pajak').value = k.tujuan_pajak ?? '';

    if (baru) {
        q(c, '.f-nama').addEventListener('input', () => {
            const kode = q(c, '.f-kode');
            if (!kode.dataset.manual) kode.value = slug(q(c, '.f-nama').value);
        });
        q(c, '.f-kode').addEventListener('input', e => e.target.dataset.manual = '1');
    }
    sinkronKartu(c);
    return c;
}

function sinkronKartu(c) {
    q(c, '.f-debit-rumus').style.display  = q(c, '.f-debit-mode').value  === 'rumus' ? '' : 'none';
    q(c, '.f-kredit-rumus').style.display = q(c, '.f-kredit-mode').value === 'rumus' ? '' : 'none';
    q(c, '.pajak-opsi').style.display     = q(c, '.f-pajak').checked ? '' : 'none';
    if (q(c, '.f-pajak').checked) q(c, '.f-rincian').checked = true;
}

function tambahKas(k) {
    const c = kartuKas(k || { aktif: true }, true);
    document.getElementById('listKas').appendChild(c);
    q(c, '.f-nama').focus();
    jadwalValidasi();
}

function kumpulKas() {
    return [...document.querySelectorAll('.kas-card')].map(c => ({
        kode: q(c, '.f-kode').value, nama: q(c, '.f-nama').value, aktif: q(c, '.f-aktif').checked,
        saldo_awal_mode: q(c, '.f-saldo').value,
        debit_mode: q(c, '.f-debit-mode').value, debit_rumus: q(c, '.f-debit-rumus').value,
        kredit_mode: q(c, '.f-kredit-mode').value, kredit_rumus: q(c, '.f-kredit-rumus').value,
        menerima_sumbangan: q(c, '.f-sumbangan').checked, tampil_rincian: q(c, '.f-rincian').checked,
        nama_bidang: q(c, '.f-bidang').value,
        punya_pajak: q(c, '.f-pajak').checked, label_pajak: q(c, '.f-label-pajak').value, tujuan_pajak: q(c, '.f-tujuan-pajak').value,
    }));
}

/* ---------------- parameter ---------------- */
function tambahParam(p = {}, baru = true) {
    const r = document.createElement('div');
    r.className = 'row par-grid prow';
    r.innerHTML = `
        <div class="c-kode"><div class="lbl">Kode</div><input type="text" class="inp mono p-kode"></div>
        <div class="c-nama"><div class="lbl">Nama</div><input type="text" class="inp p-nama"></div>
        <div class="c-nilai"><div class="lbl">Nilai</div><input type="text" class="inp num p-nilai"></div>
        <div class="c-del"><button type="button" class="btn-icon btn-del" aria-label="Hapus"
            onclick="this.closest('.prow').remove();jadwalValidasi();segarkanChip()">✕</button></div>
        <div class="err-txt"></div>`;
    q(r, '.p-kode').value = p.kode ?? '';
    q(r, '.p-nama').value = p.nama ?? '';
    q(r, '.p-nilai').value = p.nilai ?? '';
    document.getElementById('tbodyParam').appendChild(r);
    if (baru) { q(r, '.p-kode').focus(); jadwalValidasi(); }
}

function kumpulParam() {
    return [...document.querySelectorAll('#tbodyParam .prow')].map(r => ({
        kode: q(r, '.p-kode').value,
        nama: q(r, '.p-nama').value,
        nilai: q(r, '.p-nilai').value.replace(',', '.'),
    }));
}

const payload = () => ({ kas: kumpulKas(), params: kumpulParam() });

/* ---------------- chip variabel ---------------- */
let lastRumus = null;
document.addEventListener('focusin', e => { if (e.target.matches('.rumus')) lastRumus = e.target; });

function segarkanChip() {
    const kas = kumpulKas().filter(k => k.aktif && k.kode);
    const params = kumpulParam().filter(p => p.kode);
    const bidang = new Set(BIDANG_TETAP.map(norm));
    kumpulKas().forEach(k => { if (k.nama_bidang) bidang.add(norm(k.nama_bidang)); });

    const grup = [
        ['Umum', ['COS', 'INSENTIF', 'DANA_LAIN']],
        ['Parameter', params.map(p => p.kode.toUpperCase())],
        ['Saldo awal', kas.map(k => 'SALDO.' + norm(k.kode))],
        ['Debit', kas.map(k => 'DEBIT.' + norm(k.kode))],
        ['Kredit', kas.map(k => 'KREDIT.' + norm(k.kode))],
        ['Kredit per bidang', kas.map(k => 'KREDIT_BIDANG.' + norm(k.kode))],
        ['Pajak', kas.filter(k => k.punya_pajak).map(k => 'PAJAK.' + norm(k.kode))],
        ['Sumbangan', kas.filter(k => k.menerima_sumbangan).map(k => 'SUMBANGAN.' + norm(k.kode))],
        ['Total per bidang', [...bidang].map(b => 'BIDANG.' + b)],
    ];

    document.getElementById('chipWrap').innerHTML = grup.filter(g => g[1].length).map(([judul, arr]) =>
        `<div style="margin-bottom:6px"><span style="font-weight:600;font-size:.78rem">${judul}:</span> ` +
        arr.map(v => `<span class="chip" onclick="sisipChip('${v}')">${v}</span>`).join('') + '</div>'
    ).join('');
}

function sisipChip(v) {
    if (!lastRumus) { showToast('Klik dulu kolom rumus yang ingin diisi.', 'info'); return; }
    const el = lastRumus, s = el.selectionStart ?? el.value.length, e = el.selectionEnd ?? s;
    el.value = el.value.slice(0, s) + v + el.value.slice(e);
    el.focus();
    el.selectionStart = el.selectionEnd = s + v.length;
    jadwalValidasi();
}

/* ---------------- validasi langsung ---------------- */
let tv = null;
function jadwalValidasi() { clearTimeout(tv); tv = setTimeout(validasi, 500); }

function tampilError(errors) {
    document.querySelectorAll('.kas-card').forEach(c => { c.classList.remove('invalid'); q(c, '.err-box').innerHTML = ''; });
    document.querySelectorAll('#tbodyParam .prow').forEach(r => { r.classList.remove('invalid'); q(r, '.err-txt').textContent = ''; });
    const g = document.getElementById('errGlobal');
    g.classList.add('hidden'); g.innerHTML = '';

    const label = { kode: 'Kode', nama: 'Nama', debit_rumus: 'Rumus debit', kredit_rumus: 'Rumus kredit', nilai: 'Nilai' };
    const kartu = [...document.querySelectorAll('.kas-card')];
    const baris = [...document.querySelectorAll('#tbodyParam .prow')];

    errors.forEach(er => {
        const teks = (label[er.field] ? label[er.field] + ': ' : '') + er.message;
        if (er.scope === 'kas' && kartu[er.index]) {
            kartu[er.index].classList.add('invalid');
            q(kartu[er.index], '.err-box').insertAdjacentHTML('beforeend', `<div>• ${esc(teks)}</div>`);
        } else if (er.scope === 'param' && baris[er.index]) {
            baris[er.index].classList.add('invalid');
            const c = q(baris[er.index], '.err-txt');
            c.textContent += (c.textContent ? ' | ' : '') + teks;
        } else {
            g.classList.remove('hidden');
            g.insertAdjacentHTML('beforeend', `<div>• ${esc(teks)}</div>`);
        }
    });

    const st = document.getElementById('statusValidasi');
    st.textContent = errors.length ? `✗ ${errors.length} masalah` : '✓ Semua rumus valid';
    st.style.color = errors.length ? 'var(--ui-bad)' : 'var(--ui-ok)';
}

async function validasi() {
    try {
        const res = await fetch(URL_VALIDASI, { method: 'POST', headers: H, body: JSON.stringify(payload()) });
        const j = await res.json();
        tampilError(j.errors || []);
    } catch (e) { console.error(e); }
}

function sinkronSemua() { document.querySelectorAll('.kas-card').forEach(sinkronKartu); }
['input', 'change'].forEach(ev => document.addEventListener(ev, e => {
    if (e.target.closest('#tab-kas, #tab-param')) { sinkronSemua(); jadwalValidasi(); }
}));

/* ---------------- simpan ---------------- */
async function simpan(konfirmasi = false) {
    const btn = document.getElementById('btnSimpan');

    if (!konfirmasi) {
        const ok = await showConfirm({
            title: 'Simpan pengaturan?',
            message: 'Perubahan akan disimpan dan laporan bulan baru akan memakai konfigurasi ini.',
            okText: 'Ya, simpan',
        });
        if (!ok) return;
    }

    btn.disabled = true;
    try {
        const res = await fetch(URL_SIMPAN, { method: 'POST', headers: H, body: JSON.stringify({ ...payload(), konfirmasi }) });
        const j = await res.json();

        if (res.status === 409 && j.needs_confirm) {
            btn.disabled = false;
            const ok = await showConfirm({
                title: 'Simpan dengan peringatan?',
                message: (j.warnings || []).join('\n\n'),
                okText: 'Tetap simpan',
                danger: true,
            });
            if (ok) return simpan(true);
            return;
        }
        if (res.status === 422) {
            tampilError(j.errors || []);
            showToast('Masih ada isian atau rumus yang tidak valid.', 'error', 4000);
            return;
        }
        if (!res.ok || !j.success) throw new Error(j.message || 'Gagal menyimpan');

        showToast(j.message || 'Pengaturan berhasil disimpan.');
        setTimeout(() => location.reload(), 900);
    } catch (err) {
        showToast(err.message, 'error', 4000);
    } finally {
        btn.disabled = false;
    }
}

/* ---------------- simulator ---------------- */
const SIM = {};   // nilai isian, tetap ada saat form dibangun ulang
const simKey = (g, k) => g + '|' + k;

function inputSim(g, k) {
    return `<input class="sim-in" data-g="${g}" data-k="${esc(k)}" value="${formatID(SIM[simKey(g, k)] || 0)}">`;
}

function bangunSimulator() {
    const kas = kumpulKas().filter(k => k.aktif && k.kode);
    const bidang = new Set(BIDANG_TETAP);
    kumpulKas().forEach(k => { if (k.nama_bidang) bidang.add(norm(k.nama_bidang)); });

    const mute = t => `<span style="color:var(--ui-faint)">${t}</span>`;
    let html = '<div class="tbl-wrap"><table class="sim-tbl"><thead><tr>' +
        '<th>Kas</th><th>Saldo awal</th><th>Debit manual</th><th>Kredit manual</th><th>Pajak</th><th>Sumbangan</th></tr></thead><tbody>';
    kas.forEach(k => {
        const kode = norm(k.kode);
        html += `<tr><td>${esc(k.nama)}</td>
            <td>${k.saldo_awal_mode === 'carry_over' ? inputSim('saldo_awal', kode) : mute('0')}</td>
            <td>${k.debit_mode === 'manual' ? inputSim('debit_manual', kode) : mute('rumus')}</td>
            <td>${k.kredit_mode === 'manual' ? inputSim('kredit_manual', kode) : mute('rumus')}</td>
            <td>${k.punya_pajak ? inputSim('pajak', kode) : ''}</td>
            <td>${k.menerima_sumbangan ? inputSim('sumbangan', kode) : ''}</td></tr>`;
    });
    document.getElementById('simKas').innerHTML = html + '</tbody></table></div>';

    document.getElementById('simBidang').innerHTML = [...bidang].map(b =>
        `<label><div class="lbl2">${esc(b)}</div>${inputSim('bidang', b)}</label>`).join('');

    document.querySelectorAll('#tab-sim .sim-in[data-g="umum"]').forEach(el => el.value = formatID(SIM[simKey('umum', el.dataset.k)] || 0));
}

document.addEventListener('input', e => {
    if (!e.target.matches('.sim-in')) return;
    const v = parseID(e.target.value);
    SIM[simKey(e.target.dataset.g, e.target.dataset.k)] = v;
    e.target.value = formatID(v);
});

async function hitungSimulasi() {
    const input = { cos: 0, insentif: 0, dana_lain: 0, saldo_awal: {}, debit_manual: {}, kredit_manual: {}, pajak: {}, sumbangan: {}, bidang: {} };
    document.querySelectorAll('#tab-sim .sim-in').forEach(el => {
        const v = parseID(el.value), g = el.dataset.g, k = el.dataset.k;
        g === 'umum' ? input[k] = v : input[g][k] = v;
    });

    const out = document.getElementById('simHasil');
    out.innerHTML = '<span class="ui-note">Menghitung…</span>';

    try {
        const res = await fetch(URL_SIMULASI, { method: 'POST', headers: H, body: JSON.stringify({ ...payload(), input }) });
        const j = await res.json();

        if (!res.ok || !j.success) {
            const msg = (j.errors || []).map(e => '• ' + esc(e.message)).join('<br>') || esc(j.message || 'Gagal menghitung');
            out.innerHTML = `<div class="ui-alert">${msg}</div>`;
            return;
        }

        const { rows, grand } = j.hasil;
        out.innerHTML = `
          <div class="tbl-wrap"><table class="sim-tbl">
            <thead><tr><th>Kas</th><th class="r">Saldo awal</th><th class="r">Debit</th><th class="r">Kredit</th><th class="r">Total</th></tr></thead>
            <tbody>
              ${rows.map(r => `<tr><td>${esc(r.nama)}</td>
                <td class="r">${formatID(r.saldo_awal)}</td><td class="r">${formatID(r.debit)}</td>
                <td class="r">${formatID(r.kredit)}</td><td class="r" style="font-weight:600">${formatID(r.total)}</td></tr>`).join('')}
              <tr style="font-weight:700;background:var(--ui-ok-soft)"><td>TOTAL</td>
                <td class="r">${formatID(grand.saldo_awal)}</td><td class="r">${formatID(grand.debit)}</td>
                <td class="r">${formatID(grand.kredit)}</td><td class="r">${formatID(grand.total)}</td></tr>
            </tbody>
          </table></div>
          <div class="sim-sum">
            Total debit dibanding COS: <strong>${formatID(grand.debit - j.cos)}</strong>
            <span class="ui-note">(dengan rumus sekarang, jika sumbangan dan debit manual bunga = 0, selisih ini seharusnya 0 atau beberapa rupiah pembulatan)</span>
          </div>`;
    } catch (err) {
        out.innerHTML = `<div class="ui-alert">${esc(err.message)}</div>`;
    }
}

/* ---------------- init ---------------- */
bindSave(() => simpan(), BOLEH_UBAH);
KAS_AWAL.forEach(k => document.getElementById('listKas').appendChild(kartuKas(k, false)));
PARAM_AWAL.forEach(p => tambahParam(p, false));
segarkanChip();
document.addEventListener('input', e => { if (e.target.closest('#tab-kas, #tab-param')) segarkanChip(); });
validasi();
</script>
@endsection
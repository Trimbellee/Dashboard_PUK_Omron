@extends('layouts.app')
@section('title','Pengaturan Ringkasan Kas')

@section('content')
<div class="max-w-6xl mx-auto p-4 text-black">

    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-lg font-bold">Pengaturan Ringkasan Kas</h1>
            <p class="text-sm text-gray-500">
                Mengatur item kas, rumus, dan parameter. Simulator memakai isian di layar (belum disimpan).
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span id="statusValidasi" class="text-sm text-gray-500"></span>
            @if($bolehUbah)
            <button id="btnSimpan" type="button" onclick="simpan()"
                class="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-60">Simpan Pengaturan</button>
            @endif
        </div>
    </div>

    <div id="errGlobal" class="hidden mb-3 p-3 rounded border border-red-300 bg-red-50 text-red-700 text-sm"></div>

    <div class="flex gap-1 mb-3 border-b">
        <button type="button" class="tab-btn px-4 py-2 font-semibold border-b-2 border-blue-600" data-tab="kas">Item Kas</button>
        <button type="button" class="tab-btn px-4 py-2" data-tab="param">Parameter</button>
        <button type="button" class="tab-btn px-4 py-2" data-tab="sim">Simulator</button>
    </div>

    <fieldset {{ $bolehUbah ? '' : 'disabled' }} style="border:none;padding:0;margin:0;">

    {{-- TAB ITEM KAS --}}
    <div id="tab-kas">
        <details class="mb-3 border rounded p-2 bg-gray-50">
            <summary class="cursor-pointer text-sm font-semibold">Variabel yang bisa dipakai di rumus (klik untuk menyisipkan)</summary>
            <div id="chipWrap" class="mt-2 text-xs max-h-48 overflow-auto"></div>
            <p class="text-xs text-gray-500 mt-2">
                Operator: + - * / ( ) &nbsp;|&nbsp; Fungsi: floor, ceil, round, abs, min, max.
                Sumbangan otomatis menambah debit kas yang <em>menerima sumbangan</em>, tidak perlu ditulis di rumus.
            </p>
        </details>

        <div id="listKas"></div>
        <button type="button" onclick="tambahKas()" class="px-3 py-1 border rounded">+ Tambah Item Kas</button>
    </div>

    {{-- TAB PARAMETER --}}
    <div id="tab-param" class="hidden">
        <table class="w-full border text-sm" id="tblParam">
            <thead class="bg-gray-200">
                <tr>
                    <th style="width:22%">Kode</th><th>Nama</th>
                    <th style="width:18%">Nilai</th><th style="width:7%">Hapus</th>
                </tr>
            </thead>
            <tbody id="tbodyParam"></tbody>
        </table>
        <p class="text-xs text-gray-500 mt-1">Pecahan ditulis dengan titik, mis. 0.2 untuk 20%.</p>
        <button type="button" onclick="tambahParam()" class="mt-2 px-3 py-1 border rounded">+ Tambah Parameter</button>
    </div>

    </fieldset>

    {{-- TAB SIMULATOR --}}
    <div id="tab-sim" class="hidden">
        <div class="grid grid-cols-3 gap-3 mb-3 text-sm">
            <label>COS <input class="sim-in border px-2 py-1 w-full text-right" data-g="umum" data-k="cos"></label>
            <label>Total insentif <input class="sim-in border px-2 py-1 w-full text-right" data-g="umum" data-k="insentif"></label>
            <label>Total dana lain-lain <input class="sim-in border px-2 py-1 w-full text-right" data-g="umum" data-k="dana_lain"></label>
        </div>

        <div id="simKas" class="mb-3"></div>

        <div class="font-semibold text-sm mb-1">Total realisasi per bidang</div>
        <div id="simBidang" class="grid grid-cols-3 gap-2 mb-3 text-sm"></div>

        <button type="button" onclick="hitungSimulasi()" class="px-4 py-2 bg-green-600 text-white rounded">Hitung</button>
        <div id="simHasil" class="mt-4"></div>
    </div>
</div>

<style>
    .kas-card.invalid { border-color:#ef4444; }
    .chip { display:inline-block; margin:2px; padding:1px 6px; border:1px solid #d1d5db; border-radius:9999px;
            background:#fff; cursor:pointer; font-family:monospace; }
    .chip:hover { background:#dbeafe; }
    .sim-tbl th, .sim-tbl td { border:1px solid #d1d5db; padding:4px; }
    #tblParam th, #tblParam td { border:1px solid #d1d5db; padding:4px; }
    .kas-card input[type=text], .kas-card select, #tblParam input { border:1px solid #d1d5db; padding:2px 6px; border-radius:4px; }
    fieldset:disabled { opacity:.7; }
</style>

<script>
const KAS_AWAL     = @json($kas);
const PARAM_AWAL   = @json($params);
const BIDANG_TETAP = @json($bidangTetap);
const BOLEH_UBAH   = @json($bolehUbah);

const URL_VALIDASI = "{{ route('kas.validasi') }}";
const URL_SIMULASI = "{{ route('kas.simulasi') }}";
const URL_SIMPAN   = "{{ route('kas.sync') }}";
const H = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': "{{ csrf_token() }}"
};

const q = (el, s) => el.querySelector(s);
const parseID  = v => Number(String(v ?? '').replace(/[^\d\-]/g, '')) || 0;
const formatID = v => Math.floor(v || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const esc  = s => String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
const norm = s => String(s).toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '');
const slug = s => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
const opt  = (list, sel) => list.map(([v, l]) => `<option value="${v}" ${v === sel ? 'selected' : ''}>${l}</option>`).join('');

const M_SALDO = [['carry_over', 'Bawa dari total bulan lalu'], ['nol', 'Selalu 0']];
const M_ISI   = [['manual', 'Diinput manual'], ['rumus', 'Pakai rumus']];

/* ---------------- tab ---------------- */
document.querySelectorAll('.tab-btn').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(x => x.classList.remove('font-semibold', 'border-b-2', 'border-blue-600'));
    b.classList.add('font-semibold', 'border-b-2', 'border-blue-600');
    ['kas', 'param', 'sim'].forEach(t => document.getElementById('tab-' + t).classList.toggle('hidden', t !== b.dataset.tab));
    if (b.dataset.tab === 'sim') bangunSimulator();
}));

/* ---------------- kartu item kas ---------------- */
function kartuKas(k = {}, baru = false) {
    const c = document.createElement('div');
    c.className = 'kas-card border rounded p-3 mb-3 bg-white';
    c.innerHTML = `
      <div class="flex items-center gap-2 mb-2">
        <span class="whitespace-nowrap">
          <button type="button" onclick="geser(this,-1)">▲</button>
          <button type="button" onclick="geser(this,1)">▼</button>
        </span>
        <input type="text" class="f-nama flex-1 font-semibold" placeholder="Nama item kas">
        <input type="text" class="f-kode w-52 text-xs ${baru ? '' : 'bg-gray-100'}" placeholder="kode_unik" ${baru ? '' : 'readonly'}>
        <label class="text-sm whitespace-nowrap"><input type="checkbox" class="f-aktif"> Aktif</label>
        ${baru ? '<button type="button" onclick="this.closest(\'.kas-card\').remove();jadwalValidasi()" class="w-6 h-6 bg-red-600 text-white rounded">✕</button>' : ''}
      </div>
      <div class="grid grid-cols-3 gap-3 text-sm">
        <div><div class="text-xs text-gray-500">Saldo awal</div>
          <select class="f-saldo w-full">${opt(M_SALDO, k.saldo_awal_mode || 'carry_over')}</select></div>
        <div><div class="text-xs text-gray-500">Debit</div>
          <select class="f-debit-mode w-full">${opt(M_ISI, k.debit_mode || 'manual')}</select>
          <input type="text" class="f-debit-rumus rumus w-full mt-1 font-mono text-xs" placeholder="rumus debit"></div>
        <div><div class="text-xs text-gray-500">Kredit</div>
          <select class="f-kredit-mode w-full">${opt(M_ISI, k.kredit_mode || 'manual')}</select>
          <input type="text" class="f-kredit-rumus rumus w-full mt-1 font-mono text-xs" placeholder="rumus kredit"></div>
      </div>
      <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm mt-2">
        <label><input type="checkbox" class="f-sumbangan"> Menerima sumbangan</label>
        <label><input type="checkbox" class="f-rincian"> Punya rincian pengeluaran</label>
        <label>Nama bidang di pengajuan <input type="text" class="f-bidang w-40"></label>
        <label><input type="checkbox" class="f-pajak"> Punya pajak</label>
        <span class="pajak-opsi">Label <input type="text" class="f-label-pajak w-28"> Tujuan <input type="text" class="f-tujuan-pajak w-24"></span>
      </div>
      <div class="err-box text-xs text-red-600 mt-2"></div>`;

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

function tambahKas(k) { document.getElementById('listKas').appendChild(kartuKas(k || { aktif: true }, true)); jadwalValidasi(); }

function geser(btn, arah) {
    const c = btn.closest('.kas-card');
    const t = arah < 0 ? c.previousElementSibling : c.nextElementSibling;
    if (!t) return;
    arah < 0 ? t.before(c) : t.after(c);
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
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="p-kode w-full font-mono"></td>
        <td><input type="text" class="p-nama w-full"></td>
        <td><input type="text" class="p-nilai w-full text-right"></td>
        <td class="text-center"><button type="button" onclick="this.closest('tr').remove();jadwalValidasi()"
             class="w-6 h-6 bg-red-600 text-white rounded">✕</button></td>`;
    q(tr, '.p-kode').value = p.kode ?? '';
    q(tr, '.p-nama').value = p.nama ?? '';
    q(tr, '.p-nilai').value = p.nilai ?? '';
    document.getElementById('tbodyParam').appendChild(tr);
    if (baru) jadwalValidasi();
}

function kumpulParam() {
    return [...document.querySelectorAll('#tbodyParam tr')].map(tr => ({
        kode: q(tr, '.p-kode').value,
        nama: q(tr, '.p-nama').value,
        nilai: q(tr, '.p-nilai').value.replace(',', '.'),
    }));
}

const payload = () => ({ kas: kumpulKas(), params: kumpulParam() });

/* ---------------- chip variabel ---------------- */
let lastRumus = null;
document.addEventListener('focusin', e => { if (e.target.matches('.rumus')) lastRumus = e.target; });

function segarkanChip() {
    const kas = kumpulKas().filter(k => k.aktif && k.kode);
    const params = kumpulParam().filter(p => p.kode);
    const bidang = new Set(BIDANG_TETAP);
    kumpulKas().forEach(k => { if (k.nama_bidang) bidang.add(norm(k.nama_bidang)); });

    const grup = [
        ['Umum', ['COS', 'INSENTIF', 'DANA_LAIN']],
        ['Parameter', params.map(p => p.kode.toUpperCase())],
        ['Saldo awal', kas.map(k => 'SALDO.' + norm(k.kode))],
        ['Debit', kas.map(k => 'DEBIT.' + norm(k.kode))],
        ['Kredit', kas.map(k => 'KREDIT.' + norm(k.kode))],
        ['Pajak', kas.filter(k => k.punya_pajak).map(k => 'PAJAK.' + norm(k.kode))],
        ['Sumbangan', kas.filter(k => k.menerima_sumbangan).map(k => 'SUMBANGAN.' + norm(k.kode))],
        ['Total per bidang', [...bidang].map(b => 'BIDANG.' + b)],
    ];

    document.getElementById('chipWrap').innerHTML = grup.filter(g => g[1].length).map(([judul, arr]) =>
        `<div class="mb-1"><span class="font-semibold">${judul}:</span> ` +
        arr.map(v => `<span class="chip" onclick="sisipChip('${v}')">${v}</span>`).join('') + '</div>'
    ).join('');
}

function sisipChip(v) {
    if (!lastRumus) { alert('Klik dulu kolom rumus yang ingin diisi.'); return; }
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
    document.querySelectorAll('#tbodyParam tr').forEach(tr => tr.style.background = '');
    document.querySelectorAll('#tbodyParam .err-p').forEach(x => x.remove());
    const g = document.getElementById('errGlobal');
    g.classList.add('hidden'); g.innerHTML = '';

    const label = { kode: 'Kode', nama: 'Nama', debit_rumus: 'Rumus debit', kredit_rumus: 'Rumus kredit', nilai: 'Nilai' };
    const kartu = [...document.querySelectorAll('.kas-card')];
    const baris = [...document.querySelectorAll('#tbodyParam tr')];

    errors.forEach(er => {
        const teks = (label[er.field] ? label[er.field] + ': ' : '') + er.message;
        if (er.scope === 'kas' && kartu[er.index]) {
            kartu[er.index].classList.add('invalid');
            q(kartu[er.index], '.err-box').insertAdjacentHTML('beforeend', `<div>• ${esc(teks)}</div>`);
        } else if (er.scope === 'param' && baris[er.index]) {
            baris[er.index].style.background = '#fef2f2';
            baris[er.index].lastElementChild.insertAdjacentHTML('beforeend', `<span class="err-p text-xs text-red-600" title="${esc(teks)}">⚠</span>`);
        } else {
            g.classList.remove('hidden');
            g.insertAdjacentHTML('beforeend', `<div>• ${esc(teks)}</div>`);
        }
    });

    const st = document.getElementById('statusValidasi');
    st.textContent = errors.length ? `✗ ${errors.length} masalah` : '✓ Semua rumus valid';
    st.className = 'text-sm ' + (errors.length ? 'text-red-600' : 'text-green-600');
}

async function validasi() {
    try {
        const res = await fetch(URL_VALIDASI, { method: 'POST', headers: H, body: JSON.stringify(payload()) });
        const j = await res.json();
        tampilError(j.errors || []);
    } catch (e) { console.error(e); }
}

document.addEventListener('input', e => { if (e.target.closest('#tab-kas, #tab-param')) { sinkronSemua(); jadwalValidasi(); } });
document.addEventListener('change', e => { if (e.target.closest('#tab-kas, #tab-param')) { sinkronSemua(); jadwalValidasi(); } });
function sinkronSemua() { document.querySelectorAll('.kas-card').forEach(sinkronKartu); }

/* ---------------- simpan ---------------- */
async function simpan(konfirmasi = false) {
    const btn = document.getElementById('btnSimpan');
    btn.disabled = true;
    try {
        const res = await fetch(URL_SIMPAN, { method: 'POST', headers: H, body: JSON.stringify({ ...payload(), konfirmasi }) });
        const j = await res.json();

        if (res.status === 409 && j.needs_confirm) {
            btn.disabled = false;
            if (confirm(j.warnings.join('\n\n') + '\n\nTetap simpan?')) return simpan(true);
            return;
        }
        if (res.status === 422) { tampilError(j.errors || []); alert('Masih ada isian atau rumus yang tidak valid. Perbaiki yang bertanda merah.'); return; }
        if (!res.ok || !j.success) throw new Error(j.message || 'Gagal menyimpan');

        alert(j.message);
        location.reload();
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled = false;
    }
}

/* ---------------- simulator ---------------- */
const SIM = {};   // nilai isian, tetap ada saat form dibangun ulang
const simKey = (g, k) => g + '|' + k;

function inputSim(g, k) {
    return `<input class="sim-in border px-2 py-1 w-full text-right" data-g="${g}" data-k="${esc(k)}" value="${formatID(SIM[simKey(g, k)] || 0)}">`;
}

function bangunSimulator() {
    const kas = kumpulKas().filter(k => k.aktif && k.kode);
    const bidang = new Set(BIDANG_TETAP);
    kumpulKas().forEach(k => { if (k.nama_bidang) bidang.add(norm(k.nama_bidang)); });

    let html = '<table class="sim-tbl w-full text-sm"><thead class="bg-gray-200"><tr>' +
        '<th>Kas</th><th>Saldo awal</th><th>Debit manual</th><th>Kredit manual</th><th>Pajak</th><th>Sumbangan</th></tr></thead><tbody>';
    kas.forEach(k => {
        const kode = norm(k.kode);
        html += `<tr><td>${esc(k.nama)}</td>
            <td>${k.saldo_awal_mode === 'carry_over' ? inputSim('saldo_awal', kode) : '<span class="text-gray-400">0</span>'}</td>
            <td>${k.debit_mode === 'manual' ? inputSim('debit_manual', kode) : '<span class="text-gray-400">rumus</span>'}</td>
            <td>${k.kredit_mode === 'manual' ? inputSim('kredit_manual', kode) : '<span class="text-gray-400">rumus</span>'}</td>
            <td>${k.punya_pajak ? inputSim('pajak', kode) : ''}</td>
            <td>${k.menerima_sumbangan ? inputSim('sumbangan', kode) : ''}</td></tr>`;
    });
    document.getElementById('simKas').innerHTML = html + '</tbody></table>';

    document.getElementById('simBidang').innerHTML = [...bidang].map(b =>
        `<label>${b}${inputSim('bidang', b)}</label>`).join('');

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
    out.innerHTML = '<span class="text-gray-500">Menghitung…</span>';

    try {
        const res = await fetch(URL_SIMULASI, { method: 'POST', headers: H, body: JSON.stringify({ ...payload(), input }) });
        const j = await res.json();

        if (!res.ok || !j.success) {
            const msg = (j.errors || []).map(e => '• ' + esc(e.message)).join('<br>') || esc(j.message || 'Gagal menghitung');
            out.innerHTML = `<div class="p-3 rounded border border-red-300 bg-red-50 text-red-700 text-sm">${msg}</div>`;
            return;
        }

        const { rows, grand } = j.hasil;
        out.innerHTML = `
          <table class="sim-tbl w-full text-sm">
            <thead class="bg-gray-200"><tr><th>Kas</th><th>Saldo awal</th><th>Debit</th><th>Kredit</th><th>Total</th></tr></thead>
            <tbody>
              ${rows.map(r => `<tr><td>${esc(r.nama)}</td>
                <td class="text-right">${formatID(r.saldo_awal)}</td><td class="text-right">${formatID(r.debit)}</td>
                <td class="text-right">${formatID(r.kredit)}</td><td class="text-right font-semibold">${formatID(r.total)}</td></tr>`).join('')}
              <tr class="font-bold bg-green-100"><td>TOTAL</td>
                <td class="text-right">${formatID(grand.saldo_awal)}</td><td class="text-right">${formatID(grand.debit)}</td>
                <td class="text-right">${formatID(grand.kredit)}</td><td class="text-right">${formatID(grand.total)}</td></tr>
            </tbody>
          </table>
          <div class="mt-2 text-sm p-2 rounded bg-gray-50 border">
            Total debit dibanding COS: <strong>${formatID(grand.debit - j.cos)}</strong>
            <span class="text-gray-500">(dengan rumus sekarang, jika sumbangan dan debit manual bunga = 0, selisih ini seharusnya 0 atau beberapa rupiah pembulatan)</span>
          </div>`;
    } catch (err) {
        out.innerHTML = `<div class="text-red-600 text-sm">${esc(err.message)}</div>`;
    }
}

/* ---------------- init ---------------- */
KAS_AWAL.forEach(k => document.getElementById('listKas').appendChild(kartuKas(k, false)));
PARAM_AWAL.forEach(p => tambahParam(p, false));
segarkanChip();
document.addEventListener('input', e => { if (e.target.closest('#tab-kas, #tab-param')) segarkanChip(); });
validasi();
</script>
@endsection
@extends('layouts.app')
@section('title','Pengaturan Bidang')

@section('content')
@include('pengaturan._ui')

<style>
    .bd-grid{grid-template-columns:76px 1.2fr 1fr 1.2fr 70px 100px 40px}
    .bd-grid .c-mid{display:flex;justify-content:center}
    @media (max-width:767px){
        .grid-head{display:none}
        .bd-grid{grid-template-columns:1fr 1fr;padding:14px}
        .bd-grid .c-urut{grid-column:1}
        .bd-grid .c-del{grid-column:2;display:flex;justify-content:flex-end}
        .bd-grid .c-nama,.bd-grid .c-kode,.bd-grid .c-kas{grid-column:1/-1}
        .bd-grid .c-mid{justify-content:space-between;align-items:center;padding:4px 0}
        .bd-grid .c-mid .lbl{margin:0;font-size:.85rem}
    }
</style>

<div class="ui-page">

    <div class="ui-bar">
        <div>
            @include('pengaturan._back')
            <h1>Pengaturan Bidang</h1>
            <p>Bidang menentukan pilihan Alokasi Dana di Pengajuan &amp; Belanja Koperasi, dan ke kas mana realisasinya dikreditkan.</p>
        </div>
        <div class="ui-act {{ $bolehUbah ? 'float' : '' }}">
            <span id="statusValidasi" class="ui-status"></span>
            @if($bolehUbah)
            <button id="btnSimpan" type="button" onclick="simpan()" class="btn btn-primary">
                Simpan pengaturan <kbd>Ctrl+S</kbd>
            </button>
            @endif
        </div>
    </div>

    <div id="errGlobal" class="ui-alert hidden"></div>

    <fieldset {{ $bolehUbah ? '' : 'disabled' }} style="border:none;padding:0;margin:0;min-width:0">
        <div class="card">
            <div class="grid-head bd-grid">
                <div>Urutan</div><div>Nama</div><div>Kode</div><div>Kas tujuan (kredit)</div>
                <div style="text-align:center">Aktif</div><div style="text-align:center">Punya insentif</div><div></div>
            </div>
            <div id="tbodyBidang"></div>
        </div>

        <button type="button" onclick="tambahBaris()" class="btn btn-ghost" style="margin-top:14px">+ Tambah bidang</button>
    </fieldset>
</div>

<script>
const BIDANG_AWAL = @json($bidang);
const KAS_AKTIF   = @json($kasAktif);
const BOLEH_UBAH  = @json($bolehUbah);

const URL_VALIDASI = "{{ route('bidang.validasi') }}";
const URL_SIMPAN   = "{{ route('bidang.sync') }}";
const H = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': "{{ csrf_token() }}"
};

const opt = (list, sel) => list.map(v => `<option value="${esc(v.kode)}" ${v.kode === sel ? 'selected' : ''}>${esc(v.nama)}</option>`).join('');
const barisEl = () => [...document.querySelectorAll('#tbodyBidang > .row')];

function buatBaris(b = {}, baru = false) {
    const r = document.createElement('div');
    r.className = 'row bd-grid';
    r.dataset.row = '1';
    r.innerHTML = `
        <div class="c-urut mv">
            <button type="button" class="btn-icon" onclick="geser(this,-1)" aria-label="Naikkan">▲</button>
            <button type="button" class="btn-icon" onclick="geser(this,1)" aria-label="Turunkan">▼</button>
        </div>
        <div class="c-nama"><div class="lbl">Nama</div><input type="text" class="inp f-nama" placeholder="Nama bidang"></div>
        <div class="c-kode"><div class="lbl">Kode</div><input type="text" class="inp mono f-kode" ${baru ? '' : 'readonly'}></div>
        <div class="c-kas"><div class="lbl">Kas tujuan (kredit)</div><select class="sel f-kas">${opt(KAS_AKTIF, b.kas_tujuan)}</select></div>
        <div class="c-mid"><div class="lbl">Aktif</div>
            <label class="sw"><input type="checkbox" class="f-aktif"><i></i></label></div>
        <div class="c-mid"><div class="lbl">Punya insentif</div>
            <label class="sw"><input type="checkbox" class="f-insentif"><i></i></label></div>
        <div class="c-del">${baru ? `<button type="button" class="btn-icon btn-del" onclick="hapusBaris(this)" aria-label="Hapus">✕</button>` : ''}</div>
        <div class="err-txt"></div>`;

    q(r, '.f-nama').value = b.nama ?? '';
    q(r, '.f-kode').value = b.kode ?? '';
    q(r, '.f-aktif').checked    = b.aktif !== false && b.aktif !== 0;
    q(r, '.f-insentif').checked = !!b.punya_insentif;

    if (baru) {
        q(r, '.f-nama').addEventListener('input', () => {
            const kode = q(r, '.f-kode');
            if (!kode.dataset.manual) kode.value = slug(q(r, '.f-nama').value);
        });
        q(r, '.f-kode').addEventListener('input', e => e.target.dataset.manual = '1');
    }
    return r;
}

function tambahBaris(b) {
    const r = buatBaris(b || { aktif: true }, true);
    document.getElementById('tbodyBidang').appendChild(r);
    q(r, '.f-nama').focus();
    jadwalValidasi();
}
function hapusBaris(btn) { btn.closest('.row').remove(); jadwalValidasi(); }

// hanya satu baris boleh "punya insentif"
document.addEventListener('change', e => {
    if (!e.target.matches('.f-insentif')) return;
    if (e.target.checked) document.querySelectorAll('.f-insentif').forEach(el => { if (el !== e.target) el.checked = false; });
    jadwalValidasi();
});
document.addEventListener('input', e => { if (e.target.closest('#tbodyBidang')) jadwalValidasi(); });

function kumpul() {
    return barisEl().map(r => ({
        nama: q(r, '.f-nama').value,
        kode: q(r, '.f-kode').value,
        kas_tujuan: q(r, '.f-kas').value,
        aktif: q(r, '.f-aktif').checked,
        punya_insentif: q(r, '.f-insentif').checked,
    }));
}

let tv = null;
function jadwalValidasi() { clearTimeout(tv); tv = setTimeout(validasi, 400); }

function tampilError(errors) {
    const baris = barisEl();
    baris.forEach(r => { r.classList.remove('invalid'); q(r, '.err-txt').textContent = ''; });
    const g = document.getElementById('errGlobal');
    g.classList.add('hidden'); g.innerHTML = '';

    const label = { kode: 'Kode', nama: 'Nama', kas_tujuan: 'Kas tujuan' };
    errors.forEach(er => {
        const teks = (label[er.field] ? label[er.field] + ': ' : '') + er.message;
        if (er.index !== null && baris[er.index]) {
            baris[er.index].classList.add('invalid');
            const c = q(baris[er.index], '.err-txt');
            c.textContent += (c.textContent ? ' | ' : '') + teks;
        } else {
            g.classList.remove('hidden');
            g.insertAdjacentHTML('beforeend', `<div>• ${esc(teks)}</div>`);
        }
    });

    const st = document.getElementById('statusValidasi');
    st.textContent = errors.length ? `✗ ${errors.length} masalah` : '✓ Semua valid';
    st.style.color = errors.length ? 'var(--ui-bad)' : 'var(--ui-ok)';
}

async function validasi() {
    try {
        const res = await fetch(URL_VALIDASI, { method: 'POST', headers: H, body: JSON.stringify({ bidang: kumpul() }) });
        const j = await res.json();
        tampilError(j.errors || []);
    } catch (e) { console.error(e); }
}

async function simpan(konfirmasi = false) {
    const btn = document.getElementById('btnSimpan');
    if (btn) btn.disabled = true;
    try {
        const res = await fetch(URL_SIMPAN, { method: 'POST', headers: H, body: JSON.stringify({ bidang: kumpul(), konfirmasi }) });
        const j = await res.json();

        if (res.status === 409 && j.needs_confirm) {
            if (btn) btn.disabled = false;
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
            showToast('Masih ada isian yang tidak valid.', 'error', 4000);
            return;
        }
        if (!res.ok || !j.success) throw new Error(j.message || 'Gagal menyimpan');

        showToast(j.message || 'Pengaturan berhasil disimpan.');
        setTimeout(() => location.reload(), 900);
    } catch (err) {
        showToast(err.message, 'error', 4000);
    } finally {
        if (btn) btn.disabled = false;
    }
}

bindSave(() => simpan(), BOLEH_UBAH);
BIDANG_AWAL.forEach(b => document.getElementById('tbodyBidang').appendChild(buatBaris(b, false)));
validasi();
</script>
@endsection
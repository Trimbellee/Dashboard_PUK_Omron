@extends('layouts.app')
@section('title','Pengaturan Insentif')

@section('content')
@include('pengaturan._ui')

<style>
    .ins-grid{grid-template-columns:76px 160px 1fr 170px 76px 40px}
    .ins-grid .c-aktif{display:flex;justify-content:center}
    .ins-grid .c-del{display:flex;justify-content:center}
    @media (max-width:767px){
        .grid-head{display:none}
        .ins-grid{grid-template-columns:1fr 1fr;padding:14px}
        .ins-grid .c-urut{grid-column:1}
        .ins-grid .c-del{grid-column:2;justify-content:flex-end}
        .ins-grid .c-tujuan,.ins-grid .c-kep{grid-column:1/-1}
        .ins-grid .c-nom{grid-column:1}
        .ins-grid .c-aktif{grid-column:2;justify-content:flex-end;align-self:end;padding-bottom:6px}
    }
</style>

<div class="ui-page">

    <div class="ui-bar">
        <div>
            @include('pengaturan._back')
            <h1>Pengaturan Insentif Sekretariat</h1>
            <p>Daftar ini menjadi template untuk laporan bulan baru. Laporan yang sudah tersimpan tidak ikut berubah.</p>
        </div>
        <div class="ui-act float">
            <button id="btnSimpan" type="button" onclick="simpanInsentif()" class="btn btn-primary">
                Simpan pengaturan <kbd>Ctrl+S</kbd>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="grid-head ins-grid">
            <div>Urutan</div><div>Tujuan</div><div>Keperluan</div>
            <div style="text-align:right">Nominal</div><div style="text-align:center">Aktif</div><div></div>
        </div>

        <div id="tbodyInsentif"></div>
        <div id="kosong" class="ui-empty hidden">Belum ada item insentif. Tambahkan item pertama di bawah.</div>

        <div class="total-bar">
            <span>Total (yang aktif)</span>
            <span id="totalInsentif">0</span>
        </div>
    </div>

    <button type="button" onclick="tambahBaris()" class="btn btn-ghost" style="margin-top:14px">+ Tambah item</button>
</div>

<script>
const ITEMS = @json($items);

function buatBaris(it = {}) {
    const aktif = !(it.aktif === false || it.aktif === 0);
    const r = document.createElement('div');
    r.className = 'row ins-grid' + (aktif ? '' : ' off');
    r.dataset.row = '1';
    r.dataset.id  = it.id ?? '';
    r.innerHTML = `
        <div class="c-urut mv">
            <button type="button" class="btn-icon" onclick="geser(this,-1)" aria-label="Naikkan">▲</button>
            <button type="button" class="btn-icon" onclick="geser(this,1)" aria-label="Turunkan">▼</button>
        </div>
        <div class="c-tujuan"><div class="lbl">Tujuan</div>
            <input type="text" class="inp f-tujuan" value="${esc(it.tujuan ?? 'Sekretariat')}"></div>
        <div class="c-kep"><div class="lbl">Keperluan</div>
            <input type="text" class="inp f-keperluan" placeholder="Untuk keperluan apa?" value="${esc(it.keperluan)}"></div>
        <div class="c-nom"><div class="lbl">Nominal</div>
            <input type="text" inputmode="numeric" class="inp num f-nominal" value="${formatID(it.nominal ?? 0)}"></div>
        <div class="c-aktif">
            <label class="sw" title="Aktif"><input type="checkbox" class="f-aktif" ${aktif ? 'checked' : ''}><i></i></label>
        </div>
        <div class="c-del">
            <button type="button" class="btn-icon btn-del" onclick="hapusBaris(this)" aria-label="Hapus">✕</button>
        </div>`;
    return r;
}

function cekKosong() {
    document.getElementById('kosong').classList.toggle('hidden', !!document.querySelector('#tbodyInsentif .row'));
}
function tambahBaris(it) {
    const r = buatBaris(it);
    document.getElementById('tbodyInsentif').appendChild(r);
    hitungTotal(); cekKosong();
    if (!it) q(r, '.f-keperluan').focus();
}
function hapusBaris(btn) { btn.closest('.row').remove(); hitungTotal(); cekKosong(); }

function hitungTotal() {
    let total = 0;
    document.querySelectorAll('#tbodyInsentif .row').forEach(r => {
        if (q(r, '.f-aktif').checked) total += parseID(q(r, '.f-nominal').value);
    });
    document.getElementById('totalInsentif').textContent = 'Rp ' + formatID(total);
}

document.addEventListener('input', e => {
    if (e.target.matches('.f-nominal')) e.target.value = formatID(parseID(e.target.value));
    if (e.target.matches('.f-keperluan')) e.target.closest('.row').classList.remove('invalid');
    if (e.target.matches('.f-nominal')) hitungTotal();
});
document.addEventListener('change', e => {
    if (!e.target.matches('.f-aktif')) return;
    e.target.closest('.row').classList.toggle('off', !e.target.checked);
    hitungTotal();
});

async function simpanInsentif() {
    const btn  = document.getElementById('btnSimpan');
    const rows = [...document.querySelectorAll('#tbodyInsentif .row')];
    const items = rows.map(r => ({
        id:        r.dataset.id ? Number(r.dataset.id) : null,
        tujuan:    q(r, '.f-tujuan').value.trim(),
        keperluan: q(r, '.f-keperluan').value.trim(),
        nominal:   parseID(q(r, '.f-nominal').value),
        aktif:     q(r, '.f-aktif').checked,
    }));

    const kosong = rows.filter((r, i) => !items[i].keperluan);
    rows.forEach(r => r.classList.remove('invalid'));
    if (kosong.length) {
        kosong.forEach(r => r.classList.add('invalid'));
        kosong[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        showToast('Kolom Keperluan tidak boleh kosong.', 'error', 4000);
        return;
    }

    btn.disabled = true;
    try {
        const res = await fetch("{{ route('insentif.sync') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ items })
        });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.message || 'Gagal menyimpan');
        showToast(json.message || 'Pengaturan berhasil disimpan.');
        setTimeout(() => location.reload(), 900); // muat ulang supaya baris baru mendapat id
    } catch (err) {
        showToast(err.message, 'error', 4000);
        btn.disabled = false;
    }
}

bindSave(simpanInsentif);
ITEMS.forEach(it => tambahBaris(it));
cekKosong();
</script>
@endsection
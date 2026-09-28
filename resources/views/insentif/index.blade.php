@extends('layouts.app')
@section('title','Pengaturan Insentif')

@section('content')
<div class="max-w-5xl mx-auto p-4">

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-bold">Pengaturan Insentif Sekretariat</h1>
            <p class="text-sm text-gray-500">
                Daftar ini menjadi template untuk laporan bulan baru. Laporan yang sudah tersimpan tidak ikut berubah.
            </p>
        </div>
        <button id="btnSimpan" type="button" onclick="simpanInsentif()"
            class="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-60">
            Simpan Pengaturan
        </button>
    </div>

    <table class="w-full border text-sm" id="tblInsentif">
        <thead class="bg-gray-200">
            <tr>
                <th style="width:8%">Urut</th>
                <th style="width:18%">Tujuan</th>
                <th>Keperluan</th>
                <th style="width:18%">Nominal</th>
                <th style="width:8%">Aktif</th>
                <th style="width:7%">Hapus</th>
            </tr>
        </thead>
        <tbody id="tbodyInsentif"></tbody>
        <tfoot class="font-bold bg-gray-100">
            <tr>
                <td colspan="3" class="text-right px-2">TOTAL (yang aktif)</td>
                <td class="text-right px-2" id="totalInsentif">0</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <button type="button" onclick="tambahBaris()" class="mt-3 px-3 py-1 border rounded">
        + Tambah Item
    </button>
</div>

<style>
    #tblInsentif th, #tblInsentif td { border:1px solid #d1d5db; padding:4px; }
    #tblInsentif input[type=text] { width:100%; border:none; background:transparent; }
    #tblInsentif input:focus { outline:1px solid #2563eb; }
</style>

<script>
const ITEMS = @json($items);

function parseID(v){ return Number(String(v ?? '').replace(/[^\d]/g,'')) || 0; }
function formatID(v){ return Math.floor(v || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
function esc(s){ return String(s ?? '').replace(/"/g,'&quot;'); }

function buatBaris(it = {}){
    const tr = document.createElement('tr');
    tr.dataset.id = it.id ?? '';
    tr.innerHTML = `
        <td class="text-center whitespace-nowrap">
            <button type="button" onclick="geser(this,-1)">▲</button>
            <button type="button" onclick="geser(this,1)">▼</button>
        </td>
        <td><input type="text" class="f-tujuan" value="${esc(it.tujuan ?? 'Sekretariat')}"></td>
        <td><input type="text" class="f-keperluan" value="${esc(it.keperluan)}"></td>
        <td><input type="text" inputmode="numeric" class="f-nominal text-right" value="${formatID(it.nominal ?? 0)}"></td>
        <td class="text-center"><input type="checkbox" class="f-aktif" ${it.aktif === false || it.aktif === 0 ? '' : 'checked'}></td>
        <td class="text-center">
            <button type="button" onclick="hapusBaris(this)"
                class="inline-flex items-center justify-center w-6 h-6 bg-red-600 text-white rounded-md font-bold text-sm">✕</button>
        </td>`;
    return tr;
}

function tambahBaris(it){ document.getElementById('tbodyInsentif').appendChild(buatBaris(it)); hitungTotal(); }
function hapusBaris(btn){ btn.closest('tr').remove(); hitungTotal(); }

function geser(btn, arah){
    const tr = btn.closest('tr');
    const target = arah < 0 ? tr.previousElementSibling : tr.nextElementSibling;
    if(!target) return;
    arah < 0 ? target.before(tr) : target.after(tr);
}

function hitungTotal(){
    let total = 0;
    document.querySelectorAll('#tbodyInsentif tr').forEach(tr=>{
        if(tr.querySelector('.f-aktif').checked){
            total += parseID(tr.querySelector('.f-nominal').value);
        }
    });
    document.getElementById('totalInsentif').innerText = formatID(total);
}

document.addEventListener('input', e=>{
    if(e.target.matches('.f-nominal')){
        e.target.value = formatID(parseID(e.target.value));
    }
    if(e.target.matches('.f-nominal, .f-aktif')) hitungTotal();
});
document.addEventListener('change', e=>{ if(e.target.matches('.f-aktif')) hitungTotal(); });

async function simpanInsentif(){
    const btn = document.getElementById('btnSimpan');
    const items = [...document.querySelectorAll('#tbodyInsentif tr')].map(tr => ({
        id:        tr.dataset.id ? Number(tr.dataset.id) : null,
        tujuan:    tr.querySelector('.f-tujuan').value.trim(),
        keperluan: tr.querySelector('.f-keperluan').value.trim(),
        nominal:   parseID(tr.querySelector('.f-nominal').value),
        aktif:     tr.querySelector('.f-aktif').checked,
    }));

    if(items.some(i => !i.keperluan)){
        alert('Kolom Keperluan tidak boleh kosong.');
        return;
    }

    btn.disabled = true;
    try{
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
        if(!res.ok || !json.success) throw new Error(json.message || 'Gagal menyimpan');
        alert(json.message);
        location.reload(); // muat ulang supaya baris baru mendapat id
    }catch(err){
        alert(err.message);
    }finally{
        btn.disabled = false;
    }
}

ITEMS.forEach(it => tambahBaris(it));
</script>
@endsection
@extends('layouts.app')

@section('title', 'Kode Penomoran Surat')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- FORM --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow ">
        <h2 class="text-xl font-bold mb-4">Kode Penomoran Surat</h2>

        <form id="formKode" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            {{-- JENIS SURAT --}}
            <div>
                <label>Jenis Surat</label>
                <select name="jenis" required class="w-full border p-2 rounded dark:text-gray-800">
                    <option value="" disabled selected>Pilih Jenis Surat</option>
                    <option value="IN">Internal</option>
                    <option value="EKS">Eksternal</option>
                </select>
            </div>

            {{-- BIDANG --}}
            <div>
                <label>Bidang</label>
                <select name="bidang" required class="w-full border p-2 rounded dark:text-gray-800">
                    <option value="" disabled selected>Pilih Bidang</option>
                    <option value="SEK">Sekretaris</option>
                    <option value="BID.1">Bidang 1</option>
                    <option value="BID.2">Bidang 2</option>
                    <option value="BID.3">Bidang 3</option>
                    <option value="BID.4">Bidang 4</option>
                    <option value="BID.5">Bidang 5</option>
                    <option value="BID.6">Bidang 6</option>
                    <option value="GM">Garda Metal</option>
                    <option value="KORLAP">KORLAP</option>
                </select>
            </div>

            {{-- BULAN --}}
            <div>
                <label>Bulan</label>
                <select name="bulan" required class="w-full border p-2 rounded dark:text-gray-800">
                    <option value="" disabled selected>Pilih Bulan</option>
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            {{-- TAHUN --}}
            <div>
                <label>Tahun</label>
                <input type="number"
                    name="tahun"
                    required
                    placeholder="Contoh: 2025"
                    class="w-full border p-2 rounded  dark:text-gray-800">
            </div>

            {{-- KETERANGAN --}}
            <div class="md:col-span-2">
                <label>Keterangan</label>
                <input name="keterangan"
                    required
                    class="w-full border p-2 rounded"
                    placeholder="Contoh: Proposal Pendidikan Dasar">
            </div>

            {{-- BUTTON --}}
            <div class="md:col-span-2 text-right">
                <button type="submit"
                        id="btnGenerate"
                        class="bg-blue-600 hover:bg-blue-700 text-white
                            px-6 py-2 rounded flex items-center justify-center gap-2">

                    <svg id="spinner"
                        class="hidden animate-spin h-5 w-5 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25"
                                cx="12" cy="12" r="10"
                                stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4
                                a4 4 0 00-4 4H4z"></path>
                    </svg>

                    <span id="btnText">Generate</span>
                </button>
            </div>
        </form>


        <div id="hasilBox" class="mt-4 hidden">
            <strong>Nomor Surat:</strong>

            <div class="mt-2 flex items-center gap-2">
                <div id="hasilKode"
                    class="flex-1 p-3 bg-gray-100 dark:bg-gray-700
                            rounded font-mono text-lg select-all">
                </div>

                <button id="btnCopy"
                    class="px-3 py-2 text-sm border rounded
                        hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
                    Copy
                </button>
            </div>

            <div id="copyInfo"
                class="text-xs text-green-600 mt-1 hidden">
                ✓ Nomor surat berhasil disalin
            </div>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h3 class="font-bold mb-4">Riwayat Kode Surat</h3>
        {{-- FILTER --}}
        <div class="flex flex-wrap gap-3 mb-4 items-end">
            <div>
                <label class="text-sm">Search</label>
                <input type="text"
                    id="searchText"
                    placeholder="Cari no surat"
                    class="border p-2 rounded w-56">
            </div>

            <div>
                <label class="text-sm">Filter Bulan</label>
                <select id="filterBulan" class="border p-2 rounded dark:text-gray-800">
                    <option value="">Semua&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="text-sm">Filter Tahun</label>
                <input type="number"
                    id="filterTahun"
                    placeholder="Contoh: 2025"
                    class="border p-2 rounded w-32">
            </div>
            <button id="btnFilter"
                class="bg-blue-600 hover:bg-blue-700 text-white
                    px-4 py-2 rounded flex items-center gap-2">

                <svg id="filterSpinner"
                    class="hidden animate-spin h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25"
                            cx="12" cy="12" r="10"
                            stroke="currentColor"
                            stroke-width="4"></circle>
                    <path class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4
                            a4 4 0 00-4 4H4z"></path>
                </svg>

                <span id="filterText">Terapkan Filter</span>
            </button>

        </div>

        <table class="w-full text-sm border">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="border p-2">No</th>
                    <th class="border p-2">No Surat</th>
                    <th class="border p-2">Keterangan</th>
                    <th class="border p-2">Tanggal</th>
                </tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>

        <div class="flex justify-between items-center mt-4">
    <div id="pageInfo" class="text-sm text-gray-500"></div>

    <div id="pagination"
         class="flex gap-1"></div>
</div>

    </div>

</div>

<script>
const btnGenerate = document.getElementById('btnGenerate');
const spinner     = document.getElementById('spinner');
const btnText     = document.getElementById('btnText');
const form   = document.getElementById('formKode');
const hasil  = document.getElementById('hasilKode');
const box    = document.getElementById('hasilBox');
const tbody  = document.getElementById('tableBody');
const pager  = document.getElementById('pagination');
const info   = document.getElementById('pageInfo');
const btnCopy  = document.getElementById('btnCopy');
const copyInfo = document.getElementById('copyInfo');
const filterSpinner = document.getElementById('filterSpinner');
const filterText    = document.getElementById('filterText');
const btnFilter   = document.getElementById('btnFilter');
const filterBulan = document.getElementById('filterBulan');
const filterTahun = document.getElementById('filterTahun');
const searchText = document.getElementById('searchText');

let filteredData = [];
let allData = [];
let currentPage = 1;
const perPage = 10;

/* ======================
   GENERATE KODE SURAT
====================== */
form.addEventListener('submit', async e => {
    e.preventDefault();

    // aktifkan spinner
    btnGenerate.disabled = true;
    spinner.classList.remove('hidden');
    btnText.innerText = 'Generating...';

    try {
        const res = await fetch("{{ route('kode-surat.generate') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name=_token]').value
            },
            body: new FormData(form)
        });

        const data = await res.json();

        hasil.innerText = data.no_surat;
        box.classList.remove('hidden');

        form.reset();
        form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        loadTable();

    } catch (err) {
        alert('Gagal generate nomor surat');
        console.error(err);
    }

    // matikan spinner
    btnGenerate.disabled = false;
    spinner.classList.add('hidden');
    btnText.innerText = 'Generate';
});


/* ======================
   LOAD DATA TABLE
====================== */
async function loadTable() {
    const res = await fetch("{{ route('kode-surat.data') }}");
    allData = await res.json();

    filteredData = [...allData]; // default tanpa filter
    currentPage = 1;

    renderTable();
    renderPagination();
}

/*======================
    FORMAT TANGGAL
======================*/

function formatTanggalIndo(tgl) {
    if (!tgl) return '-';

    // asumsi format: dd/mm/yyyy
    const [day, month, year] = tgl.split('/');

    const bulan = [
        'Jan','Feb','Mar','Apr','May','Jun',
        'Jul','Aug','Sep','Oct','Nov','Dec'
    ];

    return `${day} ${bulan[parseInt(month) - 1]} ${year}`;
}


/* ======================
   RENDER TABLE
====================== */
function renderTable() {
    tbody.innerHTML = '';

    if (!filteredData.length) {
        tbody.innerHTML = `
        <tr>
            <td colspan="4" class="text-center p-4 text-gray-400">
                Belum ada data
            </td>
        </tr>`;
        info.innerText = '';
        return;
    }

    const total = filteredData.length;
    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    const pageData = filteredData
        .slice()
        .reverse()
        .slice(start, end);


    pageData.forEach((row, i) => {
        tbody.innerHTML += `
        <tr>
            <td class="border p-2 text-center">${start + i + 1}</td>
            <td class="border p-2 font-mono">${row.no_surat}</td>
            <td class="border p-2">${row.keterangan || '-'}</td>
            <td class="border p-2">${formatTanggalIndo(row.tanggal)}</td>
        </tr>`;
    });

    info.innerText =
    `Menampilkan ${start + 1} - ${Math.min(end, total)} dari ${total} data`;

}

/* ======================
   RENDER PAGINATION
====================== */
function renderPagination() {
    pager.innerHTML = '';
    const totalPage = Math.ceil(filteredData.length / perPage);
    if (totalPage <= 1) return;

    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end   = start + maxVisible - 1;

    if (end > totalPage) {
        end = totalPage;
        start = Math.max(1, end - maxVisible + 1);
    }

    // ===== PREV =====
    pager.innerHTML += `
    <button onclick="goPage(${currentPage - 1})"
        ${currentPage === 1 ? 'disabled' : ''}
        class="px-3 py-1 border rounded ${currentPage === 1 ? 'opacity-40' : ''}">
        Prev
    </button>`;

    // ===== FIRST PAGE =====
    if (start > 1) {
        pager.innerHTML += `
        <button onclick="goPage(1)"
            class="px-3 py-1 border rounded">
            1
        </button>`;

        if (start > 2) {
            pager.innerHTML += `
            <span class="px-2 py-1 text-gray-500">...</span>`;
        }
    }

    // ===== MIDDLE PAGES =====
    for (let i = start; i <= end; i++) {
        pager.innerHTML += `
        <button onclick="goPage(${i})"
            class="px-3 py-1 border rounded
            ${i === currentPage ? 'bg-blue-600 text-white' : ''}">
            ${i}
        </button>`;
    }

    // ===== LAST PAGE =====
    if (end < totalPage) {
        if (end < totalPage - 1) {
            pager.innerHTML += `
            <span class="px-2 py-1 text-gray-500">...</span>`;
        }

        pager.innerHTML += `
        <button onclick="goPage(${totalPage})"
            class="px-3 py-1 border rounded">
            ${totalPage}
        </button>`;
    }

    // ===== NEXT =====
    pager.innerHTML += `
    <button onclick="goPage(${currentPage + 1})"
        ${currentPage === totalPage ? 'disabled' : ''}
        class="px-3 py-1 border rounded ${currentPage === totalPage ? 'opacity-40' : ''}">
        Next
    </button>`;
}

function goPage(page) {
    const totalPage = Math.ceil(filteredData.length / perPage);
    if (page < 1 || page > totalPage) return;
    currentPage = page;
    renderTable();
    renderPagination();
}


btnCopy.addEventListener('click', async () => {
    const text = hasil.innerText.trim();
    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);

        copyInfo.classList.remove('hidden');
        btnCopy.innerText = 'Tersalin ✓';

        setTimeout(() => {
            copyInfo.classList.add('hidden');
            btnCopy.innerText = 'Copy';
        }, 3000);

    } catch (err) {
        alert('Gagal menyalin nomor surat');
    }
});


btnFilter.addEventListener('click', () => {

    // aktifkan spinner
    btnFilter.disabled = true;
    filterSpinner.classList.remove('hidden');
    filterText.innerText = 'Memfilter...';

    setTimeout(() => {
        const bulan  = filterBulan.value;
        const tahun  = filterTahun.value;
        const search = searchText.value.toLowerCase().trim();

        filteredData = allData.filter(row => {

            // ===== FILTER BULAN & TAHUN =====
            const parts = row.tanggal.split('/');
            const rowBulan = parseInt(parts[1]);
            const rowTahun = parts[2];

            if (bulan && rowBulan != bulan) return false;
            if (tahun && rowTahun != tahun) return false;

            // ===== SEARCH (NO SURAT + KETERANGAN) =====
            if (search) {
                const noSurat    = (row.no_surat || '').toLowerCase();
                const keterangan = (row.keterangan || '').toLowerCase();

                if (
                    !noSurat.includes(search) &&
                    !keterangan.includes(search)
                ) {
                    return false;
                }
            }

            return true;
        });

        currentPage = 1;
        renderTable();
        renderPagination();

        // matikan spinner
        btnFilter.disabled = false;
        filterSpinner.classList.add('hidden');
        filterText.innerText = 'Terapkan Filter';

    }, 300);
});




/* INIT */
loadTable();
</script>

@endsection

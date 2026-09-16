/* ============================================================
   KODE SURAT — LOGIC
   Membutuhkan elemen root dengan:
     data-generate-url="{{ route('kode-surat.generate') }}"
     data-data-url="{{ route('kode-surat.data') }}"
   dan meta tag: <meta name="csrf-token" content="{{ csrf_token() }}">
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('ksRoot');
    if (!root) return; // halaman ini tidak sedang dibuka

    const GENERATE_URL = root.dataset.generateUrl;
    const DATA_URL      = root.dataset.dataUrl;
    const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content;

    /* ============================================================
       ELEMENT REFS
       ============================================================ */
    const btnGenerate   = document.getElementById('btnGenerate');
    const spinner       = document.getElementById('spinner');
    const btnText       = document.getElementById('btnText');
    const form          = document.getElementById('formKode');
    const hasil         = document.getElementById('hasilKode');
    const box           = document.getElementById('hasilBox');
    const tbody         = document.getElementById('tableBody');
    const pager         = document.getElementById('pagination');
    const info          = document.getElementById('pageInfo');
    const btnCopy       = document.getElementById('btnCopy');
    const copyInfo      = document.getElementById('copyInfo');
    const filterSpinner = document.getElementById('filterSpinner');
    const filterText    = document.getElementById('filterText');
    const btnFilter     = document.getElementById('btnFilter');
    const filterBulan   = document.getElementById('filterBulan');
    const filterTahun   = document.getElementById('filterTahun');
    const searchText    = document.getElementById('searchText');
    const selectBidang  = document.getElementById('selectBidang');
    const wrapperJenis  = document.getElementById('wrapperJenis');

    let filteredData = [];
    let allData      = [];
    let currentPage  = 1;
    const perPage    = 10;

    /* ============================================================
       BIDANG → Sembunyikan Jenis kalau SK
       ============================================================ */
    selectBidang.addEventListener('change', function () {
        const isSK = this.value === 'SK';
        wrapperJenis.style.display = isSK ? 'none' : '';
        wrapperJenis.querySelector('select').required = !isSK;
    });

    /* ============================================================
       GENERATE KODE SURAT
       ============================================================ */
    form.addEventListener('submit', async e => {
        e.preventDefault();
        btnGenerate.disabled  = true;
        spinner.style.display = 'block';
        btnText.innerText     = 'Generating...';

        try {
            const res  = await fetch(GENERATE_URL, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                body:    new FormData(form),
            });
            const data = await res.json();

            hasil.innerText   = data.no_surat;
            box.style.display = 'block';

            form.reset();
            form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
            wrapperJenis.style.display = ''; // reset hide state
            loadTable();
        } catch (err) {
            alert('Gagal generate nomor surat');
            console.error(err);
        }

        btnGenerate.disabled  = false;
        spinner.style.display = 'none';
        btnText.innerText     = 'Generate';
    });

    /* ============================================================
       LOAD DATA TABLE
       ============================================================ */
    async function loadTable() {
        const res    = await fetch(DATA_URL);
        allData      = await res.json();
        filteredData = [...allData];
        currentPage  = 1;
        renderTable();
        renderPagination();
    }

    /* ============================================================
       FORMAT TANGGAL INDO
       ============================================================ */
    function formatTanggalIndo(tgl) {
        if (!tgl) return '-';
        const [day, month, year] = tgl.split('/');
        const bulan = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${day} ${bulan[parseInt(month) - 1]} ${year}`;
    }

    /* ============================================================
       RENDER TABLE
       ============================================================ */
    function renderTable() {
        tbody.innerHTML = '';

        if (!filteredData.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="ks-table-empty">
                        Belum ada data
                    </td>
                </tr>`;
            info.innerText = '';
            return;
        }

        const total    = filteredData.length;
        const start    = (currentPage - 1) * perPage;
        const end      = start + perPage;
        const pageData = filteredData.slice().reverse().slice(start, end);

        pageData.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${start + i + 1}</td>
                <td class="ks-td-no-surat">${row.no_surat}</td>
                <td>${row.keterangan || '-'}</td>
                <td>${formatTanggalIndo(row.tanggal)}</td>`;
            tbody.appendChild(tr);
        });

        info.innerText = `Menampilkan ${start + 1} – ${Math.min(end, total)} dari ${total} data`;
    }

    /* ============================================================
       RENDER PAGINATION
       ============================================================ */
    function renderPagination() {
        pager.innerHTML = '';
        const totalPage = Math.ceil(filteredData.length / perPage);
        if (totalPage <= 1) return;

        const maxVisible = 5;
        let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let end   = start + maxVisible - 1;
        if (end > totalPage) { end = totalPage; start = Math.max(1, end - maxVisible + 1); }

        function addBtn(label, page, active = false, disabled = false) {
            const btn = document.createElement('button');
            btn.innerHTML = label;
            btn.className = 'ks-page-btn' + (active ? ' is-active' : '');
            btn.disabled  = disabled;
            btn.onclick   = () => goPage(page);
            pager.appendChild(btn);
        }

        function addDots() {
            const dots = document.createElement('span');
            dots.textContent = '…';
            dots.className   = 'ks-page-dots';
            pager.appendChild(dots);
        }

        addBtn('Prev', currentPage - 1, false, currentPage === 1);

        if (start > 1) {
            addBtn('1', 1);
            if (start > 2) addDots();
        }

        for (let i = start; i <= end; i++) addBtn(i, i, i === currentPage);

        if (end < totalPage) {
            if (end < totalPage - 1) addDots();
            addBtn(totalPage, totalPage);
        }

        addBtn('Next', currentPage + 1, false, currentPage === totalPage);
    }

    function goPage(page) {
        const totalPage = Math.ceil(filteredData.length / perPage);
        if (page < 1 || page > totalPage) return;
        currentPage = page;
        renderTable();
        renderPagination();
    }

    /* ============================================================
       COPY
       ============================================================ */
    btnCopy.addEventListener('click', async () => {
        const text = hasil.innerText.trim();
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            copyInfo.style.display = 'block';
            btnCopy.textContent    = 'Tersalin ✓';
            setTimeout(() => {
                copyInfo.style.display = 'none';
                btnCopy.textContent    = 'Copy';
            }, 3000);
        } catch {
            alert('Gagal menyalin nomor surat');
        }
    });

    /* ============================================================
       FILTER
       ============================================================ */
    btnFilter.addEventListener('click', () => {
        btnFilter.disabled          = true;
        filterSpinner.style.display = 'block';
        filterText.innerText        = 'Memfilter...';

        setTimeout(() => {
            const bulan  = filterBulan.value;
            const tahun  = filterTahun.value;
            const search = searchText.value.toLowerCase().trim();

            filteredData = allData.filter(row => {
                const parts    = row.tanggal.split('/');
                const rowBulan = parseInt(parts[1]);
                const rowTahun = parts[2];

                if (bulan && rowBulan != bulan) return false;
                if (tahun && rowTahun != tahun) return false;

                if (search) {
                    const noSurat    = (row.no_surat || '').toLowerCase();
                    const keterangan = (row.keterangan || '').toLowerCase();
                    if (!noSurat.includes(search) && !keterangan.includes(search)) return false;
                }

                return true;
            });

            currentPage = 1;
            renderTable();
            renderPagination();

            btnFilter.disabled          = false;
            filterSpinner.style.display = 'none';
            filterText.innerText        = 'Terapkan Filter';
        }, 300);
    });

    /* ============================================================
       INIT
       ============================================================ */
    loadTable();
});
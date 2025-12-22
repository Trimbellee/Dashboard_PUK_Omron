@extends('layouts.app')
@section('title', 'Pengajuan | PUK OMRON')
@section('content')
<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 sm:mb-6 gap-3">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100 text-center sm:text-left">Data Pengajuan Dana</h1>
        <button id="btnAdd" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition w-full sm:w-auto">+ Tambah Pengajuan</button>
    </div>

    <!-- Filter + control -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mb-3">
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <label class="text-sm text-gray-700 dark:text-gray-200">Status:</label>
            <select id="filterStatus" class="border rounded px-3 py-1 w-32 sm:w-32
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600">
                <option value="">Semua</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Disetujui">Disetujui</option>
                <option value="Ditolak">Ditolak</option>
            </select>
        </div>
        <!-- Bulan -->
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <label class="text-sm text-gray-700 dark:text-gray-200">Bulan:</label>
                <select id="filterBulan" class="border rounded px-3 py-1 w-32 sm:w-36
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600">
                <option value="">Semua</option>
                @for($i=1;$i<=12;$i++)
                <option value="{{ $i }}">{{ DateTime::createFromFormat('!m',$i)->format('F') }}</option>
                @endfor
                </select>
        </div>

      <!-- Tahun -->
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <label class="text-sm text-gray-700 dark:text-gray-200">Tahun:</label>

            <input 
              list="listTahun"
                id="filterTahun"
                placeholder="Semua"
                inputmode="numeric"
                maxlength="4"
                class="border rounded px-3 py-1 w-24
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600"
            >

            <datalist id="listTahun">
                <option value="">Semua</option>
                @for($y = date('Y'); $y >= 2023; $y--)
                    <option value="{{ $y }}"></option>
                @endfor
            </datalist>
        </div>


        <!-- Bidang -->
       <div class="flex flex-wrap items-center gap-3 mb-3">
            <label class="text-sm text-gray-700 dark:text-gray-200">Bidang:</label>
                <select id="filterBidang" class="border rounded px-3 py-1 w-36 sm:w-40
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600">
                    <option value="">Semua</option>
                    <option>Ketua</option>
                    <option>Sekretaris</option>
                    <option>Bendahara</option>
                    <option>Bidang 1</option>
                    <option>Bidang 2</option>
                    <option>Bidang 3</option>
                    <option>Bidang 4</option>
                    <option>Bidang 5</option>
                    <option>Bidang 6</option>
                    <option>GM</option>
                    <option>KorLap</option>
                </select>
        </div>

        <!-- Button -->
        <button id="applyFilterBtn"
            class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
            Terapkan Filter
        </button>

    
       
    </div>

{{-- Export Form PRO --}}
@if(in_array(session('user')['role'], ['Ketua', 'Sekretaris', 'Bendahara']))
<form action="{{ route('pengajuan.export') }}" method="GET" 
      class="flex flex-wrap gap-3 mb-4 items-center">

    <!-- BULAN -->
    <select name="bulan" class="border rounded px-3 py-2 shadow-sm
              bg-white dark:bg-slate-800
              text-gray-900 dark:text-black
              border-gray-300 dark:border-gray-600 focus:ring focus:ring-blue-200">
        <option value="">Semua Bulan&nbsp;&nbsp;&nbsp;&nbsp;</option>
        @for($i = 1; $i <= 12; $i++)
            <option value="{{ $i }}">
                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
            </option>
        @endfor
    </select>

    <!-- TAHUN PRO -->
    <div class="relative">
        <input list="tahunList" name="tahun" id="tahunInput"
               class="border rounded px-3 py-2 shadow-sm
                bg-white dark:bg-slate-800
                text-gray-900 dark:text-gray-700
                border-gray-300 dark:border-gray-600 focus:ring focus:ring-blue-200 w-36"
               placeholder="Tahun"
               maxlength="4">

        <datalist id="tahunList">
            <option value="">-- Semua Tahun --</option>
            @php
                $thisYear = date('Y');
            @endphp
            @for($t = $thisYear - 1; $t <= $thisYear + 4; $t++)
                <option value="{{ $t }}">{{ $t }}</option>
            @endfor
        </datalist>
    </div>

    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
        Export XLSX
    </button>
</form>
@endif


<!-- VALIDASI PRO UNTUK INPUT TAHUN -->
<script>
document.getElementById('tahunInput').addEventListener('input', function() {
    let v = this.value;
    v = v.replace(/[^0-9]/g, '');
    if (v.length > 4) v = v.slice(0, 4);
    this.value = v;
});
document.getElementById('tahunInput').addEventListener('blur', function() {
    let v = this.value;
    if (!v) return;
    if (v.length < 4) {
        let now = new Date().getFullYear().toString();
        this.value = now.substring(0, 4 - v.length) + v;
    }
});
</script>

    <!-- Responsive Table Container with Sticky Header -->
    <div class="overflow-x-auto bg-white dark:bg-slate-800
            shadow-md rounded-lg
            text-gray-900 dark:text-gray-100 max-h-[120vh] overflow-y-auto relative">
        <table class="min-w-full border-collapse text-sm sm:text-base table-fixed">
            <thead class="bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-700 sticky top-0 z-10 shadow-sm">
                <tr>
                    <th class="py-2 px-3 border text-center w-12">No</th>
                    <th class="py-2 px-3 border">Nama</th>
                    <th class="py-2 px-3 border">Bidang</th>
                    <th class="py-2 px-3 border w-[120px] md:w-[200px]">Tujuan</th>
                    <th class="py-2 px-3 border w-[120px] md:w-[200px]">Keperluan</th>
                    <th class="py-2 px-3 border text-right">Biaya</th>

                    <!-- New full-table columns -->
                    <th class="py-2 px-3 border text-center">Lampiran Proposal</th>
                    <th class="py-2 px-3 border text-center">Aktual Biaya</th>
                    <th class="py-2 px-3 border text-center">Dikembalikan</th>
                    <th class="py-2 px-3 border text-center">Lampiran Laporan</th>

                    <th class="py-2 px-3 border text-center">Status</th>
                    <th class="py-2 px-3 border text-center">Catatan</th>
                    <th class="py-2 px-3 border text-center">Approver</th>
                    <th class="py-2 px-3 border text-center">Tanggal</th>
                    <th class="py-2 px-3 border text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="dataBody" class="text-gray-800"></tbody>
        </table>
    </div>

    <!-- Pagination + per-page -->
    <div class="mt-4 flex flex-col md:flex-row md:items-center">

        <!-- Pagination Center -->
        <div class="flex justify-center md:justify-center w-full md:w-2/3 mb-3 md:mb-0">
            <div class="flex items-center space-x-2" role="navigation" aria-label="Pagination">
                <button id="prevPage" class="px-3 py-1 border rounded
                            bg-white dark:bg-slate-800
                            hover:bg-gray-100 dark:hover:bg-slate-700
                            text-gray-900 dark:text-gray-900
                            border-gray-300 dark:border-gray-600 flex items-center gap-2" aria-label="Previous page">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 16.293a1 1 0 010 1.414L10.586 19.414a1 1 0 01-1.414 0L3 13.242V13a1 1 0 011-1h6.586l1.707 1.707a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                    Prev
                </button>

                <div id="pageButtons" class="flex space-x-2 bg-white dark:bg-slate-800
                            hover:bg-gray-100 dark:hover:bg-slate-700
                            text-gray-900 dark:text-gray-900
                            border-gray-300 dark:border-gray-600 "></div>

                <button id="nextPage" class="px-3 py-1 border rounded
                            bg-white dark:bg-slate-800
                            hover:bg-gray-100 dark:hover:bg-slate-700
                            text-gray-900 dark:text-gray-900
                            border-gray-300 dark:border-gray-600 flex items-center gap-2" aria-label="Next page">
                    Next
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 3.707a1 1 0 010-1.414L9.414.586a1 1 0 011.414 0l6.172 6.172V7a1 1 0 01-1 1H9.414L7.707 6.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>


    </div>
    <br>
     <div class="ml-auto text-sm text-gray-600 flex flex-col sm:flex-row items-start sm:items-center gap-2">            
            <span id="statusInfo" class="statusInfo text-sm text-gray-600 dark:text-gray-100">Menampilkan data sesuai filter</span>
             <!-- Per Page Right -->
        <div class="flex justify-end w-full md:w-1/3 items-center space-x-2">
            <label for="perPage" class="text-sm text-gray-600  dark:text-gray-100">Per halaman:</label>
            <select id="perPage" class="border rounded px-2 py-1 border rounded
                            bg-white dark:bg-slate-800
                            hover:bg-gray-100 dark:hover:bg-slate-700
                            text-gray-900 dark:text-gray-900
                            border-gray-300 dark:border-gray-600 ">
                <option value="5">5</option>
                <option value="10" selected>10&nbsp;&nbsp;&nbsp;&nbsp;</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

   
</div>

<!-- Modal Tambah Pengajuan (FIX scroll + mobile safe) -->
<div id="modalAdd" class="fixed inset-0 z-50 hidden dark:text-gray-800">

    <!-- Backdrop (FIXED, tidak ikut scroll) -->
    <div class="fixed inset-0 bg-black/30 backdrop-blur-sm"></div>

    <!-- Wrapper scroll -->
    <div class="fixed inset-0 flex justify-center items-start overflow-y-auto p-4">
        
        <!-- Modal box -->
        <div
            id="modalAddBox"
            class="relative mt-10 bg-white dark:bg-neutral-800
                   rounded-2xl shadow-2xl
                   w-full max-w-lg
                   transform transition-all duration-300
                   scale-95 opacity-0"
        >

            <!-- Header (sticky, tidak ikut blur) -->
            <div class="p-6 border-b border-gray-200 flex justify-between items-center
                        sticky top-0 bg-white dark:bg-neutral-800 z-10 rounded-t-2xl">
                <h2 class="text-xl font-semibold text-gray-700">
                    Tambah Pengajuan
                </h2>
                <button id="closeAdd" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">
                    &times;
                </button>
            </div>

            <!-- Form (TIDAK DIUBAH) -->
            <form id="formAdd" class="p-6 space-y-4" enctype="multipart/form-data">
                <input type="hidden" id="addNama" name="Nama" value="{{ session('user.username') }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bidang</label>
                    <select id="addBidang" name="Bidang" class="mt-1 w-full border-gray-300 rounded-lg text-gray-700" required>
                        <option value="">-- Pilih Bidang --</option>
                        <option>Ketua</option>
                        <option>Sekretaris</option>
                        <option>Bendahara</option>
                        <option>Bidang 1</option>
                        <option>Bidang 2</option>
                        <option>Bidang 3</option>
                        <option>Bidang 4</option>
                        <option>Bidang 5</option>
                        <option>Bidang 6</option>
                        <option>GM</option>
                        <option>KorLap</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tujuan</label>
                    <input type="text" id="addTujuan" name="Tujuan" class="mt-1 w-full border-gray-300 rounded-lg text-gray-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Keperluan</label>
                    <textarea id="addKeperluan" name="Keperluan" class="mt-1 w-full border-gray-300 rounded-lg text-gray-700" required></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Biaya</label>
                    <input type="number" id="addBiaya" name="Biaya" class="mt-1 w-full border-gray-300 rounded-lg text-gray-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 ">Lampiran Proposal (PDF)</label>
                    <input type="file" id="addProposalFile" accept="application/pdf" class="mt-1 w-full" />
                    <p class="text-xs text-gray-500 mt-1">
                        Format PDF. File akan diupload ke Google Drive melalui Apps Script.
                    </p>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <button type="button" id="cancelAdd"
                        class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                        Batal
                    </button>
                    <button type="submit" id="submitAdd"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <span class="spinner hidden mr-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        Simpan
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- Modal Upload Laporan -->
<div id="modalUploadLaporan" class="hidden fixed inset-0 flex justify-center items-start overflow-y-auto z-50 dark:text-gray-800">
  <div class="absolute inset-0 backdrop-blur-sm bg-black/30"></div>
  <div class="mt-20 bg-white rounded-lg shadow-lg w-full max-w-md transform transition-all duration-300 scale-95 opacity-0 z-10" id="modalUploadLaporanBox" style="backdrop-filter: blur(6px);">
    <div class="p-5 border-b border-gray-200 flex justify-between items-center">
      <h2 class="text-lg font-semibold text-gray-700">Upload Laporan Kegiatan</h2>
      <button id="closeUploadLaporan" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <div class="p-5">
      <input type="hidden" id="laporanTargetId" />
      <label class="block text-sm font-medium">Pilih file PDF</label>
      <input type="file" id="laporanFileInput" accept="application/pdf" class="mt-2 w-full" />
      <p class="text-xs text-gray-500 mt-2">Setelah upload, file akan disimpan ke Google Drive dan direferensikan di sheet.</p>
    </div>
    <div class="flex justify-end p-5 space-x-2">
      <button id="cancelUploadLaporan" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
      <button id="confirmUploadLaporan" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Upload</button>
    </div>
  </div>
</div>

<!-- Modal Catatan -->
<div id="modalCatatan" class="hidden fixed inset-0 flex justify-center items-start overflow-y-auto z-50">
    <div class="absolute inset-0 backdrop-blur-sm bg-black/30"></div>
    <div class="mt-20 bg-white rounded-lg shadow-lg w-full max-w-md transform transition-all duration-300 scale-95 opacity-0 z-10" id="modalCatatanBox" style="backdrop-filter: blur(6px);">
        <div class="p-5 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700" id="modalCatatanTitle">Catatan</h2>
            <button id="closeCatatan" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <div class="p-5">
            <textarea id="inputCatatan" class="w-full border-gray-300 rounded-lg p-2 text-gray-700 dark:text-gray-700" rows="4" placeholder="Tulis alasan..." required></textarea>
        </div>
        <div class="flex justify-end space-x-2 p-5">
            <button id="cancelCatatan" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Batal</button>
            <button id="confirmCatatan" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                <span class="spinner hidden mr-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                Kirim
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit Pengajuan -->
<div id="modalEditPengajuan" class="hidden fixed inset-0 flex justify-center items-start overflow-y-auto z-50">
  <div class="absolute inset-0 backdrop-blur-sm bg-black/30"></div>

  <div id="modalEditPengajuanBox"
       class="mt-16 bg-white rounded-lg shadow-lg w-full max-w-lg
              transform transition-all duration-300 scale-95 opacity-0 z-10">

    <div class="p-5 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold dark:text-gray-800">Edit Pengajuan</h2>
      <button id="closeEditPengajuan" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>

    <div class="p-5 space-y-3 dark:text-gray-800">
      <input type="hidden" id="editID">

      <input id="editNama" class="w-full border px-3 py-2 rounded" disabled>
      <input id="editBidang" class="w-full border px-3 py-2 rounded">
      <input id="editTujuan" class="w-full border px-3 py-2 rounded">
      <textarea id="editKeperluan" class="w-full border px-3 py-2 rounded"></textarea>
      <input id="editBiaya" type="number" class="w-full border px-3 py-2 rounded">

      <label class="text-sm font-medium dark:text-gray-800">Ganti Proposal (opsional)</label>
      <input type="file" id="editProposal" accept="application/pdf">
    </div>

    <div class="flex justify-end gap-2 p-5">
      <button id="cancelEditPengajuan" class="px-4 py-2 bg-gray-400 rounded">Batal</button>
      <button id="saveEditPengajuan" class="px-4 py-2 bg-blue-600 text-white rounded">
        Simpan
      </button>
    </div>
  </div>
</div>

<!-- Modal Preview PDF -->
<div id="modalPreviewPdf"
     class="fixed inset-0 z-50 bg-black bg-opacity-60 hidden flex items-center justify-center">

    <div class="bg-white w-[95vw] h-[95vh] rounded-lg shadow-lg flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex justify-between items-center px-4 py-2 border-b">
            <span id="previewFileName" class="font-semibold truncate"></span>
            <div class="flex gap-2">
                <a id="previewDownloadBtn"
                   target="_blank"
                   class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                    Download
                </a>
                <button id="closePreviewPdf"
                        class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                    Tutup
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1">
            <iframe
                id="previewPdfFrame"
                class="w-full h-full border-0"
                allow="autoplay"
            ></iframe>
        </div>

    </div>
</div>

<!-- Modal Keperluan -->
<div id="modalKeperluan"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800
                rounded-lg shadow-lg
                max-w-lg w-full mx-4 p-5">

        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-800">
                Detail Keperluan
            </h3>
            <button id="closeModalKeperluan"
                    class="text-gray-500 hover:text-gray-700 text-xl">
                &times;
            </button>
        </div>

        <div id="modalKeperluanContent"
             class="text-gray-700 dark:text-gray-800 text-sm whitespace-pre-line">
        </div>

        <div class="text-right mt-4">
            <button id="closeModalKeperluanBtn"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                Tutup
            </button>
        </div>
    </div>
</div>

<!--konfirmasi delete-->

<div id="modalConfirmDelete"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 dark:text-gray-700">
    <div class="bg-white dark:bg-slate-800 rounded-lg w-full max-w-md p-5 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-800 mb-2">
            Konfirmasi Hapus
        </h3>

        <p class="text-sm text-gray-600 dark:text-gray-700 mb-4">
            Apakah kamu yakin ingin menghapus pengajuan ini?<br>
            <span class="text-red-600 font-semibold">
                Pengajuan akan terhapus permanen.
            </span>
        </p>

        <div class="flex justify-end gap-2">
            <button id="cancelDeleteBtn"
                class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">
                Batal
            </button>

            <button id="confirmDeleteBtn"
                class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>


<!-- Toast -->
<div id="toast" class="fixed bottom-5 right-5 hidden px-4 py-3 rounded-lg shadow-lg text-white text-sm z-[100]"></div>

<!-- Optional small CSS customizations for premium look -->
<style>
    /* subtle page button focus */
    #pageButtons button:focus { outline: 2px solid rgba(59,130,246,0.35); outline-offset: 2px; }
    /* modal enter animation */
    #modalAddBox.opacity-100, #modalCatatanBox.opacity-100, #modalUploadLaporanBox.opacity-100 { transform: translateY(0) scale(1); }
</style>

<script>
document.getElementById('applyFilterBtn').addEventListener('click', () => {
    appliedFilters.status = document.getElementById('filterStatus').value || '';
    appliedFilters.bulan  = document.getElementById('filterBulan').value || '';
    appliedFilters.tahun  = document.getElementById('filterTahun').value || '';
    appliedFilters.bidang = document.getElementById('filterBidang').value || '';

    currentPage = 1;
    window.__alreadyLoadedOnce = false;
    loadPengajuan();
});
</script>


<script>
const userRole = "{{ session('user.role') }}";
const username = "{{ session('user.username') }}";
const API_URL = "{{ config('app.api_url') }}"; // direct Apps Script for mutations
const DATA_URL = "{{ url('pengajuan/data') }}"; // Laravel proxy for server-side pagination

// Helper: convert File ke Base64
function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => {
      const base64 = reader.result.split(',')[1]; // IMPORTANT
      resolve(base64);
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

// Toast helper
function showToast(msg, success = true) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = `fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-white text-sm z-[100] ${success ? 'bg-green-600' : 'bg-red-600'}`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// Modal helpers
function openModal(id, box) {
    const m = document.getElementById(id), b = document.getElementById(box);
    m.classList.remove('hidden');
    setTimeout(() => { b.classList.remove('opacity-0', 'scale-95'); b.classList.add('opacity-100', 'scale-100'); }, 10);
}
function closeModal(id, box) {
    const m = document.getElementById(id), b = document.getElementById(box);
    b.classList.add('opacity-0', 'scale-95');
    setTimeout(() => m.classList.add('hidden'), 150);
}

// Format tanggal (same as before)
function formatTanggal(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const tgl = d.getDate().toString().padStart(2,'0');
    const bln = bulan[d.getMonth()];
    const thn = d.getFullYear();
    const jam = d.toLocaleTimeString('id-ID', { hour12: false });
    return `<div class='flex flex-col items-center'>
        <span>${tgl} ${bln} ${thn}</span>
        <span class='text-xs text-gray-500'>${jam}</span>
    </div>`;
}

/* =========================
   Pagination state (server-side)
   ========================= */
let currentPage = 1;
let totalPages = 1;
let perPage = Number(document.getElementById('perPage').value || 10);
let currentData = []; // data for current page
let filterDebounceTimer = null;
let appliedFilters = {
    status: '',
    bulan: '',
    tahun: '',
    bidang: ''
};
let deleteTargetId = null;
let deleteTargetBtn = null;



// render table from server data (updated with new columns)
function renderTableFromServer(data, page) {
     tableData = data; 
    const tbody = document.getElementById('dataBody');
    tbody.innerHTML = '';

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="15" class="text-center text-gray-500 py-4">Belum ada data</td></tr>`;
        return;
    }

    data.forEach((row, i) => {
        const isPembuat = String(row.Nama || "").trim() === username;
        const canApprove = ['Ketua', 'Sekretaris'].includes(userRole);
        const showDelete = String(row.Status).trim() === 'Menunggu' && isPembuat;
        const statusDisetujui = String(row.Status).trim() === 'Disetujui';
        const canEdit = String(row.Status).trim() === 'Menunggu' && isPembuat;


        // proposal link (if present)
        const proposalId = row.LampiranProposal || row.lampiranproposal || '';
        const proposalName ='Lampiran';
        const proposalHtml = proposalId
        ? `
            <button
            class="text-blue-600 underline text-sm hover:text-blue-800 btn-preview-proposal"
            data-id="${proposalId}"
            data-name="${proposalName}">
            ${proposalName}
            </button>
        `
        : `<span class="text-xs text-gray-400">-</span>`;


        // laporan link or upload button (if disetujui & pembuat)
        const laporanId = row.LampiranLaporan || row.lampiranlaporan || '';
        let laporanHtml = `<span class="text-xs text-gray-400">-</span>`;
        const laporanName = 'Lampiran';

        if (laporanId) {
            laporanHtml = `
                <div class="flex flex-col items-center gap-1">
                    <button
                        class="text-blue-600 underline text-sm hover:text-blue-800 btn-preview-laporan"
                        data-id="${laporanId}"
                        data-name="${laporanName}">
                        ${laporanName}
                    </button>

                    ${
                        statusDisetujui && isPembuat
                        ? `<button
                                class="btn-upload-laporan text-xs text-orange-600 hover:text-orange-800 underline"
                                data-id="${row.ID}">
                                Ganti Lampiran
                        </button>`
                        : ''
                    }
                </div>
            `;
        } 
        else if (statusDisetujui && isPembuat) {
            laporanHtml = `
                <button
                    class="btn-upload-laporan px-3 py-1 text-sm bg-yellow-500 hover:bg-yellow-600 text-white rounded"
                    data-id="${row.ID}">
                    Upload Laporan
                </button>
            `;
        }


        // AktualBiaya display / edit (only Bendahara can edit)
        const aktual = row.AktualBiaya !== undefined && row.AktualBiaya !== null && row.AktualBiaya !== '' ? Number(row.AktualBiaya) : '';
        const dikembalikan = row.Dikembalikan !== undefined && row.Dikembalikan !== null && row.Dikembalikan !== '' ? Number(row.Dikembalikan) : '';

        let aktualHtml = `<span class="text-xs text-gray-400">-</span>`;
      
        if (userRole === 'Bendahara' && statusDisetujui) {
            aktualHtml = `
                <div class="flex gap-2 items-center justify-center">
                    <input
                        type="number"
                        class="input-aktual w-28 text-right px-2 py-1 border rounded"
                        data-id="${row.ID}"
                        value="${aktual !== '' ? aktual : ''}"
                        placeholder="0"
                    />
                    <button
                        class="btn-save-aktual px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm"
                        data-id="${row.ID}">
                        Simpan
                    </button>
                </div>
            `;
        } else if (aktual !== '') {
            aktualHtml = `Rp${Number(aktual).toLocaleString()}`;
        }


        const aksiParts = [];
        if (canEdit){
            aksiParts.push(`<button class="btn-edit bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded mb-1 w-full" data-id="${row.ID}">Edit</button>`);
        }
        if (canApprove && String(row.Status).trim() === 'Menunggu') {
            aksiParts.push(`<button class="btn-approve bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded mb-1 w-full" data-id="${row.ID}">Setujui</button>`);
            aksiParts.push(`<button class="btn-reject bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded mb-1 w-full" data-id="${row.ID}">Tolak</button>`);
        }
        if (showDelete) {
            aksiParts.push(`<button class="btn-delete bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded mt-1 w-full" data-id="${row.ID}">Hapus</button>`);
        }

        const aksi = `<div class="flex flex-col items-center">${aksiParts.join('')}</div>`;

        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 opacity-0 translate-y-3 transition-all duration-500";

        const indexNumber = (page - 1) * perPage + (i + 1);

        tr.innerHTML = `
            <td class="p-2 text-center">${indexNumber}</td>
            <td class="p-2">${escapeHtml(row.Nama || '')}</td>
            <td class="p-2">${escapeHtml(row.Bidang || '')}</td>
            <td class="p-2">${escapeHtml(row.Tujuan || '')}</td>
            <td class="p-2 w-[200px] max-w-[200px] align-top">
                <div class="break-words">
                    ${escapeHtml(truncateText(row.Keperluan || '', 25))}
                </div>


                ${
                    (row.Keperluan || '').length > 25
                    ? `<button
                        class="text-blue-600 text-xs hover:underline mt-1"
                        onclick="openKeperluanModal('${escapeJs(row.Keperluan)}')">
                        Baca selengkapnya
                    </button>`
                    : ''
                }
            </td>


            <td class="p-2 text-right">Rp${parseInt(row.Biaya || 0).toLocaleString()}</td>

            <td class="p-2 text-center">${proposalHtml}</td>
            <td class="p-2 text-center">${aktualHtml}</td>
            <td class="p-2 text-right"> ${dikembalikan !== '' ? 'Rp' + Number(dikembalikan).toLocaleString() : '-'}</td>
            <td class="p-2 text-center">${laporanHtml}</td>

            <td class="p-2 text-center">${escapeHtml(row.Status || '')}</td>
            <td class="p-2 text-center text-gray-600">${escapeHtml(row.Catatan || '-')}</td>
            <td class="p-2 text-center">${escapeHtml(row.Approver || '-')}</td>
            <td class="p-2 text-center">${formatTanggal(row.Tanggal)}</td>
            <td class="p-2 text-center">${aksi}</td>
        `;

        tbody.appendChild(tr);

        setTimeout(() => {
            tr.classList.remove('opacity-0', 'translate-y-3');
            tr.classList.add('opacity-100', 'translate-y-0');
        }, 50 * i);
    });

    setEventHandlers();
}

// render pagination buttons (unchanged)
function renderPaginationButtons() {
    const container = document.getElementById('pageButtons');
    container.innerHTML = '';

    const maxButtons = 7;
    let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let end = start + maxButtons - 1;
    if (end > totalPages) {
        end = totalPages;
        start = Math.max(1, end - maxButtons + 1);
    }

    if (start > 1) {
        const firstBtn = document.createElement('button');
        firstBtn.textContent = 1;
        firstBtn.className = "px-3 py-1 border rounded bg-white hover:bg-gray-100";
        firstBtn.onclick = () => { currentPage = 1; loadPengajuan(); };
        container.appendChild(firstBtn);

        if (start > 2) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.className = 'px-2 text-gray-400';
            container.appendChild(dots);
        }
    }

    for (let i = start; i <= end; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = "px-3 py-1 border rounded " + (i === currentPage ? "bg-blue-600 text-white" : "bg-white hover:bg-gray-100");
        btn.onclick = () => { currentPage = i; loadPengajuan(); };
        container.appendChild(btn);
    }

    if (end < totalPages) {
        if (end < totalPages - 1) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.className = 'px-2 text-gray-400';
            container.appendChild(dots);
        }
        const lastBtn = document.createElement('button');
        lastBtn.textContent = totalPages;
        lastBtn.className = "px-3 py-1 border rounded bg-white hover:bg-gray-100";
        lastBtn.onclick = () => { currentPage = totalPages; loadPengajuan(); };
        container.appendChild(lastBtn);
    }

    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;
}

// Load data from server (via Laravel controller proxy)
async function loadPengajuan() {
    const tbody = document.getElementById('dataBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="15" class="text-center text-gray-500 py-4">
                Memuat data...
            </td>
        </tr>
    `;

    try {
        perPage = Number(document.getElementById('perPage').value) || 10;

        const params = new URLSearchParams({
            page: currentPage,
            limit: perPage,
            status: appliedFilters.status,
            bulan: appliedFilters.bulan,
            tahun: appliedFilters.tahun,
            bidang: appliedFilters.bidang
        });

        const url = `${DATA_URL}?${params.toString()}`;

        const res = await fetch(url, { cache: 'no-store' });
        const json = await res.json();

        if (json && json.success) {
            currentData = json.data || [];
            totalPages =
                json.totalPages ||
                Math.max(1, Math.ceil((json.total || 0) / perPage));

            // AUTO GO LAST PAGE ON FIRST LOAD
            if (!window.__alreadyLoadedOnce) {
                window.__alreadyLoadedOnce = true;
                currentPage = totalPages;
                return loadPengajuan();
            }

            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
                return loadPengajuan();
            }

            renderTableFromServer(currentData, json.page || currentPage);
            renderPaginationButtons();

            document.getElementById('statusInfo').textContent =
                `Halaman ${json.page || currentPage} dari ${totalPages} • Total: ${json.total ?? '-'}`;
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="15" class="text-center text-gray-500 py-4">
                        Belum ada data
                    </td>
                </tr>
            `;
        }
    } catch (err) {
        tbody.innerHTML = `
            <tr>
                <td colspan="15" class="text-center text-red-500 py-4">
                    Gagal memuat data
                </td>
            </tr>
        `;
        console.error(err);
    }
}


/* ========= Event handlers (approve/reject/delete/upload/update aktual) ========= */
function setEventHandlers() {
    // approve/reject/delete existing handlers
    document.querySelectorAll('.btn-approve').forEach(btn => btn.onclick = () => openCatatanModal(btn.dataset.id, 'approve'));
    document.querySelectorAll('.btn-reject').forEach(btn => btn.onclick = () => openCatatanModal(btn.dataset.id, 'reject'));
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.onclick = () => {
            deleteTargetId = btn.dataset.id;
            deleteTargetBtn = btn;

            const modal = document.getElementById('modalConfirmDelete');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {btn.onclick = () => {
        const id = btn.dataset.id;
        const row = tableData.find(r => String(r.ID) === String(id));
        if (!row) return;
        editID.value        = row.ID;
        editNama.value      = row.Nama;
        editBidang.value    = row.Bidang;
        editTujuan.value    = row.Tujuan;
        editKeperluan.value = row.Keperluan;
        editBiaya.value     = row.Biaya;
        openModal('modalEditPengajuan', 'modalEditPengajuanBox');
    };
});

    document.querySelectorAll('.btn-upload-laporan').forEach(btn => {
    btn.onclick = () => {
        const id = btn.dataset.id;
        laporanTargetId.value = id;

        // reset file input supaya bisa overwrite file lama
        document.getElementById('laporanFileInput').value = '';

        openModal('modalUploadLaporan', 'modalUploadLaporanBox');
    };
});


    // preview laporan
    document.querySelectorAll('.btn-preview-laporan').forEach(btn => {
        btn.onclick = () => openPdfPreview(btn.dataset.id, btn.dataset.name);
    });

    // preview proposal
    document.querySelectorAll('.btn-preview-proposal').forEach(btn => {
        btn.onclick = () => openPdfPreview(btn.dataset.id, btn.dataset.name);
    });

    // save aktual (bendahara)
    document.querySelectorAll('.btn-save-aktual').forEach(btn => btn.onclick = async () => {
        const id = btn.dataset.id;
        const input = document.querySelector(`.input-aktual[data-id="${id}"]`);
        const val = input ? input.value.trim() : '';
        if (val === '') return showToast('Isi nilai aktual terlebih dahulu', false);
        const num = Number(val);
        if (isNaN(num)) return showToast('Nilai harus numeric', false);

        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
        try {
            const payload = { action: 'updateAktualBiaya', ID: id, AktualBiaya: num, username };
            const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify(payload) });
            const json = await res.json();
            if (json.success) {
                showToast('AktualBiaya diperbarui');
                loadPengajuan();
            } else {
                showToast(json.message || 'Gagal update', false);
            }
        } catch (e) {
            console.error(e);
            showToast('Kesalahan jaringan', false);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan';
        }
    });

    // confirm upload laporan modal
    document.getElementById('confirmUploadLaporan').onclick = async () => {
        const fileInput = document.getElementById('laporanFileInput');
        const file = fileInput.files[0];
        const id = laporanTargetId.value;
        if (!file) return showToast('Pilih file laporan terlebih dahulu', false);
        if (!id) return showToast('Target ID tidak ditemukan', false);
        if (file.type !== 'application/pdf') return showToast('Hanya PDF yang diperbolehkan', false);

        const btn = document.getElementById('confirmUploadLaporan');
        btn.disabled = true; btn.textContent = 'Mengunggah...';

        try {
            const base64 = await fileToBase64(file);
            const payload = {
                action: 'uploadFile',
                ID: id,
                fileName: file.name,
                fileBase64: base64,                                                     
                mimeType: file.type,
                fileType: 'laporan',
                username
            };
            const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify(payload) });
            const json = await res.json();
            if (json.success) {
                showToast('Laporan berhasil diupload');
                closeModal('modalUploadLaporan', 'modalUploadLaporanBox');
                document.getElementById('laporanFileInput').value = '';
                loadPengajuan();
            } else {
                showToast(json.message || 'Gagal upload', false);
            }
        } catch (e) {
            console.error(e);
            showToast('Kesalahan jaringan', false);
        } finally {
            btn.disabled = false; btn.textContent = 'Upload';
        }
    };

    // close upload modal
    document.getElementById('closeUploadLaporan').onclick = document.getElementById('cancelUploadLaporan').onclick = () => {
        closeModal('modalUploadLaporan', 'modalUploadLaporanBox');
        document.getElementById('laporanFileInput').value = '';
    };

    // close Preview
    document.getElementById('closePreviewPdf').onclick = () => {
    document.getElementById('previewPdfFrame').src = '';
    document.getElementById('modalPreviewPdf').classList.add('hidden');
    document.body.classList.remove('overflow-hidden'); // 🔓 unlock
    };

    //<!-- SIMPAN HASIL EDIT 
    document.getElementById('saveEditPengajuan').onclick = async () => {
    const id = editID.value;
    if (!id) return showToast('ID pengajuan tidak ditemukan', false);

    const fd = {
        action: 'editPengajuan', // nama function di Apps Script
        ID: id,
        Nama: editNama.value,
        Bidang: editBidang.value,
        Tujuan: editTujuan.value,
        Keperluan: editKeperluan.value,
        Biaya: editBiaya.value,
        username // opsional, bisa dipakai di Apps Script
    };

    // Jika ada file proposal, convert ke base64
    if (editProposal.files.length) {
        try {
            const base64 = await fileToBase64(editProposal.files[0]);
            fd.fileBase64 = base64;
            fd.fileName   = editProposal.files[0].name;
            fd.mimeType   = editProposal.files[0].type;
            fd.fileType   = 'proposal';
        } catch (e) {
            console.error(e);
            return showToast('Gagal membaca file proposal', false);
        }
    }

    const btn = document.getElementById('saveEditPengajuan');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify(fd)
        });

        // Ambil response JSON
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Pengajuan berhasil diperbarui');
            closeModal('modalEditPengajuan', 'modalEditPengajuanBox');
            loadPengajuan(); // reload data table / pagination
            editProposal.value = ''; // reset file input
        } else {
            showToast(json.message || 'Gagal update pengajuan', false);
        }
    } catch (err) {
        console.error(err);
        showToast('Kesalahan jaringan', false);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
};

// === CLOSE MODAL EDIT PENGAJUAN ===
const closeEditBtn = document.getElementById('closeEditPengajuan');
const cancelEditBtn = document.getElementById('cancelEditPengajuan');
if (closeEditBtn) {
    closeEditBtn.onclick = () => {
        closeModal('modalEditPengajuan', 'modalEditPengajuanBox');
    };
}
if (cancelEditBtn) {
    cancelEditBtn.onclick = () => {
        closeModal('modalEditPengajuan', 'modalEditPengajuanBox');
    };
}


document.getElementById('cancelDeleteBtn').onclick = () => {
    const modal = document.getElementById('modalConfirmDelete');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    deleteTargetId = null;
    deleteTargetBtn = null;
};

document.getElementById('confirmDeleteBtn').onclick = () => {
    if (!deleteTargetId || !deleteTargetBtn) return;

    const modal = document.getElementById('modalConfirmDelete');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    handleDelete(deleteTargetId, deleteTargetBtn);

    deleteTargetId = null;
    deleteTargetBtn = null;
};

}

function truncateText(text, limit = 25) {
    if (!text) return '';
    text = String(text);
    return text.length > limit
        ? text.slice(0, limit) + '...'
        : text;
}


function openKeperluanModal(text) {
    const modal = document.getElementById('modalKeperluan');
    const content = document.getElementById('modalKeperluanContent');

    content.textContent = text;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeKeperluanModal() {
    const modal = document.getElementById('modalKeperluan');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('closeModalKeperluan')
    ?.addEventListener('click', closeKeperluanModal);

document.getElementById('closeModalKeperluanBtn')
    ?.addEventListener('click', closeKeperluanModal);


// Modal tambah
btnAdd.onclick = () => openModal('modalAdd', 'modalAddBox');
closeAdd.onclick = cancelAdd.onclick = () => closeModal('modalAdd', 'modalAddBox');

// Form add (with proposal upload after creating row)
formAdd.onsubmit = async e => {
    e.preventDefault();
    const bidang = addBidang.value.trim(), tujuan = addTujuan.value.trim(), keperluan = addKeperluan.value.trim(), biaya = addBiaya.value.trim();
    if (!bidang || !tujuan || !keperluan || !biaya) return showToast('Semua field wajib diisi!', false);
    const btn = submitAdd, spin = btn.querySelector('.spinner');
    spin.classList.remove('hidden'); btn.disabled = true;

    try {
        // 1) create pengajuan -> get ID
        const data = { action: 'addPengajuan', Nama: addNama.value, Bidang: bidang, Tujuan: tujuan, Keperluan: keperluan, Biaya: biaya };
        const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify(data) });
        const json = await res.json();

        if (!json.success) {
            showToast(json.message || 'Gagal membuat pengajuan', false);
            return;
        }

        const id = json.id || (json.data && json.data.id) || null;
        if (!id) {
            showToast('ID pengajuan tidak diterima dari server', false);
            return;
        }

        // 2) if proposal selected -> upload file via uploadFile action
        const proposalInput = document.getElementById('addProposalFile');
        const file = proposalInput.files[0];
        if (file) {
            if (file.type !== 'application/pdf') {
                showToast('Lampiran proposal harus PDF', false);
            } else {
                try {
                    const base64 = await fileToBase64(file);
                    const upPayload = {
                        action: 'uploadFile',
                        ID: id,
                        fileName: file.name,
                        fileBase64: base64, 
                        mimeType: file.type,
                        fileType: 'proposal',
                        username: username
                    };
                    const upRes = await fetch(API_URL, { method: 'POST', body: JSON.stringify(upPayload) });
                    const upJson = await upRes.json();
                    if (!upJson.success) {
                        // warn but still consider pengajuan created
                        showToast('Pengajuan dibuat tetapi upload proposal gagal: ' + (upJson.message || ''), false);
                    }
                } catch (err) {
                    console.error(err);
                    showToast('Gagal upload proposal', false);
                }
            }
        }

        // success flow
        e.target.reset();
        closeModal('modalAdd', 'modalAddBox');
        showToast('Pengajuan berhasil dikirim!');
        currentPage = totalPages;
        window.__alreadyLoadedOnce = true;
        loadPengajuan();
    } catch (err) {
        console.error(err);
        showToast('Terjadi kesalahan jaringan', false);
    } finally {
        spin.classList.add('hidden'); btn.disabled = false;
    }
};

// Catatan approve/reject (unchanged)
let currentAction = '', currentId = '';
function openCatatanModal(id, action) {
    currentAction = action; currentId = id;
    modalCatatanTitle.textContent = action === 'approve' ? 'Catatan Persetujuan' : 'Catatan Penolakan';
    inputCatatan.value = ''; openModal('modalCatatan', 'modalCatatanBox');
}
closeCatatan.onclick = cancelCatatan.onclick = () => closeModal('modalCatatan', 'modalCatatanBox');

confirmCatatan.onclick = async () => {
    const catatan = inputCatatan.value.trim();
    if (!catatan) return showToast('Isi catatan dulu!', false);
    const btn = confirmCatatan, spin = btn.querySelector('.spinner');
    spin.classList.remove('hidden'); btn.disabled = true;
    try {
        const data = { action: currentAction === 'approve' ? 'approvePengajuan' : 'rejectPengajuan', ID: currentId, Approver: userRole, Catatan: catatan };
        const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify(data) });
        const json = await res.json();
        if (json.success) {
            closeModal('modalCatatan', 'modalCatatanBox');
            showToast(currentAction === 'approve' ? 'Disetujui ✅' : 'Ditolak ❌');
            loadPengajuan();
        } else showToast(json.message || 'Gagal menyimpan catatan', false);
    } catch (err) {
        console.error(err);
        showToast('Kesalahan jaringan', false);
    } finally {
        spin.classList.add('hidden'); btn.disabled = false;
    }
};

//fungsi preview
function openPdfPreview(fileId, fileName = 'Dokumen.pdf') {
    const modal = document.getElementById('modalPreviewPdf');
    const frame = document.getElementById('previewPdfFrame');
    const name = document.getElementById('previewFileName');
    const download = document.getElementById('previewDownloadBtn');

    name.textContent = fileName;

    frame.src = `https://drive.google.com/file/d/${fileId}/preview`;
    download.href = `https://drive.google.com/uc?export=download&id=${fileId}`;

    document.body.classList.add('overflow-hidden'); // 🔒 lock background
    modal.classList.remove('hidden');
}



// Delete
async function handleDelete(id, btn) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin inline-block"></span>';
    btn.disabled = true;
    try {
        const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'deletePengajuan', ID: id }) });
        const json = await res.json();
        if (json.success) {
            showToast('Pengajuan dihapus');
            loadPengajuan();
        } else showToast(json.message || 'Gagal menghapus', false);
    } catch (err) {
        console.error(err);
        showToast('Kesalahan jaringan', false);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// Upload laporan modal input references
const laporanTargetId = document.getElementById('laporanTargetId');

// Pagination controls events
document.getElementById('prevPage').addEventListener('click', () => {
    if (currentPage > 1) { currentPage--; loadPengajuan(); }
});
document.getElementById('nextPage').addEventListener('click', () => {
    if (currentPage < totalPages) { currentPage++; loadPengajuan(); }
});
document.getElementById('perPage').addEventListener('change', () => {
    perPage = Number(document.getElementById('perPage').value) || 10;
    currentPage = 1; // reset to first
    loadPengajuan();
});

// keyboard shortcuts: left/right for prev/next
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') document.getElementById('prevPage').click();
    if (e.key === 'ArrowRight') document.getElementById('nextPage').click();
});

// initial load
loadPengajuan();

// small helper to escape HTML
function escapeHtml(str) {
  if (!str && str !== 0) return '';
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function escapeJs(text) {
    return String(text)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n');
}

</script>
@endsection

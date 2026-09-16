<!-- Modal Catatan (untuk Setujui / Tolak) -->
<div id="catatanModal" class="hidden fixed inset-0 z-50 flex items-start justify-center overflow-y-auto">
  <div class="relative w-full max-w-md mt-20 transition-all duration-300 transform scale-95 opacity-0"
       id="catatanModalContent">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full border border-gray-200">
      <h3 class="text-lg font-semibold mb-4">Catatan Persetujuan / Penolakan</h3>
      <form id="catatanForm" onsubmit="submitCatatan(event)">
        @csrf
        <input type="hidden" name="ID" id="catatanID">
        <input type="hidden" name="Aksi" id="catatanAksi">

        <label class="block mb-1 text-sm font-semibold text-gray-700">Catatan</label>
        <textarea name="Catatan" id="catatanText"
                  class="w-full border p-2 rounded mb-4" placeholder="Tulis alasan atau catatan singkat..." required></textarea>

        <div class="flex justify-end">
          <button type="button" onclick="closeCatatanModal()"
                  class="px-4 py-2 bg-gray-300 rounded-lg mr-2 hover:bg-gray-400 transition">
            Batal
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <span id="catatanSpinner" class="hidden animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
            Kirim
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCatatanModal(id, aksi) {
  document.getElementById('catatanID').value = id;
  document.getElementById('catatanAksi').value = aksi;
  const modal = document.getElementById('catatanModal');
  const content = document.getElementById('catatanModalContent');
  modal.classList.remove('hidden');
  setTimeout(() => {
    content.classList.remove('opacity-0', 'scale-95');
    content.classList.add('opacity-100', 'scale-100');
  }, 50);
}

function closeCatatanModal() {
  const modal = document.getElementById('catatanModal');
  const content = document.getElementById('catatanModalContent');
  content.classList.add('opacity-0', 'scale-95');
  setTimeout(() => modal.classList.add('hidden'), 300);
}

async function submitCatatan(event) {
  event.preventDefault();
  const form = event.target;
  const spinner = document.getElementById('catatanSpinner');
  spinner.classList.remove('hidden');

  const data = Object.fromEntries(new FormData(form).entries());
  const res = await fetch("{{ env('API_URL') }}", {
    method: 'POST',
    body: new URLSearchParams({
      action: data.Aksi === 'setujui' ? 'approvePengajuan' : 'rejectPengajuan',
      ID: data.ID,
      Approver: "{{ session('user.username') }}",
      Catatan: data.Catatan
    })
  });

  const result = await res.json();
  spinner.classList.add('hidden');

  if (result.status === 'success') {
    closeCatatanModal();
    showToast(`Pengajuan berhasil ${data.Aksi === 'setujui' ? 'disetujui' : 'ditolak'}`, true);
    setTimeout(() => loadPengajuan(), 500);
  } else {
    showToast('Gagal memproses tindakan.', false);
  }
}
</script>

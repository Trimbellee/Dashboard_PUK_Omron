<!-- Modal Tambah Pengajuan -->
<div id="addModal" class="hidden fixed inset-0 z-50 flex items-start justify-center overflow-y-auto">
  <div class="relative w-full max-w-lg mt-10 transition-all duration-300 transform scale-95 opacity-0"
       id="addModalContent">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full border border-gray-200">
      <h3 class="text-lg font-semibold mb-4">Tambah Pengajuan</h3>
      <form id="addForm" onsubmit="submitPengajuan(event)">
        @csrf

        {{-- Nama otomatis --}}
        <label class="block mb-1 text-sm font-semibold text-gray-700">Nama</label>
        <input type="text" name="Nama" value="{{ session('user.username') }}"
               class="w-full border p-2 rounded mb-3 bg-gray-100" readonly>

        {{-- Bidang dropdown --}}
        <label class="block mb-1 text-sm font-semibold text-gray-700">Bidang</label>
        <select name="Bidang" class="w-full border p-2 rounded mb-3" required>
          <option value="" disabled selected>Pilih Bidang</option>
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

        <label class="block mb-1 text-sm font-semibold text-gray-700">Tujuan</label>
        <input type="text" name="Tujuan" placeholder="Tujuan"
               class="w-full border p-2 rounded mb-3" required>

        <label class="block mb-1 text-sm font-semibold text-gray-700">Keperluan</label>
        <textarea name="Keperluan" placeholder="Keperluan"
                  class="w-full border p-2 rounded mb-3" required></textarea>

        <label class="block mb-1 text-sm font-semibold text-gray-700">Biaya</label>
        <input type="number" name="Biaya" placeholder="Biaya"
               class="w-full border p-2 rounded mb-4" required>

        <div class="flex justify-end">
          <button type="button" onclick="closeAddModal()"
                  class="px-4 py-2 bg-gray-300 rounded-lg mr-2 hover:bg-gray-400 transition">
            Batal
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <span id="addSpinner" class="hidden animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div id="toast"
     class="hidden fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg transition-opacity duration-300">
  <span id="toastMsg"></span>
</div>

<script>
function openAddModal() {
  const modal = document.getElementById('addModal');
  const content = document.getElementById('addModalContent');
  modal.classList.remove('hidden');
  setTimeout(() => {
    content.classList.remove('opacity-0', 'scale-95');
    content.classList.add('opacity-100', 'scale-100');
  }, 50);
}

function closeAddModal() {
  const modal = document.getElementById('addModal');
  const content = document.getElementById('addModalContent');
  content.classList.add('opacity-0', 'scale-95');
  setTimeout(() => modal.classList.add('hidden'), 300);
}

function showToast(message, success = true) {
  const toast = document.getElementById('toast');
  const msg = document.getElementById('toastMsg');
  toast.classList.remove('hidden');
  toast.classList.remove('bg-green-600', 'bg-red-600');
  toast.classList.add(success ? 'bg-green-600' : 'bg-red-600');
  msg.textContent = message;
  setTimeout(() => toast.classList.add('opacity-0'), 2500);
  setTimeout(() => {
    toast.classList.add('hidden');
    toast.classList.remove('opacity-0');
  }, 3000);
}

async function submitPengajuan(event) {
  event.preventDefault();
  const form = event.target;
  const spinner = document.getElementById('addSpinner');
  spinner.classList.remove('hidden');

  const data = Object.fromEntries(new FormData(form).entries());
  const res = await fetch("{{ env('API_URL') }}", {
    method: 'POST',
    body: new URLSearchParams({
      action: 'addPengajuan',
      ...data
    })
  });

  const result = await res.json();
  spinner.classList.add('hidden');

  if (result.status === 'success') {
    form.reset();
    closeAddModal();
    showToast('Pengajuan berhasil dikirim!', true);
    setTimeout(() => loadPengajuan(), 500);
  } else {
    showToast('Gagal mengirim pengajuan.', false);
  }
}
</script>

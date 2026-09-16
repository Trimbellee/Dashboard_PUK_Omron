document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi awal
});

// ================= NIK CHECK =================
const nikInput = document.getElementById('nikKaryawan');
const checkNikBtn = document.getElementById('checkNikBtn');
const nikStatus = document.getElementById('nikStatus');
const usernameInput = document.getElementById('username');
const emailInput = document.getElementById('email');
const emailHint = document.getElementById('emailHint');

checkNikBtn.addEventListener('click', function() {
    const nik = nikInput.value.trim();

    if (!nik) {
        nikStatus.className = 'text-xs mt-1 text-yellow-600 dark:text-yellow-400';
        nikStatus.textContent = '⚠️ Silahkan masukkan NIK terlebih dahulu';
        nikStatus.classList.remove('hidden');
        return;
    }

    // Show loading
    checkNikBtn.textContent = 'Loading...';
    checkNikBtn.disabled = true;
    nikStatus.className = 'text-xs mt-1 text-gray-500';
    nikStatus.textContent = '⏳ Mengecek NIK...';
    nikStatus.classList.remove('hidden');

    fetch(`/users/anggota/${nik}`)
        .then(res => res.json())
        .then(data => {
            checkNikBtn.textContent = 'Cek';
            checkNikBtn.disabled = false;

            if (!data.success) {
                nikStatus.className = 'text-xs mt-1 text-red-600 dark:text-red-400';
                nikStatus.textContent = '❌ ' + data.message;
                usernameInput.value = '';
                emailInput.value = '';
                emailHint.classList.add('hidden');
                return;
            }

            // Auto fill username dan email
            const anggota = data.data;
            nikStatus.className = 'text-xs mt-1 text-green-600 dark:text-green-400';
            nikStatus.textContent = '✅ NIK ditemukan';

            usernameInput.value = anggota.nama_lengkap;

            if (anggota.email) {
                emailInput.value = anggota.email;
                emailHint.classList.add('hidden');
            } else {
                emailInput.value = '';
                emailHint.classList.remove('hidden');
                emailInput.focus();
            }
        })
        .catch(error => {
            checkNikBtn.textContent = 'Cek';
            checkNikBtn.disabled = false;
            nikStatus.className = 'text-xs mt-1 text-red-600 dark:text-red-400';
            nikStatus.textContent = '❌ Terjadi kesalahan server';
        });
});

// Enter key pada NIK juga trigger check
nikInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        checkNikBtn.click();
    }
});

// ================= SIGNATURE PAD =================
const canvas = document.getElementById('signatureCanvas');
let signaturePad = null;
let existingTtdUrl = null;
let hasSignatureContent = false;

function initSignaturePad() {
    if (signaturePad) return;

    signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(0,0,0,0)',
        penColor: 'rgb(17,24,39)',
        minWidth: 2,
        maxWidth: 4
    });

    signaturePad.addEventListener('beginStroke', () => {
        hasSignatureContent = true;
        document.getElementById('signatureEmptyHint').classList.add('hidden');
        document.getElementById('signatureStatus').textContent = '';
    });
}

function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    canvas.getContext('2d').scale(ratio, ratio);

    if (signaturePad) {
        const data = signaturePad.toData();
        signaturePad.clear();
        if (data && data.length > 0) {
            signaturePad.fromData(data);
        }
    }
}

function clearSignature() {
    if (!signaturePad) return;
    signaturePad.clear();
    hasSignatureContent = false;
    existingTtdUrl = null;
    document.getElementById('signatureEmptyHint').classList.remove('hidden');
    document.getElementById('signatureStatus').textContent = '';
}

function loadExistingSignature(url) {
    const img = new Image();
    img.onload = () => {
        const ctx = canvas.getContext('2d');
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const cw = canvas.width / ratio;
        const ch = canvas.height / ratio;

        ctx.clearRect(0, 0, cw, ch);

        const scale = Math.min(cw / img.width, ch / img.height) * 0.85;
        const w = img.width * scale;
        const h = img.height * scale;
        const x = (cw - w) / 2;
        const y = (ch - h) / 2;

        ctx.drawImage(img, x, y, w, h);

        hasSignatureContent = true;
        document.getElementById('signatureEmptyHint').classList.add('hidden');
        document.getElementById('signatureStatus').textContent = '✅ Tanda tangan tersimpan';
    };
    img.src = url;
}

function previewTtd(url) {
    window.open(url, '_blank');
}

window.addEventListener('resize', () => {
    if (document.getElementById('userModal').classList.contains('modal-show')) {
        resizeCanvas();
        if (existingTtdUrl) {
            loadExistingSignature(existingTtdUrl);
        }
    }
});

// ================= TRIM SIGNATURE =================
function trimSignatureCanvas(sourceCanvas) {
    const ctx = sourceCanvas.getContext('2d');
    const w = sourceCanvas.width;
    const h = sourceCanvas.height;
    const pixels = ctx.getImageData(0, 0, w, h).data;

    let top = null, bottom = null, left = null, right = null;

    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            const alpha = pixels[(y * w + x) * 4 + 3];
            if (alpha > 0) {
                if (top === null) top = y;
                bottom = y;
                if (left === null || x < left) left = x;
                if (right === null || x > right) right = x;
            }
        }
    }

    if (top === null) return sourceCanvas;

    const padding = 10;
    left = Math.max(0, left - padding);
    top = Math.max(0, top - padding);
    right = Math.min(w, right + padding);
    bottom = Math.min(h, bottom + padding);

    const trimmedW = right - left;
    const trimmedH = bottom - top;

    const trimmed = document.createElement('canvas');
    trimmed.width = trimmedW;
    trimmed.height = trimmedH;

    trimmed.getContext('2d').drawImage(
        sourceCanvas,
        left, top, trimmedW, trimmedH,
        0, 0, trimmedW, trimmedH
    );

    return trimmed;
}

// ================= MODAL =================
const userModal = document.getElementById('userModal');
const userForm = document.getElementById('userForm');
const deleteModal = document.getElementById('deleteModal');
const deleteForm = document.getElementById('deleteForm');
const tableBody = document.getElementById('tableBody');

let editMode = false;
let currentUsername = null;

function lockScroll() {
    document.body.style.overflow = 'hidden';
}

function unlockScroll() {
    document.body.style.overflow = '';
}

function openAddModal() {
    editMode = false;
    currentUsername = null;
    existingTtdUrl = null;
    hasSignatureContent = false;

    userForm.reset();
    document.getElementById('modalTitle').innerText = 'Tambah User';
    document.getElementById('nikKaryawan').readOnly = false;
    document.getElementById('nikKaryawan').style.opacity = '';
    document.getElementById('username').readOnly = false;
    document.getElementById('username').style.opacity = '';
    document.getElementById('passwordInput').placeholder = 'Password wajib diisi';
    document.getElementById('passwordHint').classList.add('hidden');
    document.getElementById('signatureStatus').textContent = '';
    document.getElementById('nikStatus').classList.add('hidden');
    document.getElementById('emailHint').classList.add('hidden');
    checkNikBtn.style.display = '';

    userModal.classList.remove('hidden');
    setTimeout(() => {
        userModal.classList.add('modal-show');
        initSignaturePad();
        resizeCanvas();
        document.getElementById('signatureEmptyHint').classList.remove('hidden');
    }, 10);
    lockScroll();
}

function openEditModal(username) {
    const row = document.querySelector(`#tableBody .user-row[data-username="${username}"]`);
    if (!row) return;

    editMode = true;
    currentUsername = username;
    existingTtdUrl = row.dataset.ttd || null;
    hasSignatureContent = false;

    document.getElementById('modalTitle').innerText = 'Edit User';
    document.getElementById('nikKaryawan').value = row.dataset.nik || '';
    document.getElementById('nikKaryawan').readOnly = true;
    document.getElementById('nikKaryawan').style.opacity = '.7';
    checkNikBtn.style.display = 'none';
    document.getElementById('nikStatus').classList.add('hidden');

    document.getElementById('username').value = row.dataset.username;
    document.getElementById('username').readOnly = true;
    document.getElementById('username').style.opacity = '.7';

    document.getElementById('email').value = row.dataset.email;
    document.getElementById('email').readOnly = false;
    document.getElementById('email').style.opacity = '';
    document.getElementById('emailHint').classList.add('hidden');

    document.getElementById('passwordInput').value = '';
    document.getElementById('passwordInput').placeholder = 'Kosongkan jika tidak diubah';
    document.getElementById('passwordHint').classList.remove('hidden');

    document.querySelector('select[name="role"]').value = row.dataset.role;

    userModal.classList.remove('hidden');
    setTimeout(() => {
        userModal.classList.add('modal-show');
        initSignaturePad();
        resizeCanvas();

        if (existingTtdUrl) {
            loadExistingSignature(existingTtdUrl);
        } else {
            document.getElementById('signatureEmptyHint').classList.remove('hidden');
            document.getElementById('signatureStatus').textContent = '';
        }
    }, 10);
    lockScroll();
}

function closeModal() {
    userModal.classList.remove('modal-show');
    unlockScroll();
    setTimeout(() => {
        userModal.classList.add('hidden');
    }, 250);
}

// ================= SUBMIT =================
userForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const spinner = userForm.querySelector('.spinner');
    spinner.classList.remove('hidden');

    const formData = new FormData(userForm);

    if (hasSignatureContent) {
        const trimmedCanvas = trimSignatureCanvas(canvas);
        trimmedCanvas.toBlob(function(blob) {
            formData.append('ttd', blob, 'ttd.png');
            sendUserForm(formData);
        }, 'image/png');
    } else {
        sendUserForm(formData);
    }
});

function sendUserForm(formData) {
    const spinner = userForm.querySelector('.spinner');

    let url;
    if (editMode) {
        url = `/users/update/${currentUsername}`;
    } else {
        url = '/users/store';
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(async response => {
        const data = await response.json();

        if (!response.ok) {
            // Buat error dengan detail dari server
            let errorMessage = 'Terjadi kesalahan';

            if (data.message) {
                if (typeof data.message === 'object') {
                    // Jika message adalah object (error validasi)
                    const errors = Object.values(data.message).flat();
                    errorMessage = errors.join(', ');
                } else {
                    errorMessage = data.message;
                }
            }

            throw new Error(errorMessage);
        }
        return data;
    })
    .then(data => {
        spinner.classList.add('hidden');

        if (!data.success) {
            showToast(data.message || 'Gagal menyimpan user', true);
            return;
        }

        if (editMode) {
            updateRow(data.data);
            showToast('User berhasil diperbarui');
        } else {
            addRow(data.user);
            showToast('User berhasil ditambahkan');
        }

        closeModal();
    })
    .catch(error => {
        spinner.classList.add('hidden');
        console.error('Error:', error);
        showToast(error.message || 'Terjadi kesalahan server', true);
    });
}

// ================= ADD ROW =================
function addRow(user) {
    const row = document.createElement('tr');
    row.className = 'user-row border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all duration-200 row-added';
    row.dataset.username = user.username;
    row.dataset.nik = user.nik_karyawan || '';
    row.dataset.email = user.email;
    row.dataset.role = user.role;
    row.dataset.ttd = user.ttd_url || '';

    row.innerHTML = `
        <td class="px-4 py-3.5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                    ${user.username.substring(0, 2).toUpperCase()}
                </div>
                <span class="font-medium text-gray-900 dark:text-white">${user.username}</span>
            </div>
        </td>
        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300 font-mono text-xs">${user.nik_karyawan || '-'}</td>
        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">${user.email}</td>
        <td class="px-4 py-3.5">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                ${user.role}
            </span>
        </td>
        <td class="px-4 py-3.5 text-center">
            ${user.ttd_url
                ? `<img src="${user.ttd_url}" class="ttd-thumb mx-auto cursor-pointer hover:scale-110 transition-transform duration-200" onclick="previewTtd('${user.ttd_url}')" alt="TTD ${user.username}">`
                : `<span class="text-gray-400 dark:text-gray-500 text-xs">—</span>`
            }
        </td>
        <td class="px-4 py-3.5 text-right">
            <div class="flex items-center justify-end gap-2">
                <button onclick="openEditModal('${user.username}')" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200 hover:scale-110" title="Edit User">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="openDeleteModal('${user.username}')" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200 hover:scale-110" title="Delete User">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </td>
    `;

    tableBody.appendChild(row);

    setTimeout(() => {
        row.classList.remove('row-added');
    }, 1500);
}

// ================= UPDATE ROW =================
function updateRow(user) {
    const row = document.querySelector(`#tableBody .user-row[data-username="${user.username}"]`);
    if (!row) return;

    row.dataset.nik = user.nik_karyawan || '';
    row.dataset.email = user.email;
    row.dataset.role = user.role;

    const cells = row.querySelectorAll('td');
    cells[1].textContent = user.nik_karyawan || '-';
    cells[2].textContent = user.email;

    const roleSpan = cells[3].querySelector('span');
    if (roleSpan) {
        roleSpan.textContent = user.role;
    }

    if (user.ttd_url) {
        row.dataset.ttd = user.ttd_url;
        cells[4].innerHTML = `
            <img src="${user.ttd_url}" class="ttd-thumb mx-auto cursor-pointer hover:scale-110 transition-transform duration-200" onclick="previewTtd('${user.ttd_url}')" alt="TTD ${user.username}">
        `;
    }

    row.classList.add('row-updated');
    setTimeout(() => {
        row.classList.remove('row-updated');
    }, 1500);
}

// ================= DELETE =================
function openDeleteModal(username) {
    currentUsername = username;
    document.getElementById('deleteUsername').textContent = username;

    deleteModal.classList.remove('hidden');
    setTimeout(() => {
        deleteModal.classList.add('modal-show');
    }, 10);
    lockScroll();
}

function closeDeleteModal() {
    deleteModal.classList.remove('modal-show');
    unlockScroll();
    setTimeout(() => {
        deleteModal.classList.add('hidden');
    }, 250);
}

deleteForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const spinner = document.getElementById('deleteSpinner');
    spinner.classList.remove('hidden');

    const row = document.querySelector(`#tableBody .user-row[data-username="${currentUsername}"]`);

    fetch(`/users/destroy/${currentUsername}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
    })
    .then(res => res.json())
    .then(data => {
        spinner.classList.add('hidden');

        if (!data.success) {
            showToast(data.message || 'Gagal menghapus user', true);
            return;
        }

        row.classList.add('row-deleted');
        setTimeout(() => {
            row.remove();
        }, 400);

        closeDeleteModal();
        showToast('User berhasil dihapus');
    })
    .catch(() => {
        spinner.classList.add('hidden');
        showToast('Terjadi kesalahan server', true);
    });
});

// ================= CLOSE BACKDROP =================
userModal.addEventListener('click', function(e) {
    if (e.target === userModal) closeModal();
});

deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) closeDeleteModal();
});

// ================= ESC =================
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});

// ================= TOAST =================
function showToast(message, isError = false) {
    const existingToast = document.querySelector('.toast-container');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = `toast-container fixed bottom-6 right-6 z-[9999] px-6 py-3.5 rounded-xl shadow-lg transform transition-all duration-500 translate-y-0 opacity-100 flex items-center gap-3 ${
        isError
            ? 'bg-red-500 text-white'
            : 'bg-gradient-to-r from-emerald-500 to-green-600 text-white'
    }`;

    toast.innerHTML = `
        <span class="text-lg">${isError ? '✕' : '✓'}</span>
        <span class="font-medium">${message}</span>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

document.addEventListener('DOMContentLoaded',()=>{
    ['userModal','deleteModal'].forEach(id=>{const el=document.getElementById(id);if(el)document.body.appendChild(el);});
});

/* ── EXPOSE UNTUK INLINE onclick DI BLADE ───── */
window.openAddModal = openAddModal;
window.openEditModal = openEditModal;
window.closeModal = closeModal;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.clearSignature = clearSignature;
window.previewTtd = previewTtd;
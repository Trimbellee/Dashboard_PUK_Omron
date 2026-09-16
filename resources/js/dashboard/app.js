/* ── CLOCK ───────────────────────────────────── */
function updateClock() {
    const now = new Date();
    document.getElementById('header-day').textContent =
        now.toLocaleDateString('id-ID', { weekday: 'long' });
    document.getElementById('header-date-time').textContent =
        now.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' })
        + ' · '
        + now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

/* ── SIDEBAR ─────────────────────────────────── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const menuBtn = document.getElementById('menuBtn');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
}

menuBtn.onclick = () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
overlay.onclick = closeSidebar;

/* ── DROPDOWN ────────────────────────────────── */
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
userMenuBtn.onclick = (e) => {
    e.stopPropagation();
    userDropdown.classList.toggle('open');
};
document.addEventListener('click', () => userDropdown.classList.remove('open'));

/* ── MODAL ───────────────────────────────────── */
const profileModal = document.getElementById('profileModal');

function openProfileModal() {
    userDropdown.classList.remove('open');
    profileModal.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeProfileModal() {
    profileModal.classList.remove('open');
    document.body.style.overflow = '';
}

profileModal.addEventListener('click', (e) => {
    if (e.target === profileModal) closeProfileModal();
});

/* ── ESC KEY ─────────────────────────────────── */
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeSidebar(); closeProfileModal(); }
});

/* ── TOAST ───────────────────────────────────── */
const toast = document.getElementById('toast');
const toastMsg = document.getElementById('toastMsg');

function showToast(message, isError = false) {
    toastMsg.textContent = message;
    toast.style.background = isError ? '#7F1D1D' : '#111827';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
}

/* ── EXPOSE UNTUK INLINE onclick DI BLADE ───── */
window.openProfileModal = openProfileModal;
window.closeProfileModal = closeProfileModal;

/* ── PROFILE FORM ────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    const btn = document.getElementById('saveProfileBtn');
    const saveText = document.getElementById('saveText');
    const spinner = document.getElementById('saveSpinner');

    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        btn.disabled = true;
        saveText.textContent = 'Menyimpan…';
        spinner.style.display = 'block';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            if (!res.ok) throw new Error('Server error');
            return res.json();
        })
        .then(data => {
            btn.disabled = false;
            saveText.textContent = 'Simpan';
            spinner.style.display = 'none';
            if (data.success) {
                closeProfileModal();
                showToast(data.message || 'Profil berhasil diperbarui');
            } else {
                showToast(data.message || 'Gagal menyimpan', true);
            }
        })
        .catch(() => {
            btn.disabled = false;
            saveText.textContent = 'Simpan';
            spinner.style.display = 'none';
            showToast('Terjadi kesalahan server', true);
        });
    });
});
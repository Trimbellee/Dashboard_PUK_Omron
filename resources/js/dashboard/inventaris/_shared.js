/**
 * Inventaris — Shared Utilities
 * Toast notifications + custom confirm dialog, used across all
 * Inventaris pages instead of the native alert()/confirm().
 */

let toastStack = null;

function ensureToastStack() {
    if (!toastStack) {
        toastStack = document.createElement('div');
        toastStack.className = 'inv-toast-stack';
        document.body.appendChild(toastStack);
    }
    return toastStack;
}

const TOAST_ICONS = { success: '✅', error: '⚠️', warning: '⚠️', info: 'ℹ️' };

/**
 * Show a toast notification.
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration ms before auto-dismiss
 */
export function showToast(message, type = 'info', duration = 4000) {
    const stack = ensureToastStack();
    const toast = document.createElement('div');
    toast.className = `inv-toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${TOAST_ICONS[type] || TOAST_ICONS.info}</span>
        <span class="toast-message"></span>
        <button type="button" class="toast-close" aria-label="Tutup">✕</button>
    `;
    toast.querySelector('.toast-message').textContent = message;

    const remove = () => {
        toast.classList.add('leaving');
        setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('.toast-close').addEventListener('click', remove);
    const timer = setTimeout(remove, duration);
    toast.addEventListener('mouseenter', () => clearTimeout(timer));

    stack.appendChild(toast);
    return toast;
}

/**
 * Show any Laravel flash messages (session('success') / session('error'))
 * rendered into a hidden #inv-flash-data element — call once per page.
 * Blade: <div id="inv-flash-data" hidden
 *             data-success="{{ session('success') }}"
 *             data-error="{{ session('error') }}"></div>
 */
export function flashSessionMessages() {
    const el = document.getElementById('inv-flash-data');
    if (!el) return;
    const { success, error } = el.dataset;
    if (success) showToast(success, 'success');
    if (error) showToast(error, 'error');
}

let confirmModal = null;

function buildConfirmModal() {
    const backdrop = document.createElement('div');
    backdrop.className = 'inv-modal-backdrop';
    backdrop.innerHTML = `
        <div class="inv-modal inv-modal-sm" role="alertdialog" aria-modal="true">
            <div class="inv-modal-header">
                <h2 class="inv-modal-title" data-role="title">Konfirmasi</h2>
            </div>
            <div class="inv-modal-body">
                <p data-role="message" style="margin:0;color:var(--text-secondary);font-size:14px;line-height:1.6;"></p>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="btn btn-ghost" data-role="cancel">Batal</button>
                <button type="button" class="btn btn-danger" data-role="confirm">Ya, Lanjutkan</button>
            </div>
        </div>
    `;
    document.body.appendChild(backdrop);
    return backdrop;
}

/**
 * Custom confirm dialog. Returns a Promise<boolean>.
 * confirmAction('Yakin ingin menghapus barang ini?', { confirmText: 'Hapus' })
 */
export function confirmAction(message, opts = {}) {
    if (!confirmModal) confirmModal = buildConfirmModal();

    const { title = 'Konfirmasi', confirmText = 'Ya, Lanjutkan', danger = true } = opts;

    confirmModal.querySelector('[data-role="title"]').textContent = title;
    confirmModal.querySelector('[data-role="message"]').textContent = message;
    const confirmBtn = confirmModal.querySelector('[data-role="confirm"]');
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `btn ${danger ? 'btn-danger' : 'btn-primary'}`;

    return new Promise((resolve) => {
        const cancelBtn = confirmModal.querySelector('[data-role="cancel"]');

        const close = (result) => {
            confirmModal.classList.remove('open');
            document.body.style.overflow = '';
            confirmBtn.removeEventListener('click', onConfirm);
            cancelBtn.removeEventListener('click', onCancel);
            confirmModal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKeydown);
            resolve(result);
        };

        const onConfirm = () => close(true);
        const onCancel = () => close(false);
        const onBackdrop = (e) => { if (e.target === confirmModal) close(false); };
        const onKeydown = (e) => { if (e.key === 'Escape') close(false); };

        confirmBtn.addEventListener('click', onConfirm);
        cancelBtn.addEventListener('click', onCancel);
        confirmModal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKeydown);

        confirmModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
}

/**
 * Intercept a form's submit so a delete/action button shows the
 * custom confirm dialog instead of window.confirm().
 * Usage: attachConfirmToForms('.js-confirm-delete', 'Yakin ingin menghapus barang ini?')
 */
export function attachConfirmToForms(selector, message, opts = {}) {
    document.querySelectorAll(selector).forEach((form) => {
        form.addEventListener('submit', async function (e) {
            if (form.dataset.confirmed === 'true') return;
            e.preventDefault();
            const ok = await confirmAction(form.dataset.confirmMessage || message, opts);
            if (ok) {
                form.dataset.confirmed = 'true';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });
}

/**
 * Client-side pagination untuk tabel yang sudah difilter.
 * Semua baris tetap di DOM (jadi search selalu menjangkau seluruh
 * data) — paginator ini cuma mengatur baris mana yang display:none
 * berdasarkan halaman aktif, dan merender kontrol halaman.
 *
 * Panggil .update(matchedRows) setiap kali hasil filter berubah.
 * Panggil .reset() untuk balik ke halaman 1 (mis. saat search berubah).
 */
export function createPaginator({ container, pageSize = 10 } = {}) {
    let currentPage = 1;
    let lastMatchedRows = [];

    function renderControls(totalPages, totalItems) {
        if (!container) return;
        container.innerHTML = '';
        if (totalItems === 0) return;

        const info = document.createElement('div');
        info.className = 'pagination-info';
        const startItem = (currentPage - 1) * pageSize + 1;
        const endItem = Math.min(currentPage * pageSize, totalItems);
        info.textContent = `Menampilkan ${startItem}–${endItem} dari ${totalItems}`;
        container.appendChild(info);

        if (totalPages <= 1) return;

        const nav = document.createElement('div');
        nav.className = 'pagination-nav';

        const makeBtn = (label, page, opts = {}) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pagination-btn' + (opts.active ? ' active' : '');
            btn.textContent = label;
            btn.disabled = !!opts.disabled;
            btn.addEventListener('click', () => goToPage(page));
            return btn;
        };

        nav.appendChild(makeBtn('‹', currentPage - 1, { disabled: currentPage === 1 }));

        const delta = 1;
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - delta && i <= currentPage + delta)) {
                nav.appendChild(makeBtn(String(i), i, { active: i === currentPage }));
            } else if (nav.lastElementChild?.textContent !== '…') {
                const span = document.createElement('span');
                span.className = 'pagination-ellipsis';
                span.textContent = '…';
                nav.appendChild(span);
            }
        }

        nav.appendChild(makeBtn('›', currentPage + 1, { disabled: currentPage === totalPages }));
        container.appendChild(nav);
    }

    function render(matchedRows) {
        const totalItems = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        currentPage = Math.min(Math.max(currentPage, 1), totalPages);

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        matchedRows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        renderControls(totalPages, totalItems);
    }

    function goToPage(page) {
        currentPage = page;
        render(lastMatchedRows);
    }

    function update(matchedRows) {
        lastMatchedRows = matchedRows;
        render(matchedRows);
    }

    function reset() {
        currentPage = 1;
    }

    return { update, reset, goToPage };
}

/**
 * Format input harga jadi pakai pemisah ribuan (titik) saat mengetik,
 * mis. 1234567789 -> 1.234.567.789. Titik dilepas lagi otomatis pas
 * form di-submit, jadi backend tetap menerima angka murni.
 */
export function attachThousandsSeparator(input) {
    if (!input) return;

    const groupThousands = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Nilai awal dari server bisa mengandung desimal, mis. "1500000.00"
    // (kolom harga di-cast decimal:2) — ambil bagian bulatnya saja,
    // karena di form ini harga selalu ditampilkan sebagai rupiah bulat.
    const initialDigits = String(input.value ?? '').split('.')[0].replace(/\D/g, '');
    input.value = groupThousands(initialDigits);

    input.addEventListener('input', function () {
        const cursorFromEnd = this.value.length - this.selectionStart;
        const digits = this.value.replace(/\D/g, '');
        this.value = groupThousands(digits);
        const newPos = Math.max(0, this.value.length - cursorFromEnd);
        this.setSelectionRange(newPos, newPos);
    });

    const form = input.closest('form');
    form?.addEventListener('submit', function () {
        input.value = input.value.replace(/\./g, '');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.LP_AGENDA;
    if (!cfg) return;

    const MONTH_NAMES = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const MONTH_NAMES_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    const BIDANG_PALETTE = [
        { dot: '#6366F1', bg: 'rgba(99,102,241,0.12)',  fg: '#6366F1' },
        { dot: '#0EA5E9', bg: 'rgba(14,165,233,0.12)',  fg: '#0284C7' },
        { dot: '#14B8A6', bg: 'rgba(20,184,166,0.12)',  fg: '#0D9488' },
        { dot: '#22C55E', bg: 'rgba(34,197,94,0.12)',   fg: '#16A34A' },
        { dot: '#EAB308', bg: 'rgba(234,179,8,0.15)',   fg: '#A16207' },
        { dot: '#F97316', bg: 'rgba(249,115,22,0.12)',  fg: '#EA580C' },
        { dot: '#EF4444', bg: 'rgba(239,68,68,0.12)',   fg: '#DC2626' },
        { dot: '#EC4899', bg: 'rgba(236,72,153,0.12)',  fg: '#DB2777' },
        { dot: '#8B5CF6', bg: 'rgba(139,92,246,0.12)',  fg: '#7C3AED' },
        { dot: '#06B6D4', bg: 'rgba(6,182,212,0.12)',   fg: '#0891B2' },
        { dot: '#64748B', bg: 'rgba(100,116,139,0.15)', fg: '#475569' },
    ];
    function bidangColor(bidang) {
        if (!bidang) return BIDANG_PALETTE[0];
        let hash = 0;
        for (let i = 0; i < bidang.length; i++) hash = (hash * 31 + bidang.charCodeAt(i)) >>> 0;
        return BIDANG_PALETTE[hash % BIDANG_PALETTE.length];
    }

    let currentDate = new Date();
    let pickerYear = currentDate.getFullYear();
    let currentEvents = [];
    let currentView = 'grid';
    let searchKeyword = '';
    let currentPage = 1;
    const AGENDA_PAGE_SIZE = 8;
    let tooltipTimeout = null;

    const toLocalDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const monthParam = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
    const escapeHtml = (s) => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    const isWeekend = (d) => { const day = d.getDay(); return day === 6 || day === 0; };

    function formatTimeDisplay(startTime, endTime) {
        let s = '--:--', e = '';
        if (startTime && String(startTime).includes(':')) s = String(startTime).slice(0,5);
        if (endTime && String(endTime).includes(':')) e = String(endTime).slice(0,5);
        if (e) return `${s} s/d ${e}`;
        return s !== '--:--' ? `${s} s/d selesai` : s;
    }

    const el = (id) => document.getElementById(id);

    function loadEvents() {
        el('lpAgendaMonthLabel').textContent = `${MONTH_NAMES[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        if (el('lpAgendaMonthDropdown').classList.contains('open')) renderMonthPicker();

        fetch(`${cfg.dataUrl}?month=${monthParam(currentDate)}`)
            .then(res => res.json())
            .then(events => { currentEvents = events; renderCurrentView(); })
            .catch(() => {
                el('lpAgendaGridBody').innerHTML = '';
                el('lpAgendaListView').innerHTML = '<div class="lp-agenda-empty"><span class="lp-agenda-empty-icon">⚠️</span>Gagal memuat agenda.</div>';
            });
    }

    function changeMonth(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        pickerYear = currentDate.getFullYear();
        currentPage = 1;
        loadEvents();
    }

    function renderCurrentView() { currentView === 'grid' ? renderGrid() : renderList(); }

    function getFilteredEvents() {
        if (!searchKeyword) return currentEvents;
        return currentEvents.filter(e =>
            e.title.toLowerCase().includes(searchKeyword) ||
            (e.location && e.location.toLowerCase().includes(searchKeyword)) ||
            (e.bidang && e.bidang.toLowerCase().includes(searchKeyword))
        );
    }

    function filteredEventsForList() {
        const y = currentDate.getFullYear(), m = currentDate.getMonth();
        const start = toLocalDateStr(new Date(y,m,1));
        const end = toLocalDateStr(new Date(y,m+1,0));
        return getFilteredEvents().filter(e => e.start_date <= end && e.end_date >= start);
    }

    function renderGridHead() {
        const days = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];
        el('lpAgendaGridHead').innerHTML = days.map(d => `<div>${d}</div>`).join('');
    }

    function renderGrid() {
        const events = getFilteredEvents();
        const year = currentDate.getFullYear(), month = currentDate.getMonth();
        const first = new Date(year, month, 1);
        const offset = (first.getDay() + 6) % 7;
        const start = new Date(year, month, 1 - offset);
        const todayStr = toLocalDateStr(new Date());
        let html = '';

        for (let i = 0; i < 42; i++) {
            const d = new Date(start); d.setDate(start.getDate() + i);
            const str = toLocalDateStr(d);
            const other = d.getMonth() !== month;
            const today = str === todayStr;
            const dayEvents = events.filter(e => e.start_date <= str && str <= e.end_date);
            const visible = dayEvents.slice(0, 3);
            const extra = dayEvents.length - visible.length;

            let cls = 'lp-agenda-day-cell';
            if (other) cls += ' other-month';
            if (today) cls += ' today';
            if (dayEvents.length) cls += ' has-events';

            const tooltipAttr = dayEvents.length ? `data-tooltip-date="${str}"` : '';

            html += `<div class="${cls}" data-date="${str}" ${tooltipAttr}>
                <span class="lp-agenda-day-num">${d.getDate()}</span>
                ${visible.map(e => {
                    const c = bidangColor(e.bidang);
                    return `<span class="lp-agenda-pill" style="background:${c.bg};color:${c.fg};border-left-color:${c.dot};" title="${escapeHtml(e.title)}">${escapeHtml(e.title)}</span>`;
                }).join('')}
                ${extra > 0 ? `<span class="lp-agenda-pill-more">+${extra}</span>` : ''}
            </div>`;
        }
        el('lpAgendaGridBody').innerHTML = html;

        document.querySelectorAll('#lpAgendaGridBody .lp-agenda-day-cell').forEach(cellEl => {
            cellEl.addEventListener('click', () => openDayDetail(cellEl.dataset.date));
            if (cellEl.dataset.tooltipDate) {
                cellEl.addEventListener('mouseenter', (ev) => showTooltip(ev, cellEl.dataset.tooltipDate));
                cellEl.addEventListener('mouseleave', hideTooltip);
            }
        });
    }

    function renderList() {
        const events = [...filteredEventsForList()].sort((a,b) => a.start_date.localeCompare(b.start_date));

        if (!events.length) {
            el('lpAgendaListBody').innerHTML = `<div class="lp-agenda-empty"><span class="lp-agenda-empty-icon">📭</span>Belum ada agenda bulan ini</div>`;
            el('lpAgendaPager').style.display = 'none';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(events.length / AGENDA_PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * AGENDA_PAGE_SIZE;
        const pageEvents = events.slice(startIdx, startIdx + AGENDA_PAGE_SIZE);

        const groups = {};
        pageEvents.forEach(e => { (groups[e.start_date] ||= []).push(e); });

        let html = '';
        Object.keys(groups).sort().forEach(date => {
            const d = new Date(date + 'T00:00:00');
            html += `<div class="lp-agenda-list-group">
                <p class="lp-agenda-list-date">${d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'})}
                    <span class="lp-agenda-badge-count">${groups[date].length}</span>
                </p>`;
            groups[date].forEach(e => {
                const c = bidangColor(e.bidang);
                html += `<div class="lp-agenda-list-row" style="border-left-color:${c.dot};">
                    <span class="lp-agenda-list-time">${formatTimeDisplay(e.start_time, e.end_time)}</span>
                    <span class="lp-agenda-list-title">${escapeHtml(e.title)}</span>
                    ${e.location ? `<span class="lp-agenda-list-location">📍 ${escapeHtml(e.location)}</span>` : ''}
                    ${e.bidang ? `<span class="lp-agenda-tooltip-bidang">${escapeHtml(e.bidang)}</span>` : ''}
                </div>`;
            });
            html += `</div>`;
        });
        el('lpAgendaListBody').innerHTML = html;

        renderPager(events.length, totalPages);
    }

    function renderPager(totalEvents, totalPages) {
        const pager = el('lpAgendaPager');
        if (totalPages <= 1) { pager.style.display = 'none'; return; }
        pager.style.display = 'flex';

        const startItem = (currentPage - 1) * AGENDA_PAGE_SIZE + 1;
        const endItem = Math.min(currentPage * AGENDA_PAGE_SIZE, totalEvents);

        let html = `<div class="lp-agenda-pager__info">Menampilkan ${startItem}–${endItem} dari ${totalEvents} agenda</div>`;
        html += `<button type="button" data-page="prev" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;

        // Tampilkan maks 5 nomor halaman, dengan window di sekitar halaman aktif
        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        if (endPage - startPage + 1 < maxButtons) startPage = Math.max(1, endPage - maxButtons + 1);

        if (startPage > 1) {
            html += `<button type="button" data-page="1">1</button>`;
            if (startPage > 2) html += `<span style="padding:0 4px;color:var(--text-muted);">…</span>`;
        }
        for (let p = startPage; p <= endPage; p++) {
            html += `<button type="button" data-page="${p}" class="${p === currentPage ? 'active' : ''}">${p}</button>`;
        }
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += `<span style="padding:0 4px;color:var(--text-muted);">…</span>`;
            html += `<button type="button" data-page="${totalPages}">${totalPages}</button>`;
        }

        html += `<button type="button" data-page="next" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;
        pager.innerHTML = html;

        pager.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.dataset.page;
                if (val === 'prev') currentPage--;
                else if (val === 'next') currentPage++;
                else currentPage = parseInt(val);
                renderList();
                // scroll ke atas list biar user langsung lihat halaman baru
                el('lpAgendaListView').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    }

    function openDayDetail(dateStr) {
        const events = getFilteredEvents().filter(e => e.start_date <= dateStr && dateStr <= e.end_date);
        const d = new Date(dateStr + 'T00:00:00');
        el('lpAgendaDayModalTitle').textContent = d.toLocaleDateString('id-ID', {weekday:'long',day:'numeric',month:'long'});
        el('lpAgendaDayModalList').innerHTML = events.map(e => {
            const c = bidangColor(e.bidang);
            return `<div class="lp-agenda-list-row" style="border-left-color:${c.dot}; flex-wrap:wrap;">
                <span class="lp-agenda-list-time">${formatTimeDisplay(e.start_time, e.end_time)}</span>
                <span class="lp-agenda-list-title">${escapeHtml(e.title)}</span>
                ${e.location ? `<span class="lp-agenda-list-location">📍 ${escapeHtml(e.location)}</span>` : ''}
                ${e.bidang ? `<span class="lp-agenda-tooltip-bidang">${escapeHtml(e.bidang)}</span>` : ''}
                ${e.description ? `<div class="lp-agenda-desc">${escapeHtml(e.description)}</div>` : ''}
            </div>`;
        }).join('') || '<p style="text-align:center;color:var(--text-muted);padding:20px;">Tidak ada agenda</p>';
        el('lpAgendaDayModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeDayDetail() {
        el('lpAgendaDayModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function showTooltip(evt, dateStr) {
        clearTimeout(tooltipTimeout);
        const dayEvents = getFilteredEvents().filter(e => e.start_date <= dateStr && dateStr <= e.end_date);
        if (!dayEvents.length) return;
        const tooltip = el('lpAgendaTooltip');
        const d = new Date(dateStr + 'T00:00:00');
        tooltip.innerHTML = `
            <div class="lp-agenda-tooltip-date">${d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'})}</div>
            ${dayEvents.map(e => `
                <div class="lp-agenda-tooltip-row">
                    <span class="lp-agenda-tooltip-time">${formatTimeDisplay(e.start_time, e.end_time)}</span>
                    <span class="lp-agenda-tooltip-title">${escapeHtml(e.title)}</span>
                    ${e.bidang ? `<span class="lp-agenda-tooltip-bidang">${escapeHtml(e.bidang)}</span>` : ''}
                </div>`).join('')}
        `;
        const rect = evt.currentTarget.getBoundingClientRect();
        tooltip.style.left = Math.min(Math.max(8, rect.left), window.innerWidth - 290) + 'px';
        tooltip.style.top = (rect.top - 10 > 150 ? rect.top - tooltip.offsetHeight - 8 : rect.bottom + 8) + 'px';
        tooltip.classList.add('visible');
    }
    function hideTooltip() {
        clearTimeout(tooltipTimeout);
        tooltipTimeout = setTimeout(() => el('lpAgendaTooltip').classList.remove('visible'), 150);
    }

    function switchView(view) {
        currentView = view;
        currentPage = 1;
        el('lpAgendaTabGrid').classList.toggle('active', view === 'grid');
        el('lpAgendaTabList').classList.toggle('active', view === 'list');
        el('lpAgendaGridView').style.display = view === 'grid' ? 'block' : 'none';
        el('lpAgendaListView').style.display = view === 'list' ? 'block' : 'none';
        el('lpAgendaSearchWrap').style.display = view === 'list' ? 'block' : 'none';
        if (view !== 'list') { el('lpAgendaSearch').value = ''; searchKeyword = ''; }
        renderCurrentView();
    }

    function renderMonthPicker() {
        const grid = el('lpAgendaMonthGrid');
        el('lpAgendaPickerYear').textContent = pickerYear;
        grid.innerHTML = MONTH_NAMES_SHORT.map((name, i) => {
            const isActive = i === currentDate.getMonth() && pickerYear === currentDate.getFullYear();
            return `<button type="button" data-month="${i}" class="${isActive ? 'active' : ''}">${name}</button>`;
        }).join('');
        grid.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                currentDate.setMonth(parseInt(btn.dataset.month));
                currentDate.setFullYear(pickerYear);
                currentPage = 1;
                el('lpAgendaMonthDropdown').classList.remove('open');
                loadEvents();
            });
        });
    }

    // ── Event bindings ──
    el('lpAgendaPrev').addEventListener('click', () => changeMonth(-1));
    el('lpAgendaNext').addEventListener('click', () => changeMonth(1));
    el('lpAgendaSearch').addEventListener('input', function() {
        searchKeyword = this.value.toLowerCase().trim();
        currentPage = 1;
        renderCurrentView();
    });
    el('lpAgendaTabGrid').addEventListener('click', () => switchView('grid'));
    el('lpAgendaTabList').addEventListener('click', () => switchView('list'));
    el('lpAgendaSearch').addEventListener('input', function() { searchKeyword = this.value.toLowerCase().trim(); renderCurrentView(); });
    el('lpAgendaDayModalClose').addEventListener('click', closeDayDetail);
    el('lpAgendaDayModal').addEventListener('click', (e) => { if (e.target === el('lpAgendaDayModal')) closeDayDetail(); });

    el('lpAgendaMonthBtn').addEventListener('click', () => {
        const dropdown = el('lpAgendaMonthDropdown');
        const isOpen = dropdown.classList.contains('open');
        if (isOpen) { dropdown.classList.remove('open'); return; }
        pickerYear = currentDate.getFullYear();
        dropdown.classList.add('open');
        renderMonthPicker();
    });
    el('lpAgendaPickerPrevYear').addEventListener('click', () => { pickerYear--; renderMonthPicker(); });
    el('lpAgendaPickerNextYear').addEventListener('click', () => { pickerYear++; renderMonthPicker(); });
    document.addEventListener('click', (e) => {
        const wrapper = document.querySelector('.lp-agenda-month-wrapper');
        if (wrapper && !wrapper.contains(e.target)) el('lpAgendaMonthDropdown').classList.remove('open');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDayDetail();
            el('lpAgendaMonthDropdown').classList.remove('open');
        }
    });

    renderGridHead();
    loadEvents();
});
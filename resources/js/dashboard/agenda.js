document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.AGENDA_CONFIG;
    if (!cfg) return;

    // ── Configuration ──
    const MONTH_NAMES = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const MONTH_NAMES_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const AGENDA_ROUTES = {
        data: cfg.dataUrl,
        store: cfg.storeUrl,
        update: (id) => `/agenda/${id}`,
        destroy: (id) => `/agenda/${id}`,
    };
    const CSRF_TOKEN = cfg.csrfToken || '';

    // Palet warna per-bidang — dipakai supaya warna event punya makna (kategori), bukan sekadar hiasan
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
    let currentEvents = [];
    let currentView = 'grid';
    let tooltipTimeout = null;
    let searchKeyword = '';
    let pickerYear = currentDate.getFullYear();
    let currentPage = 1;
    const AGENDA_PAGE_SIZE = 8;

    // ── Helpers ──
    function toLocalDateStr(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function isWeekend(d) { const day = d.getDay(); return day === 6 || day === 0; }

    function monthParam(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`; }

    // ── Format Tanggal ──
    function formatDateShort(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return `${d.getDate()} ${MONTH_NAMES[d.getMonth()]}`;
    }

    function formatDateFull(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return `${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
    }

    function multiDayLabel(e) {
        if (e.start_date === e.end_date) return '';
        
        const start = new Date(e.start_date + 'T00:00:00');
        const end = new Date(e.end_date + 'T00:00:00');
        const sameYear = start.getFullYear() === end.getFullYear();
        
        const format = (d) => {
            const date = `${d.getDate()} ${MONTH_NAMES[d.getMonth()]}`;
            return sameYear ? date : `${date} ${d.getFullYear()}`;
        };
        
        return ` (${format(start)} ~ ${format(end)})`;
    }

    // ── Format Waktu ──
    function formatTimeDisplay(startTime, endTime) {
        let startDisplay = '--:--';
        let endDisplay = '';
        
        if (startTime) {
            if (typeof startTime === 'string' && startTime.length >= 5) {
                startDisplay = startTime.slice(0, 5);
            } else {
                try {
                    const timeStr = String(startTime);
                    if (timeStr.includes(':')) {
                        startDisplay = timeStr.slice(0, 5);
                    }
                } catch (err) {}
            }
        }
        
        if (endTime) {
            if (typeof endTime === 'string' && endTime.length >= 5) {
                endDisplay = endTime.slice(0, 5);
            } else {
                try {
                    const timeStr = String(endTime);
                    if (timeStr.includes(':')) {
                        endDisplay = timeStr.slice(0, 5);
                    }
                } catch (err) {}
            }
        }
        
        if (endDisplay) {
            return `${startDisplay} s/d ${endDisplay}`;
        }
        
        if (startDisplay !== '--:--') {
            return `${startDisplay} s/d selesai`;
        }
        
        return startDisplay;
    }

    // ── Toast ──
    function showToast(msg, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = (type === 'error' ? '❌ ' : '✅ ') + msg;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
    }

    // ── Recipient Checkbox Dropdown ──
    function toggleRecipientDropdown() {
        document.getElementById('recipientDropdown').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('recipientDropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    function updateRecipientSelection() {
        const checkboxes = document.querySelectorAll('.recipient-checkbox:checked');
        const count = checkboxes.length;
        const countLabel = document.getElementById('recipientCount');
        const label = document.getElementById('recipientSelectedLabel');
        
        countLabel.textContent = count;
        
        if (count === 0) {
            label.textContent = 'Pilih penerima reminder';
        } else if (count <= 3) {
            const names = Array.from(checkboxes).map(cb => cb.value).join(', ');
            label.textContent = names;
        } else {
            label.textContent = `${count} dipilih`;
        }
    }

    function selectAllRecipients() {
        document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = true);
        updateRecipientSelection();
    }

    function deselectAllRecipients() {
        document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
        updateRecipientSelection();
    }

    function getSelectedRecipients() {
        return Array.from(document.querySelectorAll('.recipient-checkbox:checked')).map(cb => cb.value);
    }

    function setSelectedRecipients(selectedRoles) {
        document.querySelectorAll('.recipient-checkbox').forEach(cb => {
            cb.checked = selectedRoles.includes(cb.value);
        });
        updateRecipientSelection();
    }

    // ── Load Events ──
    function renderSkeleton() {
        if (currentView === 'grid') {
            document.getElementById('gridBody').innerHTML =
                Array.from({ length: 35 }).map(() => '<div class="skeleton-cell"></div>').join('');
        } else {
            document.getElementById('listBody').innerHTML =
                `<div class="skeleton-list">${Array.from({ length: 4 }).map(() => '<div class="skeleton-row"></div>').join('')}</div>`;
            document.getElementById('listPager').style.display = 'none';
        }
    }

    function loadEvents() {
        document.getElementById('monthLabel').textContent = `${MONTH_NAMES[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        
        // Update picker jika terbuka
        if (document.getElementById('monthPickerDropdown').classList.contains('open')) {
            renderMonthPicker();
        }

        renderSkeleton();

        fetch(`${AGENDA_ROUTES.data}?month=${monthParam(currentDate)}`)
            .then(res => res.json())
            .then(events => {
                currentEvents = events;
                const container = currentView === 'grid' ? document.getElementById('gridBody') : document.getElementById('listBody');
                container.style.opacity = '0';
                renderCurrentView();
                requestAnimationFrame(() => { container.style.opacity = '1'; });
            })
            .catch(() => showToast('Gagal memuat data', 'error'));
    }

    function changeMonth(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        pickerYear = currentDate.getFullYear();
        currentPage = 1;
        loadEvents();
    }

    function goToday() {
        currentDate = new Date();
        pickerYear = currentDate.getFullYear();
        currentPage = 1;
        loadEvents();
    }

    // ── Month Picker ──
    function toggleMonthPicker() {
        const dropdown = document.getElementById('monthPickerDropdown');
        const btn = document.getElementById('monthPickerBtn');
        const isOpen = dropdown.classList.contains('open');
        
        if (isOpen) {
            closeMonthPicker();
        } else {
            openMonthPicker();
        }
    }

    function openMonthPicker() {
        const dropdown = document.getElementById('monthPickerDropdown');
        const btn = document.getElementById('monthPickerBtn');
        pickerYear = currentDate.getFullYear();
        
        dropdown.classList.add('open');
        btn.classList.add('open');
        renderMonthPicker();
    }

    function closeMonthPicker() {
        const dropdown = document.getElementById('monthPickerDropdown');
        const btn = document.getElementById('monthPickerBtn');
        dropdown.classList.remove('open');
        btn.classList.remove('open');
    }

    function renderMonthPicker() {
        const grid = document.getElementById('monthPickerGrid');
        const yearLabel = document.getElementById('pickerYearLabel');
        const currentMonth = currentDate.getMonth();
        const currentYear = currentDate.getFullYear();
        
        yearLabel.textContent = pickerYear;
        
        grid.innerHTML = MONTH_NAMES_SHORT.map((name, index) => {
            const isActive = index === currentMonth && pickerYear === currentYear;
            return `<button class="${isActive ? 'active' : ''}" onclick="selectMonth(${index})">${name}</button>`;
        }).join('');
    }

    function selectMonth(month) {
        currentDate.setMonth(month);
        currentDate.setFullYear(pickerYear);
        currentPage = 1;
        closeMonthPicker();
        loadEvents();
    }

    function changePickerYear(delta) {
        pickerYear += delta;
        renderMonthPicker();
    }

    // ── Close month picker on outside click ──
    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.month-picker-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            closeMonthPicker();
        }
    });

    // ── View Switching ──
    function switchView(view) {
        currentView = view;
        currentPage = 1;
        document.getElementById('tabGrid').classList.toggle('active', view === 'grid');
        document.getElementById('tabList').classList.toggle('active', view === 'list');
        document.getElementById('gridView').style.display = view === 'grid' ? 'block' : 'none';
        document.getElementById('listView').style.display = view === 'list' ? 'block' : 'none';
        
        const searchContainer = document.getElementById('searchContainer');
        if (view === 'list') {
            searchContainer.style.display = 'block';
        } else {
            searchContainer.style.display = 'none';
            document.getElementById('agendaSearch').value = '';
            searchKeyword = '';
        }
        
        renderCurrentView();
    }

    function renderCurrentView() { 
        if (currentView === 'grid') renderGrid(); 
        else renderList(); 
    }

    // ── Search ──
    document.getElementById('agendaSearch')?.addEventListener('input', function() {
        searchKeyword = this.value.toLowerCase().trim();
        currentPage = 1;
        applyFilters();
    });

    // ── Apply Filters ──
    function applyFilters() {
        renderCurrentView();
    }

    function getFilteredEvents() {
        let filtered = currentEvents;
        if (searchKeyword) {
            filtered = filtered.filter(e => 
                e.title.toLowerCase().includes(searchKeyword) ||
                (e.location && e.location.toLowerCase().includes(searchKeyword)) ||
                (e.bidang && e.bidang.toLowerCase().includes(searchKeyword))
            );
        }
        return filtered;
    }

    function filteredEventsForGrid() { return getFilteredEvents(); }

    function filteredEventsForList() {
        const y = currentDate.getFullYear(), m = currentDate.getMonth();
        const start = toLocalDateStr(new Date(y,m,1));
        const end = toLocalDateStr(new Date(y,m+1,0));
        return getFilteredEvents().filter(e => e.start_date <= end && e.end_date >= start);
    }

    // ── Render Grid ──
    function renderGridHead() {
        const days = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];
        document.getElementById('gridHead').innerHTML = days.map((d,i) => 
            `<div class="${i===5||i===6?'weekend-head':''}">${d}</div>`
        ).join('');
    }

    function isCompactScreen() { return window.innerWidth <= 480; }

    function renderGrid() {
        const events = filteredEventsForGrid();
        const year = currentDate.getFullYear(), month = currentDate.getMonth();
        const first = new Date(year, month, 1);
        const offset = (first.getDay() + 6) % 7;
        const start = new Date(year, month, 1 - offset);
        const todayStr = toLocalDateStr(new Date());
        const compact = isCompactScreen();
        let html = '';

        for (let i = 0; i < 42; i++) {
            const d = new Date(start); d.setDate(start.getDate() + i);
            const str = toLocalDateStr(d);
            const other = d.getMonth() !== month;
            const today = str === todayStr;
            const w = isWeekend(d);
            const dayEvents = events.filter(e => e.start_date <= str && str <= e.end_date);
            const visible = dayEvents.slice(0, 3);
            const extra = dayEvents.length - visible.length;

            let cls = 'agenda-day-cell';
            if (other) cls += ' other-month';
            if (today) cls += ' today';
            if (w && !other) cls += ' weekend';
            if (dayEvents.length) cls += ' has-events';

            const tooltipAttr = (dayEvents.length && !compact) ?
                `onmouseenter="showDayTooltip(event, '${str}')" onmouseleave="hideDayTooltip()"` : '';

            // Di layar kecil, sel hari yang punya event membuka detail hari (bukan form event baru)
            const cellClick = (compact && dayEvents.length)
                ? `openDayDetail('${str}')`
                : `openEventModal(null,'${str}')`;

            const body = compact
                ? (dayEvents.length ? `<div class="agenda-day-dots">
                        ${dayEvents.slice(0, 4).map(e => `<span class="day-dot" style="background:${bidangColor(e.bidang).dot}"></span>`).join('')}
                        ${dayEvents.length > 4 ? `<span class="day-dot-more">+${dayEvents.length - 4}</span>` : ''}
                    </div>` : '')
                : `${visible.map(e => {
                        const c = bidangColor(e.bidang);
                        return `<span class="agenda-pill"
                              style="background:${c.bg}; color:${c.fg}; border-left-color:${c.dot};"
                              onclick="event.stopPropagation(); openEventModal(${e.id})"
                              title="${escapeHtml(e.title)}">${escapeHtml(e.title)}</span>`;
                    }).join('')}
                    ${extra > 0 ? `<span class="agenda-pill-more" onclick="event.stopPropagation(); openDayDetail('${str}')">+${extra}</span>` : ''}`;

            html += `<div class="${cls}" onclick="${cellClick}" data-date="${str}" ${tooltipAttr}>
                <span class="agenda-day-num">${d.getDate()}</span>
                ${body}
            </div>`;
        }
        document.getElementById('gridBody').innerHTML = html;
    }

    let resizeRenderTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeRenderTimer);
        resizeRenderTimer = setTimeout(() => { if (currentView === 'grid') renderGrid(); }, 200);
    });

    // ── Swipe Navigation ──
    let touchStartX = 0;
    let touchCurrentX = 0;
    let isSwiping = false;
    let isAnimating = false;
    let swipeIndicatorTimeout = null;

    function showSwipeIndicator(direction) {
        const leftIndicator = document.getElementById('swipeLeft');
        const rightIndicator = document.getElementById('swipeRight');
        
        clearTimeout(swipeIndicatorTimeout);
        
        if (direction === 'left') {
            leftIndicator.classList.add('show');
            rightIndicator.classList.remove('show');
        } else {
            rightIndicator.classList.add('show');
            leftIndicator.classList.remove('show');
        }
        
        swipeIndicatorTimeout = setTimeout(() => {
            hideSwipeIndicator();
        }, 800);
    }

    function hideSwipeIndicator() {
        document.getElementById('swipeLeft').classList.remove('show');
        document.getElementById('swipeRight').classList.remove('show');
        clearTimeout(swipeIndicatorTimeout);
    }

    function initSwipe() {
        const mainContainer = document.querySelector('.agenda-main');
        const gridBody = document.getElementById('gridBody');
        const listView = document.getElementById('listView');
        
        const getActiveView = () => currentView === 'grid' ? gridBody : listView;

        mainContainer.addEventListener('touchstart', function(e) {
            if (isAnimating) return;
            touchStartX = e.changedTouches[0].screenX;
            touchCurrentX = touchStartX;
            isSwiping = false;
            const view = getActiveView();
            view.style.transition = 'none';
            view.style.transform = 'translateX(0)';
            view.style.opacity = '1';
        }, { passive: true });

        mainContainer.addEventListener('touchmove', function(e) {
            if (isAnimating) return;
            touchCurrentX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchCurrentX;
            
            if (Math.abs(diff) > 10) {
                isSwiping = true;
                const view = getActiveView();
                const translateX = -diff * 0.5;
                view.style.transition = 'none';
                view.style.transform = `translateX(${translateX}px)`;
                view.style.opacity = Math.max(0.5, 1 - Math.abs(diff) / 300);
                
                showSwipeIndicator(diff > 0 ? 'left' : 'right');
            }
        }, { passive: true });

        mainContainer.addEventListener('touchend', function(e) {
            if (isAnimating) return;
            const diff = touchStartX - touchCurrentX;
            const threshold = 60;
            const view = getActiveView();
            
            view.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s ease';
            
            if (Math.abs(diff) > threshold && isSwiping) {
                isAnimating = true;
                
                if (diff > 0) {
                    view.style.transform = 'translateX(-80px)';
                    view.style.opacity = '0';
                    setTimeout(() => {
                        changeMonth(1);
                        setTimeout(() => {
                            view.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s ease';
                            view.style.transform = 'translateX(80px)';
                            view.style.opacity = '0';
                            setTimeout(() => {
                                view.style.transform = 'translateX(0)';
                                view.style.opacity = '1';
                                setTimeout(() => {
                                    view.style.transition = '';
                                    view.style.transform = '';
                                    view.style.opacity = '';
                                    isAnimating = false;
                                }, 500);
                            }, 50);
                        }, 100);
                    }, 300);
                } else {
                    view.style.transform = 'translateX(80px)';
                    view.style.opacity = '0';
                    setTimeout(() => {
                        changeMonth(-1);
                        setTimeout(() => {
                            view.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s ease';
                            view.style.transform = 'translateX(-80px)';
                            view.style.opacity = '0';
                            setTimeout(() => {
                                view.style.transform = 'translateX(0)';
                                view.style.opacity = '1';
                                setTimeout(() => {
                                    view.style.transition = '';
                                    view.style.transform = '';
                                    view.style.opacity = '';
                                    isAnimating = false;
                                }, 500);
                            }, 50);
                        }, 100);
                    }, 300);
                }
            } else {
                view.style.transform = 'translateX(0)';
                view.style.opacity = '1';
                setTimeout(() => {
                    view.style.transition = '';
                    view.style.transform = '';
                    view.style.opacity = '';
                }, 500);
            }
            
            isSwiping = false;
            hideSwipeIndicator();
        }, { passive: true });
    }

    // ── Tooltip ──
    function showDayTooltip(evt, dateStr) {
        clearTimeout(tooltipTimeout);
        const dayEvents = filteredEventsForGrid().filter(e => e.start_date <= dateStr && dateStr <= e.end_date);
        if (dayEvents.length === 0) return;

        const tooltip = document.getElementById('agendaTooltip');
        const d = new Date(dateStr + 'T00:00:00');
        const dateLabel = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });

        tooltip.innerHTML = `
            <div class="agenda-tooltip-date">${dateLabel}</div>
            ${dayEvents.map(e => {
                const timeDisplay = formatTimeDisplay(e.start_time, e.end_time);
                const c = bidangColor(e.bidang);
                return `
                    <div class="agenda-tooltip-row">
                        <span class="event-dot" style="background:${c.dot}"></span>
                        <span class="agenda-tooltip-time">${timeDisplay}</span>
                        <span class="agenda-tooltip-title">${escapeHtml(e.title)}</span>
                        ${e.location ? `<span class="agenda-tooltip-location">📍${escapeHtml(e.location)}</span>` : ''}
                        ${e.bidang ? `<span class="agenda-tooltip-bidang">${escapeHtml(e.bidang)}</span>` : ''}
                    </div>
                `;
            }).join('')}
        `;

        positionTooltip(evt.currentTarget, tooltip);
        tooltip.classList.add('visible');
    }

    function positionTooltip(cellEl, tooltip) {
        const cell = cellEl.getBoundingClientRect();
        const margin = 10;
        const gap = 8;

        const tooltipRect = tooltip.getBoundingClientRect();
        let left = cell.left + cell.width / 2 - tooltipRect.width / 2;
        if (left < margin) left = margin;
        if (left + tooltipRect.width > window.innerWidth - margin) {
            left = window.innerWidth - tooltipRect.width - margin;
        }

        const spaceAbove = cell.top - margin;
        const spaceBelow = window.innerHeight - cell.bottom - margin;
        let top;
        
        if (tooltipRect.height + gap <= spaceAbove || spaceAbove >= spaceBelow) {
            top = cell.top - tooltipRect.height - gap;
        } else {
            top = cell.bottom + gap;
        }

        if (top < margin) top = margin;
        if (top + tooltipRect.height > window.innerHeight - margin) {
            top = Math.max(margin, window.innerHeight - tooltipRect.height - margin);
        }

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    }

    function hideDayTooltip() {
        clearTimeout(tooltipTimeout);
        tooltipTimeout = setTimeout(() => {
            document.getElementById('agendaTooltip').classList.remove('visible');
        }, 200);
    }

    // ── Render List ──
    function renderList() {
        const events = [...filteredEventsForList()].sort((a,b) => a.start_date.localeCompare(b.start_date));

        if (!events.length) {
            document.getElementById('listBody').innerHTML = `<div class="agenda-empty"><span class="agenda-empty-icon">📭</span><div class="agenda-empty-title">Belum ada event</div></div>`;
            document.getElementById('listPager').style.display = 'none';
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
            html += `<div class="agenda-list-group">
                <p class="agenda-list-date">${d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'})}
                    <span class="badge-count">${groups[date].length}</span>
                </p>`;
            groups[date].forEach(e => {
                const can = e.can_manage;
                const timeDisplay = formatTimeDisplay(e.start_time, e.end_time);
                const c = bidangColor(e.bidang);
                html += `
                    <div class="agenda-list-row" style="border-left-color:${c.dot};">
                        <span class="event-dot" style="background:${c.dot}"></span>
                        <span class="agenda-list-time">${timeDisplay}</span>
                        <span class="agenda-list-title">${escapeHtml(e.title)}${multiDayLabel(e)}</span>
                        ${e.location ? `<span class="agenda-list-location">📍 ${escapeHtml(e.location)}</span>` : ''}
                        ${e.bidang ? `<span class="agenda-tooltip-bidang" style="color:${c.fg}; background:${c.bg};">${escapeHtml(e.bidang)}</span>` : ''}
                        <div class="agenda-list-actions">
                            ${can ? `<button class="icon-btn" onclick="openEventModal(${e.id})">✏️</button>` : ''}
                            ${can ? `<button class="icon-btn danger" onclick="deleteEvent(${e.id})">🗑</button>` : ''}
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        });
        document.getElementById('listBody').innerHTML = html;

        renderListPager(events.length, totalPages);
    }

    function renderListPager(totalEvents, totalPages) {
        const pager = document.getElementById('listPager');
        if (totalPages <= 1) { pager.style.display = 'none'; return; }
        pager.style.display = 'flex';

        const startItem = (currentPage - 1) * AGENDA_PAGE_SIZE + 1;
        const endItem = Math.min(currentPage * AGENDA_PAGE_SIZE, totalEvents);

        let html = `<div class="agenda-pager__info">Menampilkan ${startItem}–${endItem} dari ${totalEvents} event</div>`;
        html += `<button type="button" data-page="prev" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;

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
                document.getElementById('listView').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    }

    // ── Day Detail ──
    function openDayDetail(dateStr) {
        const events = filteredEventsForGrid().filter(e => e.start_date <= dateStr && dateStr <= e.end_date);
        const d = new Date(dateStr + 'T00:00:00');
        document.getElementById('dayDetailTitle').textContent = d.toLocaleDateString('id-ID', {weekday:'long',day:'numeric',month:'long'});
        document.getElementById('dayDetailList').innerHTML = events.map(e => {
            const timeDisplay = formatTimeDisplay(e.start_time, e.end_time);
            const c = bidangColor(e.bidang);
            return `
                <div class="agenda-list-row" style="border-bottom:1px solid var(--border); border-left-color:${c.dot};">
                    <span class="event-dot" style="background:${c.dot}"></span>
                    <span class="agenda-list-time">${timeDisplay}</span>
                    <span class="agenda-list-title">${escapeHtml(e.title)}${multiDayLabel(e)}</span>
                    ${e.location ? `<span class="agenda-list-location">📍 ${escapeHtml(e.location)}</span>` : ''}
                    <div class="agenda-list-actions">
                        ${e.can_manage ? `<button class="icon-btn" onclick="closeDayDetail(); openEventModal(${e.id})">✏️</button>` : ''}
                    </div>
                </div>
            `;
        }).join('') || '<p style="text-align:center;color:var(--text-muted);padding:20px;">Tidak ada agenda</p>';
        document.getElementById('dayDetailAddBtn').onclick = () => { closeDayDetail(); openEventModal(null, dateStr); };
        document.getElementById('dayDetailModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDayDetail() {
        document.getElementById('dayDetailModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    // ── Event Modal ──
    function openEventModal(id = null, date = null) {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('eventReminderCustom').style.display = 'none';
        document.getElementById('eventReminderCustom').value = '';

        document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
        updateRecipientSelection();

        if (id) {
            const ev = currentEvents.find(e => e.id === id);
            if (!ev) { showToast('Event tidak ditemukan', 'error'); return; }
            if (!ev.can_manage) { showToast('Tidak ada izin', 'error'); return; }
            document.getElementById('eventModalTitle').textContent = '✏️ Edit Event';
            document.getElementById('eventId').value = ev.id;
            document.getElementById('eventTitle').value = ev.title;
            document.getElementById('eventStartDate').value = ev.start_date;
            document.getElementById('eventEndDate').value = ev.end_date;
            document.getElementById('eventLocation').value = ev.location || '';
            
            if (ev.start_time) {
                if (typeof ev.start_time === 'string' && ev.start_time.length === 5) {
                    document.getElementById('eventStartTime').value = ev.start_time;
                } else {
                    try {
                        const timeStr = String(ev.start_time);
                        if (timeStr.includes(':')) {
                            document.getElementById('eventStartTime').value = timeStr.slice(0, 5);
                        }
                    } catch (e) {
                        document.getElementById('eventStartTime').value = '';
                    }
                }
            } else {
                document.getElementById('eventStartTime').value = '';
            }
            
            if (ev.end_time) {
                if (typeof ev.end_time === 'string' && ev.end_time.length === 5) {
                    document.getElementById('eventEndTime').value = ev.end_time;
                } else {
                    try {
                        const timeStr = String(ev.end_time);
                        if (timeStr.includes(':')) {
                            document.getElementById('eventEndTime').value = timeStr.slice(0, 5);
                        }
                    } catch (e) {
                        document.getElementById('eventEndTime').value = '';
                    }
                }
            } else {
                document.getElementById('eventEndTime').value = '';
            }
            
            document.getElementById('eventProject').value = ev.bidang || '';
            document.getElementById('eventCategory').value = ev.category || 'Konsolidasi';
            document.getElementById('eventDescription').value = ev.description || '';

            if (ev.recipients && ev.recipients.length > 0) {
                setSelectedRecipients(ev.recipients);
            }

            if (ev.reminder_offset_days !== undefined && ev.reminder_offset_days !== null) {
                const val = ev.reminder_offset_days;
                const options = ['1','0','-1'];
                if (options.includes(String(val))) {
                    document.getElementById('eventReminder').value = String(val);
                } else if (val > 0) {
                    document.getElementById('eventReminder').value = 'custom';
                    document.getElementById('eventReminderCustom').style.display = 'block';
                    document.getElementById('eventReminderCustom').value = val;
                }
            }
        } else {
            document.getElementById('eventModalTitle').textContent = '✨ Event Baru';
            if (date) {
                document.getElementById('eventStartDate').value = date;
                document.getElementById('eventEndDate').value = date;
            }
        }
        document.getElementById('eventModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeEventModal() {
        document.getElementById('eventModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function toggleCustomReminder() {
        const s = document.getElementById('eventReminder');
        const c = document.getElementById('eventReminderCustom');
        c.style.display = s.value === 'custom' ? 'block' : 'none';
        c.required = s.value === 'custom';
        if (s.value !== 'custom') c.value = '';
    }

    function getReminderValue() {
        const s = document.getElementById('eventReminder');
        if (s.value === 'custom') return parseInt(document.getElementById('eventReminderCustom').value) || null;
        return s.value;
    }

    // ── Submit Form ──
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('eventId').value;
        const reminder = getReminderValue();
        const selectedRecipients = getSelectedRecipients();

        const payload = {
            title: document.getElementById('eventTitle').value,
            start_date: document.getElementById('eventStartDate').value,
            end_date: document.getElementById('eventEndDate').value,
            location: document.getElementById('eventLocation').value || null,
            start_time: document.getElementById('eventStartTime').value || null,
            end_time: document.getElementById('eventEndTime').value || null,
            bidang: document.getElementById('eventProject').value,
            category: document.getElementById('eventCategory').value,
            reminder_offset_days: reminder,
            description: document.getElementById('eventDescription').value || null,
            recipients: selectedRecipients,
        };

        if (payload.start_time && payload.end_time && payload.end_time <= payload.start_time) {
            showToast('Waktu selesai harus lebih besar dari waktu mulai', 'error');
            return;
        }

        if (!payload.bidang || !payload.category || !reminder) {
            showToast('Lengkapi semua field wajib', 'error');
            return;
        }
        if (document.getElementById('eventReminder').value === 'custom') {
            const val = parseInt(document.getElementById('eventReminderCustom').value);
            if (isNaN(val) || val < 1) { showToast('Isi jumlah hari (min 1)', 'error'); return; }
            payload.reminder_offset_days = val;
        }

        const btn = document.getElementById('saveEventBtn');
        const text = document.getElementById('saveEventText');
        const spinner = document.getElementById('saveEventSpinner');
        btn.disabled = true;
        text.textContent = 'Menyimpan...';
        spinner.style.display = 'block';

        const url = id ? AGENDA_ROUTES.update(id) : AGENDA_ROUTES.store;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(payload),
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan');
            return data;
        })
        .then(() => {
            closeEventModal();
            showToast('Event berhasil disimpan' + (selectedRecipients.length > 0 ? ' 📧 Reminder dikirim' : ''));
            loadEvents();
        })
        .catch(err => showToast(err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            text.textContent = 'Simpan';
            spinner.style.display = 'none';
        });
    });

    // ── Delete ──
    async function deleteEvent(id) {
        const ev = currentEvents.find(e => e.id === id);
        const confirmed = await showConfirm('🗑️', 'Hapus Event?', `Hapus "${ev?.title}"?`);
        if (!confirmed) return;
        fetch(AGENDA_ROUTES.destroy(id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        })
        .then(async res => {
            if (!res.ok) throw new Error((await res.json()).message);
            showToast('Event dihapus');
            loadEvents();
            closeDayDetail();
        })
        .catch(err => showToast(err.message, 'error'));
    }

    // ── Confirmation ──
    function showConfirm(icon, title, msg) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            document.getElementById('confirmIcon').textContent = icon || '⚠️';
            document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
            document.getElementById('confirmMessage').textContent = msg || 'Apakah Anda yakin?';
            const btn = document.getElementById('confirmBtn');
            btn.className = 'btn-danger';
            btn.textContent = 'Ya, Hapus';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';

            const cleanup = () => {
                modal.classList.remove('open');
                document.body.style.overflow = '';
                document.removeEventListener('keydown', escHandler);
                modal.removeEventListener('click', backdropHandler);
            };
            const escHandler = (e) => { if (e.key === 'Escape') { cleanup(); resolve(false); } };
            const backdropHandler = (e) => { if (e.target === modal) { cleanup(); resolve(false); } };
            document.addEventListener('keydown', escHandler);
            modal.addEventListener('click', backdropHandler);

            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', () => { cleanup(); resolve(true); });
            document.querySelector('.confirm-modal-actions .btn-ghost')?.addEventListener('click', () => { cleanup(); resolve(false); });
        });
    }

    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    // ── Export ──
    function exportAgenda() {
        const events = getFilteredEvents();
        if (!events.length) { showToast('Tidak ada data', 'error'); return; }
        let csv = 'Judul,Tanggal Mulai,Tanggal Selesai,Tempat,Waktu,Bidang,Kategori\n';
        events.forEach(e => {
            csv += `"${e.title}","${e.start_date}","${e.end_date}","${e.location||''}","${e.start_time||''}","${e.bidang||''}","${e.category}"\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Agenda_${MONTH_NAMES[currentDate.getMonth()]}_${currentDate.getFullYear()}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        showToast('Export berhasil');
    }

    // ── Keyboard Shortcuts ──
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'n') { e.preventDefault(); openEventModal(); }
        if (e.ctrlKey && e.key === 't') { e.preventDefault(); goToday(); }
        if (e.key === 'Escape') {
            if (document.getElementById('eventModal').classList.contains('open')) closeEventModal();
            if (document.getElementById('dayDetailModal').classList.contains('open')) closeDayDetail();
            if (document.getElementById('confirmModal').classList.contains('open')) closeConfirm();
            hideDayTooltip();
            closeMonthPicker();
        }
    });

    // ── Init ──
    ['eventModal', 'agendaTooltip', 'dayDetailModal','confirmModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
    renderGridHead();
    loadEvents();
    initSwipe();

    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    });

    // Make functions global (dipakai oleh onclick="" inline di HTML)
    window.changeMonth = changeMonth;
    window.goToday = goToday;
    window.switchView = switchView;
    window.openEventModal = openEventModal;
    window.closeEventModal = closeEventModal;
    window.openDayDetail = openDayDetail;
    window.closeDayDetail = closeDayDetail;
    window.deleteEvent = deleteEvent;
    window.toggleCustomReminder = toggleCustomReminder;
    window.exportAgenda = exportAgenda;
    window.closeConfirm = closeConfirm;
    window.showDayTooltip = showDayTooltip;
    window.hideDayTooltip = hideDayTooltip;
    window.toggleRecipientDropdown = toggleRecipientDropdown;
    window.updateRecipientSelection = updateRecipientSelection;
    window.selectAllRecipients = selectAllRecipients;
    window.deselectAllRecipients = deselectAllRecipients;
    window.toggleMonthPicker = toggleMonthPicker;
    window.closeMonthPicker = closeMonthPicker;
    window.selectMonth = selectMonth;
    window.changePickerYear = changePickerYear;
});
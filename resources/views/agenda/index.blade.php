@extends('layouts.app')

@section('title', 'Agenda | PUK OMRON')

@section('content')

<div class="agenda-page">

    {{-- HEADER --}}
    <div class="agenda-header">  
        <div class="agenda-header-left">
            <h1 class="agenda-title">
                <span class="title-emoji">📅</span>
                <span class="title-text">Agenda Kegiatan</span>
            </h1>
            <p class="agenda-subtitle">Kelola dan pantau seluruh jadwal kegiatan dalam satu tampilan</p>
        </div>
        <div class="agenda-header-actions">
            <button class="btn-ghost-primary" onclick="exportAgenda()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export
            </button>
            <button class="btn-primary" onclick="openEventModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="display:inline-block; vertical-align:middle; margin-right:4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah Event
            </button>
        </div>
    </div>

    <div class="agenda-layout">

        {{-- MAIN --}}
        <main class="agenda-main">
            <div class="swipe-indicator left" id="swipeLeft">‹</div>
            <div class="swipe-indicator right" id="swipeRight">›</div>

            <div class="agenda-toolbar">
                <div class="agenda-nav">
                    <button class="agenda-nav-btn" onclick="changeMonth(-1)">‹</button>
                    <div class="month-picker-wrapper">
                        <button class="month-picker-btn" id="monthPickerBtn" onclick="toggleMonthPicker()">
                            <span id="monthLabel"></span>
                            <span class="dropdown-arrow">▾</span>
                        </button>
                        <div class="month-picker-dropdown" id="monthPickerDropdown">
                            <div class="month-picker-header">
                                <button onclick="changePickerYear(-1)">‹</button>
                                <span class="year-label" id="pickerYearLabel"></span>
                                <button onclick="changePickerYear(1)">›</button>
                            </div>
                            <div class="month-picker-grid" id="monthPickerGrid"></div>
                            <div class="month-picker-footer">
                                <button onclick="closeMonthPicker()">Tutup</button>
                            </div>
                        </div>
                    </div>
                    <button class="agenda-nav-btn" onclick="changeMonth(1)">›</button>
                    <button class="agenda-today-btn" onclick="goToday()">Hari ini</button>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <div class="agenda-search" id="searchContainer" style="display:none;">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="agendaSearch" placeholder="Cari event...">
                    </div>
                    <div class="agenda-view-tabs">
                        <button class="agenda-view-tab active" id="tabGrid" onclick="switchView('grid')">📅 Bulan</button>
                        <button class="agenda-view-tab" id="tabList" onclick="switchView('list')">📋 List</button>
                    </div>
                </div>
            </div>

            <div id="gridView">
                <div class="agenda-grid-head" id="gridHead"></div>
                <div class="agenda-grid-body" id="gridBody"></div>
            </div>
            <div id="listView" style="display:none;">
                <div id="listBody"></div>
                <div class="agenda-pager" id="listPager" style="display:none;"></div>
            </div>
        </main>
    </div>
</div>

{{-- MODAL EVENT --}}
<div class="modal-backdrop" id="eventModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="eventModalTitle">✨ Event Baru</h2>
            <button class="modal-close" onclick="closeEventModal()">✕</button>
        </div>
        <form id="eventForm">
            <input type="hidden" id="eventId">
            <div class="form-group">
                <label class="form-label">Judul Event</label>
                <input type="text" id="eventTitle" class="form-input" placeholder="Masukkan judul..." required>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" id="eventStartDate" class="form-input" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" id="eventEndDate" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">📍 Tempat</label>
                <input type="text" id="eventLocation" class="form-input" placeholder="Masukkan tempat...">
            </div>

            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="time" id="eventStartTime" class="form-input">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="time" id="eventEndTime" class="form-input">
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Bidang</label>
                    <select id="eventProject" class="form-input" required>
                        <option value="" disabled selected>-- Pilih --</option>
                        @foreach($bidangOptions as $bidang)
                        <option value="{{ $bidang }}">{{ $bidang }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Kategori</label>
                    <select id="eventCategory" class="form-input" required>
                        <option value="" disabled selected>-- Pilih --</option>
                        <option>Konsolidasi</option>
                        <option>Aksi</option>
                        <option>Pendidikan</option>
                        <option>Meeting</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Reminder</label>
                    <div style="display:flex; gap:6px;">
                        <select id="eventReminder" class="form-input" style="flex:1;" onchange="toggleCustomReminder()">
                            <option value="" disabled selected>-- Pilih --</option>
                            <option value="1">H-1</option>
                            <option value="0">Hari-H</option>
                            <option value="-1">Tanpa</option>
                            <option value="custom">Custom</option>
                        </select>
                        <input type="number" id="eventReminderCustom" class="form-input" 
                               placeholder="Hari" style="width:70px; display:none;" min="1" max="30">
                    </div>
                </div>
            </div>

            {{-- FIELD KIRIM REMINDER KE --}}
            <div class="form-group">
                <label class="form-label">📧 Kirim Reminder ke</label>
                <div class="checkbox-dropdown" id="recipientDropdown">
                    <button type="button" class="checkbox-dropdown-btn" onclick="toggleRecipientDropdown()">
                        <span id="recipientSelectedLabel">Pilih penerima reminder</span>
                        <span class="selected-count" id="recipientCount">0</span>
                    </button>
                    <div class="checkbox-dropdown-panel" id="recipientPanel">
                        <div id="recipientCheckboxList">
                            @foreach($users as $user)
                            <label class="checkbox-item">
                                <input type="checkbox" class="recipient-checkbox" value="{{ $user->role }}" 
                                    data-email="{{ $user->email }}" data-username="{{ $user->username }}"
                                    onchange="updateRecipientSelection()">
                                <span class="role-label">{{ $user->role }}</span>
                                <span class="email-hint">{{ $user->email }}</span>
                            </label>
                            @endforeach
                        </div>
                        <div class="checkbox-divider"></div>
                        <div class="checkbox-actions">
                            <button type="button" onclick="selectAllRecipients()">✅ Pilih Semua</button>
                            <button type="button" class="text-muted" onclick="deselectAllRecipients()">❌ Hapus Semua</button>
                        </div>
                    </div>
                </div>
                <small style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">
                    Pilih siapa saja yang akan menerima email reminder
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea id="eventDescription" class="form-input" rows="2" placeholder="Deskripsi (opsional)..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-ghost" onclick="closeEventModal()">Batal</button>
                <button type="submit" class="btn-primary" id="saveEventBtn">
                    <span id="saveEventText">Simpan</span>
                    <div class="spinner" id="saveEventSpinner"></div>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL DETAIL HARI --}}
<div class="modal-backdrop" id="dayDetailModal">
    <div class="modal-box" style="max-width:440px;">
        <div class="modal-header">
            <h2 class="modal-title" id="dayDetailTitle">📋 Agenda</h2>
            <button class="modal-close" onclick="closeDayDetail()">✕</button>
        </div>
        <div id="dayDetailList" style="max-height:350px; overflow-y:auto;"></div>
        <div class="form-actions" style="margin-top:12px;">
            <button class="btn-ghost" onclick="closeDayDetail()">Tutup</button>
            <button class="btn-primary" id="dayDetailAddBtn">+ Tambah</button>
        </div>
    </div>
</div>

{{-- TOOLTIP --}}
<div class="agenda-tooltip" id="agendaTooltip"></div>

{{-- TOAST --}}
<div class="toast-container" id="toastContainer"></div>

{{-- CONFIRMATION --}}
<div class="confirm-modal-backdrop" id="confirmModal">
    <div class="confirm-modal-box">
        <span class="confirm-modal-icon" id="confirmIcon">⚠️</span>
        <h3 class="confirm-modal-title" id="confirmTitle">Konfirmasi</h3>
        <p class="confirm-modal-message" id="confirmMessage">Apakah Anda yakin?</p>
        <div class="confirm-modal-actions">
            <button class="btn-ghost" onclick="closeConfirm()">Batal</button>
            <button class="btn-danger" id="confirmBtn">Ya, Hapus</button>
        </div>
    </div>
</div>

{{-- Jembatan data dari PHP ke agenda.js --}}
<script>
    window.AGENDA_CONFIG = {
        dataUrl: "{{ route('agenda.data') }}",
        storeUrl: "{{ route('agenda.store') }}",
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    };
</script>
@vite(['resources/css/dashboard/agenda.css', 'resources/js/dashboard/agenda.js'])

@endsection
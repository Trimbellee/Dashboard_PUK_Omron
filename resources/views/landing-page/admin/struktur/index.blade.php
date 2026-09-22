{{-- resources/views/landing-page/admin/struktur/index.blade.php --}}
@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-struktur.css'])
@endpush

@section('title', 'Struktur Organisasi - Admin PUK OMRON')

@section('content')
<div class="lp-admin-head">
    <div class="lp-admin-head__left">
        <h1 class="lp-admin-head__title">🏛️ Struktur Organisasi</h1>
        <p class="lp-admin-head__desc">Kelola hierarki dan anggota struktur organisasi PUK OMRON</p>
    </div>
    <div class="lp-admin-head__actions">
        <a href="{{ route('admin.struktur.create') }}" class="lp-btn lp-btn--primary lp-btn--icon">
            <span class="lp-btn__icon">+</span>
            <span class="lp-btn__text">Tambah Jabatan</span>
        </a>
        <button type="button" class="lp-btn lp-btn--ghost lp-btn--icon" onclick="toggleView()">
            <span id="viewToggleIcon">📊</span>
            <span class="lp-btn__text" id="viewToggleText">Tree</span>
        </button>
    </div>
</div>

@if(session('success'))
    <div class="lp-admin-alert lp-admin-alert--success">
        <span class="lp-admin-alert__icon">✅</span>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="lp-admin-alert lp-admin-alert--error">
        <span class="lp-admin-alert__icon">❌</span>
        {{ session('error') }}
    </div>
@endif

<div class="lp-struktur-container">
    @if(empty($tree) || count($tree) == 0)
        <div class="lp-empty-state">
            <div class="lp-empty-state__icon">📋</div>
            <h3 class="lp-empty-state__title">Belum ada data struktur</h3>
            <p class="lp-empty-state__desc">Tambahkan jabatan utama pertama untuk memulai hierarki organisasi</p>
            <a href="{{ route('admin.struktur.create') }}" class="lp-btn lp-btn--primary">
                + Tambah Jabatan Utama
            </a>
        </div>
    @else
        {{-- Tree View --}}
        <div id="treeView" class="lp-struktur-tree-view">
            <div class="lp-struktur-tree-wrapper">
                <ul class="lp-tree-root">
                    @foreach($tree as $node)
                        @include('landing-page.admin.struktur.node', ['node' => $node])
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- List View --}}
        <div id="listView" class="lp-struktur-list-view" style="display: none;">
            <div class="lp-table-wrapper">
                <div class="lp-table-scroll">
                    <table class="lp-admin-table">
                        <thead>
                            <tr>
                                <th class="lp-table-col--number">#</th>
                                <th class="lp-table-col--jabatan">Jabatan</th>
                                <th class="lp-table-col--level">Level</th>
                                <th class="lp-table-col--anggota">Anggota</th>
                                <th class="lp-table-col--children">Sub</th>
                                <th class="lp-table-col--actions">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($flatList ?? [] as $item)
                                <tr>
                                    <td class="lp-table-col--number">{{ $loop->iteration }}</td>
                                    <td class="lp-table-col--jabatan">
                                        <div class="lp-table-jabatan">
                                            @for($i = 0; $i < $item['level']; $i++)
                                                <span class="lp-table-indent">├─</span>
                                            @endfor
                                            <span class="lp-table-jabatan__name" data-level="{{ $item['level'] }}">
                                                {{ $item['jabatan'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="lp-table-col--level">
                                        <span class="lp-badge lp-badge--level-{{ $item['level'] }}">
                                            Lv.{{ $item['level'] }}
                                        </span>
                                    </td>
                                    <td class="lp-table-col--anggota">
                                        @if($item['anggota'])
                                            <div class="lp-table-member">
                                                <img src="{{ $item['anggota']->foto ? asset('storage/' . $item['anggota']->foto) : asset('assets/no-photo.png') }}" 
                                                     class="lp-table-avatar"
                                                     alt="{{ $item['anggota']->nama_lengkap }}">
                                                <span class="lp-table-member__name">{{ $item['anggota']->nama_lengkap }}</span>
                                            </div>
                                        @else
                                            <span class="lp-table-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="lp-table-col--children">
                                        <span class="lp-badge lp-badge--count">{{ $item['children_count'] ?? 0 }}</span>
                                    </td>
                                    <td class="lp-table-col--actions">
                                        <div class="lp-action-group">
                                            <a href="{{ route('admin.struktur.edit', $item['id']) }}" 
                                               class="lp-struktur-action-btn" 
                                               title="Edit">
                                                ✏️
                                            </a>
                                            <a href="{{ route('admin.struktur.create') }}?parent_id={{ $item['id'] }}" 
                                               class="lp-struktur-action-btn" 
                                               title="Tambah Sub">
                                                ➕
                                            </a>
                                            @if($item['level'] > 0)
                                                <form action="{{ route('admin.struktur.move-up', $item['id']) }}" 
                                                      method="POST" 
                                                      style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="lp-struktur-action-btn" title="Naik">⬆</button>
                                                </form>
                                                <form action="{{ route('admin.struktur.move-down', $item['id']) }}" 
                                                      method="POST" 
                                                      style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="lp-struktur-action-btn" title="Turun">⬇</button>
                                                </form>
                                            @endif
                                            <form id="delete-form-{{ $item['id'] }}" 
                                                  action="{{ route('admin.struktur.destroy', $item['id']) }}" 
                                                  method="POST" 
                                                  style="display:inline;">
                                                @csrf @method('DELETE')
                                            </form>
                                            <button type="button" 
                                                class="lp-struktur-action-btn lp-struktur-action-btn--danger" 
                                                @click="$dispatch('open-confirm', { 
                                                    message: 'Hapus jabatan \'{{ addslashes($item['jabatan']) }}\' beserta semua sub-jabatannya?', 
                                                    formId: 'delete-form-{{ $item['id'] }}' 
                                                })"
                                                title="Hapus">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function toggleView() {
    const treeView = document.getElementById('treeView');
    const listView = document.getElementById('listView');
    const icon = document.getElementById('viewToggleIcon');
    const text = document.getElementById('viewToggleText');
    
    if (treeView.style.display === 'none') {
        treeView.style.display = 'block';
        listView.style.display = 'none';
        icon.textContent = '📊';
        text.textContent = 'Tree';
    } else {
        treeView.style.display = 'none';
        listView.style.display = 'block';
        icon.textContent = '📋';
        text.textContent = 'Daftar';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.innerWidth < 640;
    if (isMobile) {
        const treeView = document.getElementById('treeView');
        const listView = document.getElementById('listView');
        if (treeView && listView) {
            treeView.style.display = 'none';
            listView.style.display = 'block';
            document.getElementById('viewToggleIcon').textContent = '📋';
            document.getElementById('viewToggleText').textContent = 'Daftar';
        }
    }
});
</script>

@endsection
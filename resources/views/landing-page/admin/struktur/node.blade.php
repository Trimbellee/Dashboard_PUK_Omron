{{-- resources/views/landing-page/admin/struktur/node.blade.php --}}
<li class="lp-tree-item" 
    x-data="{
        expanded: localStorage.getItem('tree-expanded-{{ $node['id'] }}') !== 'false',
        toggle() {
            this.expanded = !this.expanded;
            localStorage.setItem('tree-expanded-{{ $node['id'] }}', this.expanded);
        }
    }"
    x-init="
        $watch('expanded', value => {
            localStorage.setItem('tree-expanded-{{ $node['id'] }}', value);
        });
    "
>
    @php
        $level = $node['level'] ?? 0;
        $hasChildren = $node['children']->isNotEmpty();
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
    @endphp

    <div class="lp-tree-row" data-id="{{ $node['id'] }}" data-level="{{ $level }}">
        {{-- Indentasi --}}
        <div class="lp-tree-row__indent" style="width: {{ $level * 20 }}px; flex-shrink: 0;">
            <span class="lp-tree-indent-text">{!! $indent !!}</span>
        </div>

        {{-- Tombol Toggle --}}
        <div class="lp-tree-row__toggle">
            @if($hasChildren)
                <button type="button" @click="toggle()" 
                        class="lp-tree-toggle"
                        :aria-expanded="expanded"
                        title="{{ $node['jabatan'] }}: {{ $node['children']->count() }} sub-jabatan">
                    <svg class="lp-tree-chevron" :class="{ 'is-rotated': expanded }" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6.22 3.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L9.94 8 6.22 4.28a.75.75 0 0 1 0-1.06z"/>
                    </svg>
                </button>
            @else
                <span class="lp-tree-toggle-placeholder"></span>
            @endif
        </div>

        {{-- Konten --}}
        <div class="lp-tree-row__content">
            <div class="lp-tree-header">
                <span class="lp-tree-title" data-level="{{ $level }}">
                    {{ $node['jabatan'] }}
                </span>
            </div>

            {{-- Anggota --}}
            @if($node['anggota'])
                <div class="lp-tree-member">
                    <img src="{{ $node['anggota']->foto ? asset('storage/' . $node['anggota']->foto) : asset('assets/no-photo.png') }}" 
                         alt="{{ $node['anggota']->nama_lengkap }}"
                         class="lp-tree-avatar"
                         loading="lazy">
                    <div class="lp-tree-member-info">
                        <span class="lp-tree-member-name">{{ $node['anggota']->nama_lengkap }}</span>
                        <span class="lp-tree-member-role">
                            {{ $node['anggota']->jabatan ?? 'Anggota' }}
                            @if($node['anggota']->departemen)
                                · {{ $node['anggota']->departemen }}
                            @endif
                        </span>
                    </div>
                </div>
            @else
                <div class="lp-tree-empty-member">
                    <span>—</span>
                    <span class="lp-tree-empty-text">Kosong</span>
                </div>
            @endif
        </div>

        {{-- Aksi --}}
        <div class="lp-tree-row__actions">
            <a href="{{ route('admin.struktur.edit', $node['id']) }}" class="lp-tree-action" title="Edit">✏️</a>
            <a href="{{ route('admin.struktur.create') }}?parent_id={{ $node['id'] }}" class="lp-tree-action" title="Tambah Sub">➕</a>
            @if($level > 0)
                <form action="{{ route('admin.struktur.move-up', $node['id']) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="lp-tree-action" title="Naik">⬆</button>
                </form>
                <form action="{{ route('admin.struktur.move-down', $node['id']) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="lp-tree-action" title="Turun">⬇</button>
                </form>
            @endif
            <form id="delete-form-{{ $node['id'] }}" action="{{ route('admin.struktur.destroy', $node['id']) }}" method="POST" style="display:inline;">
                @csrf @method('DELETE')
            </form>
            {{--
                PERBAIKAN:
                Sebelumnya tombol ini pakai onclick="if(confirm('...')) ..." yaitu
                dialog konfirmasi bawaan browser, bukan modal custom Alpine yang
                sudah dibangun di layouts/app.blade.php (lp-confirm-modal).
                Sekarang di-dispatch event 'open-confirm' yang sama seperti di
                struktur/index.blade.php (List View), supaya konsisten dan
                menggunakan modal custom + styling proyek.
            --}}
            <button type="button" class="lp-tree-action lp-tree-action--danger"
                    @click="$dispatch('open-confirm', {
                        message: 'Hapus jabatan \'{{ addslashes($node['jabatan']) }}\' beserta semua sub-jabatannya?',
                        formId: 'delete-form-{{ $node['id'] }}'
                    })"
                    title="Hapus">🗑️</button>
        </div>
    </div>

    {{-- Children --}}
    @if($hasChildren)
        <ul class="lp-tree-children" 
            x-show="expanded"
            x-collapse.duration.300ms>
            @foreach($node['children'] as $child)
                @include('landing-page.admin.struktur.node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
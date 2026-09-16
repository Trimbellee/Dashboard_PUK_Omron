{{-- resources/views/landing-page/partials/struktur-node-public.blade.php --}}

@php
    $level = $node['level'] ?? 0;
    $hasChildren = !empty($node['children']) && count($node['children']) > 0;
    $isLast = $isLast ?? false;
    $indentLevel = $level * 24;
    
    $anggota = $node['anggota'] ?? null;
    $modalData = null;
    if ($anggota) {
        $tanggalMulaiFormatted = $anggota->tanggal_mulai_bekerja 
            ? \Carbon\Carbon::parse($anggota->tanggal_mulai_bekerja)->translatedFormat('d F Y') 
            : null;
            
        $modalData = json_encode([
            'nama' => $anggota->nama_lengkap ?? '—',
            'departemen' => $anggota->departemen ?? '-',
            'jabatan' => $anggota->jabatan ?? '-',
            'umur' => $anggota->tanggal_lahir ? \Carbon\Carbon::parse($anggota->tanggal_lahir)->age : '-',
            'pendidikan' => $anggota->pendidikan_terakhir ?? '-',
            'mulaiBekerja' => $tanggalMulaiFormatted ?? '-',
            'foto' => $anggota->foto ? asset('storage/' . $anggota->foto) : null,
            'jabatanPuk' => $node['jabatan'] ?? ''
        ]);
    }
@endphp

<li class="tree-item {{ $isLast ? 'is-last' : '' }}"
    x-data="{ expanded: true }"
    :class="{ 'is-expanded': expanded }"
    style="--indent: {{ $indentLevel }}px;">

    <div class="tree-row" data-level="{{ $level }}">
        <!-- Indent spacer -->
        <div class="tree-indent" style="width: {{ $indentLevel }}px;"></div>
        
        <!-- Toggle button -->
        @if($hasChildren)
            <button 
                class="tree-toggle" 
                @click="expanded = !expanded"
                :aria-expanded="expanded"
                title="Toggle children">
                <svg class="tree-chevron" :class="{ 'is-rotated': expanded }" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M6.22 3.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L9.94 8 6.22 4.28a.75.75 0 0 1 0-1.06z"/>
                </svg>
            </button>
        @else
            <span class="tree-toggle-placeholder"></span>
        @endif

        <!-- Main content -->
        <div class="tree-content"
             @if($modalData)
                 @click="window.openModal && window.openModal({{ $modalData }})"
                 role="button"
                 tabindex="0"
                 @keydown.enter="window.openModal && window.openModal({{ $modalData }})"
                 style="cursor: pointer;"
             @endif>
            
            <div class="tree-header">
                <span class="tree-title">{{ $node['jabatan'] }}</span>
                @if($hasChildren)
                    <span class="tree-badge">{{ count($node['children']) }}</span>
                @endif
            </div>

            <!-- Member info -->
            @if($anggota)
                <div class="tree-member">
                    @if($anggota->foto)
                        <img 
                            src="{{ asset('storage/'.$anggota->foto) }}" 
                            alt="{{ $anggota->nama_lengkap }}"
                            class="tree-avatar"
                            loading="lazy">
                    @else
                        <span class="tree-avatar-placeholder">
                            {{ strtoupper(substr($anggota->nama_lengkap, 0, 2)) }}
                        </span>
                    @endif
                    
                    <div class="tree-member-info">
                        <span class="tree-member-name">{{ $anggota->nama_lengkap }}</span>
                        <span class="tree-member-role">
                            {{ $anggota->jabatan }}
                            @if($anggota->departemen)
                                · {{ $anggota->departemen }}
                            @endif
                        </span>
                    </div>
                </div>
            @else
                <div class="tree-empty-member">
                    <span>—</span>
                    <span class="tree-empty-text">Belum ada anggota</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Children -->
    @if($hasChildren)
        <ul class="tree-children" 
            x-show="expanded" 
            x-collapse.duration.200ms
            x-cloak>
            @foreach($node['children'] as $child)
                @include('landing-page.partials.struktur-node-public', [
                    'node' => $child,
                    'isLast' => $loop->last
                ])
            @endforeach
        </ul>
    @endif
</li>
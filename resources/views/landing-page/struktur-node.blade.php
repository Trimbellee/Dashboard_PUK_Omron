{{-- resources/views/landing-page/struktur-node.blade --}}
<li>
    <div class="lp-tree-node-public">
        <div class="lp-tree-node-public__header">
            <span class="lp-tree-node-public__jabatan">{{ $node['jabatan'] }}</span>
            @if($node['anggota'])
                <span class="lp-tree-node-public__anggota">
                    @if($node['anggota']->foto)
                        <img src="{{ asset('storage/' . $node['anggota']->foto) }}" alt="{{ $node['anggota']->nama_lengkap }}" class="lp-tree-node-public__foto">
                    @endif
                    <span class="lp-tree-node-public__nama">{{ $node['anggota']->nama_lengkap }}</span>
                    @if($node['anggota']->nomor_whatsapp)
                        <a href="https://wa.me/{{ $node['anggota']->nomor_whatsapp }}" target="_blank" class="lp-wa-link" title="Hubungi via WhatsApp" aria-label="WhatsApp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                        </a>
                    @endif
                </span>
            @else
                <span class="lp-tree-node-public__empty">(belum diisi)</span>
            @endif
        </div>
    </div>
    @if($node['children']->isNotEmpty())
        <ul>
            @foreach($node['children'] as $child)
                @include('landing-page.struktur-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>

@if ($paginator->hasPages())
<nav role="navigation" aria-label="Navigasi Halaman"
     class="not-prose flex flex-col items-center gap-4 mt-8 pt-6 border-t border-[var(--border-color)]">

    {{-- Summary --}}
    <p class="!m-0 text-[13px] text-[var(--text-secondary)]">
        Menampilkan
        <span class="font-bold text-[var(--text-primary)]">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
        dari
        <span class="font-bold text-[var(--text-primary)]">{{ $paginator->total() }}</span>
        berita
    </p>

    {{-- Controls --}}
    <div class="flex items-center gap-1.5 flex-wrap justify-center">

        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true"
                  class="!inline-flex !items-center !justify-center w-9 h-9 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] text-[var(--text-muted)] opacity-40 cursor-not-allowed">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
               class="!inline-flex !items-center !justify-center w-9 h-9 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] text-[var(--text-primary)] no-underline transition-all duration-200 hover:!border-[var(--accent)] hover:!text-[var(--accent)] hover:bg-[var(--accent-soft)] hover:-translate-y-0.5">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
        @endif

        {{-- Page Numbers --}}
        <div class="flex items-center gap-1 flex-wrap justify-center">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="!inline-flex !items-center !justify-center min-w-[30px] h-9 text-[13px] font-bold text-[var(--text-muted)]">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="!inline-flex !items-center !justify-center min-w-9 h-9 px-2 rounded-lg text-[13.5px] font-bold text-white no-underline"
                                  style="background: var(--accent-gradient); box-shadow: var(--shadow-md);">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="!inline-flex !items-center !justify-center min-w-9 h-9 px-2 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] text-[var(--text-secondary)] no-underline text-[13.5px] font-semibold transition-all duration-200 hover:!border-[var(--accent)] hover:!text-[var(--accent)] hover:bg-[var(--accent-soft)] hover:-translate-y-0.5">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Selanjutnya"
               class="!inline-flex !items-center !justify-center w-9 h-9 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] text-[var(--text-primary)] no-underline transition-all duration-200 hover:!border-[var(--accent)] hover:!text-[var(--accent)] hover:bg-[var(--accent-soft)] hover:-translate-y-0.5">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        @else
            <span aria-disabled="true"
                  class="!inline-flex !items-center !justify-center w-9 h-9 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)] text-[var(--text-muted)] opacity-40 cursor-not-allowed">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif
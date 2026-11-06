@props(['paginator' => null])

@if (($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages()) || !$paginator)
    <div class="fixed bottom-0 inset-x-0 z-30 py-10 px-16 bg-base/90 backdrop-blur-md border-t border-subtle flex items-center justify-center shadow-lg">
        @if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages())
            <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-center gap-6">
                {{-- Tombol Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="w-[36px] h-[36px] rounded-sm bg-surface-2 border border-subtle/80 text-ink-disabled flex items-center justify-center opacity-60 cursor-default" aria-disabled="true">
                        <x-icon name="chevron-left" class="w-4 h-4" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-[36px] h-[36px] rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] font-bold flex items-center justify-center transition-all shadow-sm" aria-label="Halaman Sebelumnya">
                        <x-icon name="chevron-left" class="w-4 h-4" />
                    </a>
                @endif

                {{-- Nomor Halaman (Tampilkan rentang halaman) --}}
                @php
                    $currentPage = $paginator->currentPage();
                    $lastPage = $paginator->lastPage();
                    $start = max(1, $currentPage - 2);
                    $end = min($lastPage, $start + 4);
                    if ($end - $start < 4) {
                        $start = max(1, $end - 4);
                    }
                @endphp

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $currentPage)
                        <span class="w-[36px] h-[36px] rounded-sm bg-accent text-[#080C14] font-bold text-xs flex items-center justify-center shadow-sm" aria-current="page">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="w-[36px] h-[36px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-semibold text-xs flex items-center justify-center transition-colors" aria-label="Ke halaman {{ $page }}">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                {{-- Tombol Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-[36px] h-[36px] rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] font-bold flex items-center justify-center transition-all shadow-sm" aria-label="Halaman Berikutnya">
                        <x-icon name="chevron-right" class="w-4 h-4" />
                    </a>
                @else
                    <span class="w-[36px] h-[36px] rounded-sm bg-surface-2 border border-subtle/80 text-ink-disabled flex items-center justify-center opacity-60 cursor-default" aria-disabled="true">
                        <x-icon name="chevron-right" class="w-4 h-4" />
                    </span>
                @endif
            </nav>
        @else
            {{-- Tampilan Preview / Fallback Statis Sesuai Mockup UI --}}
            <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-center gap-6">
                <button type="button" class="w-[36px] h-[36px] rounded-sm bg-surface-2 border border-subtle/80 text-ink-disabled flex items-center justify-center opacity-60 cursor-default" aria-disabled="true">
                    <x-icon name="chevron-left" class="w-4 h-4" />
                </button>

                <span class="w-[36px] h-[36px] rounded-sm bg-accent text-[#080C14] font-bold text-xs flex items-center justify-center shadow-sm" aria-current="page">
                    1
                </span>
                <button type="button" class="w-[36px] h-[36px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-semibold text-xs flex items-center justify-center transition-colors cursor-pointer">
                    2
                </button>
                <button type="button" class="w-[36px] h-[36px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-semibold text-xs flex items-center justify-center transition-colors cursor-pointer">
                    3
                </button>

                <button type="button" class="w-[36px] h-[36px] rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] font-bold flex items-center justify-center transition-all shadow-sm cursor-pointer" aria-label="Halaman Berikutnya">
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </button>
            </nav>
        @endif
    </div>
@endif


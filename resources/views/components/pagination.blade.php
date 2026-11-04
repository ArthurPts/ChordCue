@props(['paginator' => null])

@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-center gap-8">
        {{-- Tombol Previous --}}
        @if ($paginator->onFirstPage())
            <span class="w-[38px] h-[38px] rounded-lg bg-accent text-white flex items-center justify-center opacity-75 cursor-default" aria-disabled="true">
                <x-icon name="chevron-left" class="w-4 h-4" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-[38px] h-[38px] rounded-lg bg-accent hover:bg-accent-hover text-white flex items-center justify-center transition-colors" aria-label="Halaman Sebelumnya">
                <x-icon name="chevron-left" class="w-4 h-4" />
            </a>
        @endif

        {{-- Nomor Halaman (Tampilkan rentang halaman) --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            // Batasi tampilan maksimal 5 nomor halaman agar rapi
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $start + 4);
            if ($end - $start < 4) {
                $start = max(1, $end - 4);
            }
        @endphp

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $currentPage)
                <span class="w-[38px] h-[38px] rounded-lg bg-[#536582] text-white font-medium flex items-center justify-center shadow-sm" aria-current="page">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}" class="w-[38px] h-[38px] rounded-lg bg-surface-2 hover:bg-surface-3 text-ink-secondary hover:text-ink font-medium flex items-center justify-center transition-colors" aria-label="Ke halaman {{ $page }}">
                    {{ $page }}
                </a>
            @endif
        @endfor

        {{-- Tombol Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-[38px] h-[38px] rounded-lg bg-accent hover:bg-accent-hover text-white flex items-center justify-center transition-colors" aria-label="Halaman Berikutnya">
                <x-icon name="chevron-right" class="w-4 h-4" />
            </a>
        @else
            <span class="w-[38px] h-[38px] rounded-lg bg-accent text-white flex items-center justify-center opacity-75 cursor-default" aria-disabled="true">
                <x-icon name="chevron-right" class="w-4 h-4" />
            </span>
        @endif
    </nav>
@else
    {{-- Tampilan Preview / Fallback Statis Sesuai Mockup UI --}}
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-center gap-8">
        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-accent hover:bg-accent-hover text-white flex items-center justify-center transition-colors cursor-pointer" aria-label="Halaman Sebelumnya">
            <x-icon name="chevron-left" class="w-4 h-4" />
        </button>

        <span class="w-[38px] h-[38px] rounded-lg bg-[#536582] text-white font-medium flex items-center justify-center shadow-sm" aria-current="page">
            1
        </span>
        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-surface-2 hover:bg-surface-3 text-ink-secondary hover:text-ink font-medium flex items-center justify-center transition-colors cursor-pointer">
            2
        </button>
        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-surface-2 hover:bg-surface-3 text-ink-secondary hover:text-ink font-medium flex items-center justify-center transition-colors cursor-pointer">
            3
        </button>
        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-surface-2 hover:bg-surface-3 text-ink-secondary hover:text-ink font-medium flex items-center justify-center transition-colors cursor-pointer">
            4
        </button>
        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-surface-2 hover:bg-surface-3 text-ink-secondary hover:text-ink font-medium flex items-center justify-center transition-colors cursor-pointer">
            5
        </button>

        <button type="button" class="w-[38px] h-[38px] rounded-lg bg-accent hover:bg-accent-hover text-white flex items-center justify-center transition-colors cursor-pointer" aria-label="Halaman Berikutnya">
            <x-icon name="chevron-right" class="w-4 h-4" />
        </button>
    </nav>
@endif


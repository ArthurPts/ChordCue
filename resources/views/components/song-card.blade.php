@props([
    'song' => null,
    'title' => null,
    'artist' => null,
    'bpm' => null,
    'timeSignature' => null,
    'categories' => null,
    'url' => '#',
    'canManage' => false,
    'editUrl' => '#',
    'deleteUrl' => '#',
])

@php
    if ($song) {
        $title = $title ?? $song->title;
        $artist = $artist ?? ($song->artist ? $song->artist->name : 'Artis Tidak Diketahui');
        $bpm = $bpm ?? $song->bpm;
        $timeSignature = $timeSignature ?? ($song->time_signature_numerator . '/' . $song->time_signature_denominator);
        $categories = $categories ?? $song->categories;
        $url = $url !== '#' ? $url : route('chords.index');
        $editUrl = $editUrl !== '#' ? $editUrl : route('songs.edit', $song);
        $deleteUrl = $deleteUrl !== '#' ? $deleteUrl : route('songs.destroy', $song);
    } else {
        $title = $title ?? 'Judul Lagu Contoh';
        $artist = $artist ?? 'Artis Contoh';
        $bpm = $bpm ?? 120;
        $timeSignature = $timeSignature ?? '4/4';
        $categories = $categories ?? [];
    }
@endphp

<div class="bg-surface-1 border border-subtle rounded-lg p-20 flex flex-col justify-between hover:border-accent/40 transition-all hover:bg-surface-2/20 group">
    <div>
        <!-- Title & Artist Header -->
        <div class="space-y-4">
            <h3 class="text-lg sm:text-xl font-bold text-ink tracking-tight truncate group-hover:text-accent transition-colors" title="{{ $title }}">
                {{ $title }}
            </h3>
            <p class="text-xs sm:text-sm text-ink-secondary font-medium truncate" title="{{ $artist }}">
                {{ $artist }}
            </p>
        </div>

        <!-- Metric Row: BPM & Time Signature -->
        <div class="mt-16 pt-12 border-t border-subtle/50 flex items-center justify-between text-xs text-ink-secondary">
            <div class="flex items-center gap-6">
                <x-icon name="metronome" class="w-4 h-4 text-ink-secondary shrink-0" />
                <span class="font-mono font-medium text-ink">{{ $bpm }} <span class="text-[11px] text-ink-tertiary">BPM</span></span>
            </div>
            <div class="font-mono">
                <span class="text-ink-tertiary">Birama:</span>
                <span class="font-medium text-ink ml-2">{{ $timeSignature }}</span>
            </div>
        </div>

        <!-- Badges / Chips -->
        <div class="flex flex-wrap gap-6 mt-12">
            @if ($categories && count($categories) > 0)
                @foreach ($categories as $cat)
                    @php
                        $catType = is_array($cat) ? ($cat['type'] ?? 'genre') : ($cat->type ?? 'genre');
                        $catName = is_array($cat) ? ($cat['name'] ?? 'Umum') : ($cat->name ?? 'Umum');
                    @endphp
                    <span class="rounded-md px-8 py-2.5 text-[11px] font-medium text-ink-secondary border border-subtle/70 bg-surface-2/70 inline-flex items-center gap-4">
                        @if ($catType === 'mood')
                            <x-icon name="mood-smile" class="w-3 h-3 text-ink-tertiary shrink-0" />
                        @elseif ($catType === 'location')
                            <x-icon name="map-pin" class="w-3 h-3 text-ink-tertiary shrink-0" />
                        @else
                            <x-icon name="tag" class="w-3 h-3 text-ink-tertiary shrink-0" />
                        @endif
                        <span>{{ $catName }}</span>
                    </span>
                @endforeach
            @else
                <span class="rounded-md px-8 py-2.5 text-[11px] font-medium text-ink-secondary border border-subtle/70 bg-surface-2/70 inline-flex items-center gap-4">
                    <x-icon name="tag" class="w-3 h-3 text-ink-tertiary shrink-0" />
                    <span>Lagu</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Card Action Buttons -->
    <div class="mt-20 pt-16 border-t border-subtle">
        @if ($canManage)
            <div class="flex items-center justify-between gap-8">
                <!-- Putar -->
                <a href="{{ $url }}" class="h-[38px] px-14 rounded-lg bg-accent hover:bg-accent-hover text-[#080C14] font-bold text-xs inline-flex items-center gap-6 transition-all shadow-sm cursor-pointer">
                    <x-icon name="play" class="w-3.5 h-3.5 shrink-0" />
                    <span>Putar</span>
                </a>

                <div class="flex items-center gap-6">
                    <!-- Edit -->
                    <a href="{{ $editUrl }}" class="h-[38px] px-12 rounded-lg bg-surface-2 hover:bg-surface-3 border border-subtle text-ink inline-flex items-center justify-center font-medium text-xs transition-colors cursor-pointer">
                        <span>Edit</span>
                    </a>

                    <!-- Hapus -->
                    @if ($song)
                        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Apakah kamu yakin ingin menghapus lagu ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="h-[38px] px-10 rounded-lg bg-error-tint/60 hover:bg-error-tint border border-error/30 text-error font-medium text-xs inline-flex items-center justify-center transition-colors cursor-pointer" title="Hapus Lagu">
                                <span>Hapus</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <!-- Action Button (Default Putar) -->
            <a href="{{ $url }}" class="w-full h-[38px] px-16 rounded-lg bg-accent hover:bg-accent-hover text-[#080C14] font-bold text-xs inline-flex items-center justify-center gap-6 transition-all shadow-sm cursor-pointer">
                <x-icon name="play" class="w-3.5 h-3.5 shrink-0" />
                <span>Mulai Latihan</span>
            </a>
        @endif
    </div>
</div>

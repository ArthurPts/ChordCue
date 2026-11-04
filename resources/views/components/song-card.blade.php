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

<div class="bg-surface-1 border border-subtle rounded-2xl p-24 flex flex-col justify-between hover:border-accent/40 transition-colors">
    <div>
        <!-- Title & Artist -->
        <h3 class="text-2xl font-medium text-ink tracking-tight truncate" title="{{ $title }}">
            {{ $title }}
        </h3>
        <p class="text-base text-ink-secondary mt-4 truncate" title="{{ $artist }}">
            {{ $artist }}
        </p>

        <!-- Metric Row: BPM & Time Signature -->
        <div class="flex items-center justify-between text-base text-ink mt-20">
            <div class="flex items-center gap-8">
                <x-icon name="metronome" class="w-5 h-5 text-ink shrink-0" />
                <span class="font-normal">{{ $bpm }} BPM</span>
            </div>
            <div>
                <span class="font-normal">Time Signature : {{ $timeSignature }}</span>
            </div>
        </div>

        <!-- Badges / Chips -->
        <div class="flex flex-wrap gap-8 mt-16">
            @if ($categories && count($categories) > 0)
                @foreach ($categories as $cat)
                    @php
                        $catType = is_array($cat) ? ($cat['type'] ?? 'genre') : ($cat->type ?? 'genre');
                        $catName = is_array($cat) ? ($cat['name'] ?? 'Umum') : ($cat->name ?? 'Umum');
                    @endphp
                    <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                        @if ($catType === 'mood')
                            <x-icon name="mood-smile" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                        @elseif ($catType === 'location')
                            <x-icon name="map-pin" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                        @else
                            <x-icon name="tag" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                        @endif
                        <span>{{ $catName }}</span>
                    </span>
                @endforeach
            @else
                {{-- Fallback Dummy Badges Sesuai Desain Target --}}
                <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                    <x-icon name="mood-smile" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                    <span>Mood</span>
                </span>
                <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                    <x-icon name="tag" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                    <span>Genre</span>
                </span>
                <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                    <x-icon name="map-pin" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                    <span>Lokasi</span>
                </span>
                <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                    <x-icon name="mood-smile" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                    <span>Mood</span>
                </span>
                <span class="rounded-full px-12 py-4 text-xs text-ink-secondary border border-subtle bg-surface-2 inline-flex items-center gap-6">
                    <x-icon name="tag" class="w-3.5 h-3.5 text-ink-tertiary shrink-0" />
                    <span>Genre</span>
                </span>
            @endif
        </div>
    </div>

    <div>
        <!-- Divider -->
        <div class="border-t border-subtle my-20"></div>

        @if ($canManage)
            <!-- Management Action Buttons (Putar, Edit, Hapus) -->
            <div class="flex items-center justify-between gap-8">
                <!-- Putar -->
                <a href="{{ $url }}" class="h-[42px] px-20 rounded-xl bg-accent hover:bg-accent-hover text-white inline-flex items-center gap-8 font-medium text-sm transition-colors cursor-pointer">
                    <x-icon name="play" class="w-4 h-4 shrink-0" />
                    <span>Putar</span>
                </a>

                <!-- Edit -->
                <a href="{{ $editUrl }}" class="h-[42px] px-24 rounded-xl bg-surface-2 hover:bg-surface-3 border border-subtle text-ink inline-flex items-center justify-center font-medium text-sm transition-colors cursor-pointer">
                    <span>Edit</span>
                </a>

                <!-- Hapus -->
                @if ($song)
                    <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lagu ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="h-[42px] px-20 rounded-xl bg-[#DE6D62] hover:bg-[#D55F55] text-[#131C2E] font-medium text-sm inline-flex items-center justify-center transition-colors cursor-pointer">
                            <span>Hapus</span>
                        </button>
                    </form>
                @else
                    <button type="button" class="h-[42px] px-20 rounded-xl bg-[#DE6D62] hover:bg-[#D55F55] text-[#131C2E] font-medium text-sm inline-flex items-center justify-center transition-colors cursor-pointer">
                        <span>Hapus</span>
                    </button>
                @endif
            </div>
        @else
            <!-- Action Button (Default Putar) -->
            <a href="{{ $url }}" class="h-[42px] px-20 rounded-xl bg-accent hover:bg-accent-hover text-white inline-flex items-center gap-8 font-medium text-sm transition-colors cursor-pointer">
                <x-icon name="play" class="w-4 h-4 shrink-0" />
                <span>Putar</span>
            </a>
        @endif
    </div>
</div>

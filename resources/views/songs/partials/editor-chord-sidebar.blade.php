<!-- KOLOM KANAN (4 cols): Pustaka Chord + Chord yang belum digunakan di bawahnya -->
<aside class="lg:col-span-4 lg:sticky lg:top-[80px] space-y-16">
    <!-- Panel Pustaka Chord -->
    <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 space-y-14">
        <div class="flex items-center gap-8">
            <x-icon name="music" class="w-icon-control h-icon-control text-accent shrink-0" />
            <div>
                <h2 class="text-title font-medium text-ink">Pustaka Chord</h2>
                <p class="text-caption text-ink-tertiary">{{ $hint }}</p>
            </div>
        </div>

        <!-- Pencarian Instan -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-12 flex items-center pointer-events-none text-ink-secondary">
                <x-icon name="search" class="w-icon-chip h-icon-chip" />
            </div>
            <input
                type="text"
                x-model="chordSearch"
                placeholder="Cari chord..."
                aria-label="Cari chord"
                class="w-full h-[36px] pl-[36px] pr-12 rounded-sm bg-surface-2 border border-subtle text-ink placeholder:text-ink-tertiary font-mono text-caption focus:border-accent focus:outline-none"
            >
        </div>

        <!-- Daftar Chip Chord (Dark Theme Only) -->
        <div class="flex flex-wrap gap-8 max-h-[380px] overflow-y-auto pr-4">
            <template x-for="chord in filteredChords" :key="chord.id">
                <button
                    type="button"
                    draggable="true"
                    @dragstart="startDragLibraryChord($event, chord)"
                    @dragend="endDrag()"
                    class="bg-surface-2 hover:bg-surface-3 border border-subtle hover:border-accent text-accent font-mono text-caption font-medium px-10 py-6 rounded-sm cursor-grab select-none transition-colors"
                    x-text="chord.name"
                ></button>
            </template>

            <template x-if="filteredChords.length === 0">
                <p class="text-caption text-ink-tertiary py-8">
                    Tidak ada chord yang cocok dengan pencarian.
                </p>
            </template>
        </div>
    </div>

    <!-- Panel Chord yang Belum Digunakan (Tersembunyi jika kosong) -->
    <div
        x-show="unusedPlacements.length > 0"
        x-cloak
        class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 space-y-12"
    >
        <div class="flex items-center gap-8">
            <x-icon name="music-single" class="w-icon-control h-icon-control text-ink-secondary shrink-0" />
            <h2 class="text-title font-medium text-ink">Chord yang belum digunakan</h2>
        </div>

        <div class="flex flex-wrap gap-8">
            <template x-for="item in unusedPlacements" :key="'unused-' + item.id">
                @if ($mode === 'beat')
                    <span
                        :draggable="item.start_beat === null || item.start_beat === undefined"
                        @dragstart="if (item.start_beat === null || item.start_beat === undefined) startDragUnbeatedPlacement($event, item)"
                        @dragend="endDrag()"
                        :class="(item.start_beat === null || item.start_beat === undefined)
                            ? 'bg-surface-2 hover:bg-surface-3 border-accent/50 text-accent cursor-grab'
                            : 'bg-surface-2 border-subtle text-ink-secondary select-none'"
                        :title="(item.start_beat === null || item.start_beat === undefined)
                            ? 'Seret ke kotak beat baris terkait'
                            : 'Sudah memiliki beat, belum ditempatkan di lirik Simple Editor'"
                        class="border font-mono text-caption px-10 py-4 rounded-sm transition-colors"
                        x-text="item.chord_name"
                    ></span>
                @else
                    <span
                        class="bg-surface-2 border border-subtle text-ink-secondary font-mono text-caption px-10 py-4 rounded-sm select-none"
                        x-text="item.chord_name"
                    ></span>
                @endif
            </template>
        </div>
    </div>
</aside>

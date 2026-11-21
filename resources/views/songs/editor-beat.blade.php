<x-app-layout>
    <div
        x-data="beatEditor(@js($editorData))"
        class="max-w-content mx-auto w-full px-16 py-12 pb-[64px] space-y-12"
    >
        @include('songs.partials.editor-top-panel', [
            'mode'       => 'beat',
            'song'       => $song,
            'artists'    => $artists,
            'categories' => $categories,
        ])

        <!-- Tata Letak Utama Beat Editor: Kiri = Section + Baris + Grid Birama Berlapis (§7.2), Kanan = Pustaka Chord + Belum Digunakan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-20 items-start">
            <!-- KOLOM KIRI (8 cols): Daftar Section & Grid Birama Per Baris -->
            <div class="lg:col-span-8 space-y-16">
                <template x-if="sections.length === 0">
                    <div class="bg-surface-1 border border-subtle rounded-lg p-32 text-center space-y-12">
                        <x-icon name="music" class="w-icon-nav h-icon-nav text-ink-tertiary mx-auto" />
                        <h3 class="text-title font-medium text-ink">Belum Ada Section atau Lirik</h3>
                        <p class="text-caption text-ink-secondary max-w-[420px] mx-auto">
                            Tambahkan section dan baris lirik di Simple Editor terlebih dahulu sebelum mengatur ketukan beat.
                        </p>
                        <div class="pt-4">
                            <a
                                href="{{ route('songs.editor.simple', $song) }}"
                                class="h-[36px] px-16 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-caption font-medium inline-flex items-center gap-6 transition-colors"
                            >
                                <x-icon name="pencil" class="w-icon-chip h-icon-chip" />
                                <span>Buka Simple Editor</span>
                            </a>
                        </div>
                    </div>
                </template>

                <template x-for="section in sections" :key="section.id">
                    <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 space-y-14">
                        <!-- Header Section -->
                        <div class="flex items-center justify-between gap-8 pb-10 border-b border-subtle">
                            <span
                                class="font-mono text-caption uppercase tracking-wider px-10 py-4 rounded-sm bg-surface-2 border border-subtle text-accent font-medium"
                                x-text="section.name"
                            ></span>

                            <span
                                class="font-mono text-caption text-ink-tertiary"
                                x-text="(section.lines ? section.lines.length : 0) + ' baris'"
                            ></span>
                        </div>

                        <!-- Daftar Baris Lirik + Grid Birama Berlapis -->
                        <div class="space-y-14">
                            <template x-if="!section.lines || section.lines.length === 0">
                                <p class="text-caption text-ink-tertiary italic py-6">
                                    Belum ada baris lirik pada section ini. Tambahkan baris di Simple Editor.
                                </p>
                            </template>

                            <template x-for="line in section.lines" :key="line.id">
                                <div class="p-12 rounded-md bg-surface-2/40 border border-subtle space-y-10">
                                    <!-- Baris Lirik -->
                                    <p class="font-mono text-body text-ink leading-base" x-text="line.content"></p>

                                    <!-- Grid Birama (§7.2): Bar 1..N + Trailing Bar Kosong -->
                                    <div class="flex flex-wrap gap-8 items-stretch">
                                        <template
                                            x-for="bar in getLineGrid(line.id).bars"
                                            :key="line.id + '-bar-' + bar.barGlobal"
                                        >
                                            <div
                                                class="rounded-md border p-8 space-y-6 transition-colors"
                                                :class="bar.isEmptyTrailing ? 'bg-surface-2/30 border-subtle/60 border-dashed' : 'bg-surface-2/80 border-subtle'"
                                            >
                                                <!-- Header BAR -->
                                                <div class="flex items-center justify-between gap-8 px-4">
                                                    <span
                                                        class="font-mono text-caption font-medium text-ink-secondary"
                                                        x-text="'BAR ' + bar.barLokal"
                                                        :title="'Bar global ' + bar.barGlobal"
                                                    ></span>
                                                    <template x-if="bar.isEmptyTrailing">
                                                        <span class="font-mono text-caption text-ink-tertiary">
                                                            Bar Kosong
                                                        </span>
                                                    </template>
                                                </div>

                                                <!-- Sel Beat di dalam Bar (B kolom sesuai pembilang Time Signature) -->
                                                <div class="grid gap-6" :class="getBeatGridColsClass()">
                                                    <template
                                                        x-for="cell in bar.beats"
                                                        :key="line.id + '-beat-' + cell.beatGlobal"
                                                    >
                                                        <div
                                                            @dragover="onBeatDragOver($event, line.id, cell.beatGlobal)"
                                                            @dragleave="onBeatDragLeave(line.id, cell.beatGlobal)"
                                                            @drop="onBeatDrop($event, line, cell.beatGlobal)"
                                                            :data-beat="cell.beatGlobal"
                                                            class="min-w-[46px] min-h-[58px] rounded-sm border p-4 flex flex-col items-center justify-between gap-4 transition-colors"
                                                            :class="[
                                                                cell.isDownbeat ? 'border-l-accent' : '',
                                                                isBeatHighlighted(line.id, cell.beatGlobal) ? 'border-accent bg-accent-soft-bg' : 'border-subtle bg-surface-1'
                                                            ]"
                                                            :title="'Beat global #' + cell.beatGlobal"
                                                        >
                                                            <!-- Angka Beat dalam Bar -->
                                                            <span
                                                                class="font-mono text-caption leading-none select-none"
                                                                :class="cell.isDownbeat ? 'text-accent font-medium' : 'text-ink-tertiary'"
                                                                x-text="cell.beatInBar"
                                                            ></span>

                                                            <!-- Slot Terisi Chord -->
                                                            <template x-if="cell.placement !== null">
                                                                <div class="w-full flex flex-col items-center gap-4">
                                                                    <button
                                                                        type="button"
                                                                        draggable="true"
                                                                        @dragstart="startDragPlacedBeat($event, cell.placement)"
                                                                        @dragend="endDrag()"
                                                                        class="w-full px-6 py-4 rounded-sm bg-surface-3 hover:bg-surface-2 border border-accent/50 text-accent font-mono text-caption font-medium text-center leading-none cursor-grab select-none truncate"
                                                                        x-text="cell.placement.chord_name"
                                                                    ></button>

                                                                    <div class="flex items-center justify-center gap-4">
                                                                        <button
                                                                            type="button"
                                                                            @click="releaseBeat(line, cell.placement)"
                                                                            class="px-4 py-0 h-[20px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink text-caption leading-none cursor-pointer transition-colors"
                                                                            title="Lepas beat tanpa mengubah posisi huruf di Simple Editor"
                                                                        >
                                                                            Lepas
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            @click="deletePlacement(line, cell.placement)"
                                                                            class="h-[20px] w-[20px] rounded-sm bg-error-tint border border-error/30 text-error inline-flex items-center justify-center cursor-pointer transition-colors"
                                                                            title="Hapus chord seutuhnya dari kedua editor"
                                                                            aria-label="Hapus chord"
                                                                        >
                                                                            <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <!-- Slot Beat Kosong -->
                                                            <template x-if="cell.placement === null">
                                                                <span class="font-mono text-caption text-ink-disabled select-none py-4">+</span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            @include('songs.partials.editor-chord-sidebar', [
                'mode' => 'beat',
                'hint' => 'Seret chord ke kotak beat',
            ])
        </div>

        <!-- Modal Konfirmasi Timpa Beat Global (409 Conflict) -->
        <div
            x-show="conflictModal.open"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto flex items-center justify-center p-16"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="cancelConflictModal()"></div>

            <div class="relative w-full max-w-dialog bg-surface-1 border border-subtle rounded-lg p-24 space-y-16">
                <h3 class="text-heading font-medium text-ink">Beat Sudah Terisi</h3>
                <p class="text-body text-ink-secondary leading-base">
                    Beat global
                    <strong class="font-mono text-ink" x-text="'#' + conflictModal.startBeat"></strong>
                    sudah digunakan oleh chord
                    <strong class="font-mono text-accent" x-text="conflictModal.existingChordName"></strong>.
                    Apakah Anda ingin menimpanya dengan chord
                    <strong class="font-mono text-accent" x-text="conflictModal.incomingChordName"></strong>?
                </p>

                <div class="flex justify-end gap-10 pt-8">
                    <button
                        type="button"
                        @click="cancelConflictModal()"
                        class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-medium text-body cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmOverwriteConflict()"
                        class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on font-medium text-body cursor-pointer"
                    >
                        Timpa Chord
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

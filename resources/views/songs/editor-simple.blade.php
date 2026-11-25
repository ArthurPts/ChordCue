<x-app-layout>
    <div
        x-data="simpleEditor(@js($editorData))"
        class="max-w-content mx-auto w-full px-16 py-12 pb-[64px] space-y-12"
    >
        @include('songs.partials.editor-top-panel', [
            'mode'       => 'simple',
            'song'       => $song,
            'artists'    => $artists,
            'categories' => $categories,
        ])

        <!-- Tata Letak Utama Sesuai Gambar Referensi: Kiri = Area Lirik & Section, Kanan = Pustaka Chord + Chord Belum Digunakan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-20 items-start">
            <!-- KOLOM KIRI (8 cols): Daftar Section & Baris Lirik -->
            <div class="lg:col-span-8 space-y-16">
                <template x-if="sections.length === 0">
                    <div class="bg-surface-1 border border-subtle rounded-lg p-32 text-center space-y-12">
                        <x-icon name="music" class="w-icon-nav h-icon-nav text-ink-tertiary mx-auto" />
                        <h3 class="text-title font-medium text-ink">Belum Ada Section</h3>
                        <p class="text-caption text-ink-secondary max-w-[420px] mx-auto">
                            Tambahkan section seperti Intro, Verse, atau Chorus lalu isi baris lirik untuk mulai menaruh chord.
                        </p>
                    </div>
                </template>

                <template x-for="section in sections" :key="section.id">
                    <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 space-y-16">
                        <!-- Header Section -->
                        <div class="flex flex-wrap items-center justify-between gap-10 pb-12 border-b border-subtle">
                            <div class="flex items-center gap-8 min-w-0">
                                <template x-if="editingSectionId !== section.id">
                                    <div class="flex items-center gap-8">
                                        <span class="font-mono text-caption uppercase tracking-wider px-10 py-4 rounded-sm bg-surface-2 border border-subtle text-accent font-medium" x-text="section.name"></span>
                                        <button
                                            type="button"
                                            @click="startEditSection(section)"
                                            class="h-[32px] w-[32px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink inline-flex items-center justify-center cursor-pointer transition-colors"
                                            title="Ubah nama section"
                                        >
                                            <x-icon name="pencil" class="w-icon-chip h-icon-chip" />
                                        </button>
                                    </div>
                                </template>

                                <template x-if="editingSectionId === section.id">
                                    <div class="flex items-center gap-6">
                                        <input
                                            type="text"
                                            x-model="editingSectionName"
                                            @keydown.enter.prevent="saveSectionName(section)"
                                            class="h-[32px] px-10 rounded-sm bg-surface-2 border border-accent text-ink text-caption font-medium focus:outline-none"
                                        >
                                        <button
                                            type="button"
                                            @click="saveSectionName(section)"
                                            class="h-[32px] px-12 rounded-sm bg-accent text-accent-on text-caption font-medium cursor-pointer"
                                        >
                                            Simpan
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEditSection()"
                                            class="h-[32px] px-10 rounded-sm bg-surface-2 border border-subtle text-ink-secondary text-caption cursor-pointer"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center gap-8">
                                <button
                                    type="button"
                                    @click="openAddLine(section.id)"
                                    class="h-[32px] px-12 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-caption font-medium inline-flex items-center gap-4 cursor-pointer transition-colors"
                                >
                                    <x-icon name="plus" class="w-icon-chip h-icon-chip" />
                                    <span>Tambah Baris</span>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteSection(section)"
                                    class="h-[32px] px-12 rounded-sm bg-error-tint hover:bg-error-tint border border-error/30 text-error text-caption font-medium inline-flex items-center gap-4 cursor-pointer transition-colors"
                                >
                                    <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        <!-- Baris Lirik dalam Section -->
                        <div class="space-y-14">
                            <template x-if="section.lines.length === 0">
                                <p class="text-caption text-ink-tertiary italic">
                                    Belum ada baris lirik pada section ini.
                                </p>
                            </template>

                            <template x-for="line in section.lines" :key="line.id">
                                <div class="p-12 rounded-md bg-surface-2/60 border border-subtle space-y-10">
                                    <div class="flex items-start justify-between gap-12">
                                        <!-- Mode Tampil & Drag-Drop Karakter -->
                                        <template x-if="editingLineId !== line.id">
                                            <div class="flex-1 min-w-0 overflow-x-auto pb-4">
                                                <div class="inline-flex flex-wrap items-end gap-y-10">
                                                    <template x-for="(ch, charIdx) in getChars(line)" :key="line.id + '-' + charIdx">
                                                        <div
                                                            @dragover="onSlotDragOver($event, line.id, charIdx)"
                                                            @dragleave="onSlotDragLeave(line.id, charIdx)"
                                                            @drop="onSlotDrop($event, line, charIdx)"
                                                            class="inline-flex flex-col items-start transition-colors rounded-sm px-[1px]"
                                                            :class="isSlotHighlighted(line.id, charIdx) ? 'bg-accent-soft-bg/60 ring-1 ring-accent' : ''"
                                                        >
                                                            <!-- Baris Slot Chord Di Atas Huruf -->
                                                            <div class="min-h-[26px] flex items-center mb-4">
                                                                <template x-if="getPlacementAt(line, charIdx)">
                                                                    <span
                                                                        draggable="true"
                                                                        @dragstart="startDragPlacedChord($event, getPlacementAt(line, charIdx))"
                                                                        @dragend="endDrag()"
                                                                        class="inline-flex items-center gap-4 px-6 py-4 rounded-sm bg-accent-soft-bg border border-accent text-accent-soft-text font-mono text-caption font-medium leading-none cursor-grab select-none"
                                                                    >
                                                                        <span x-text="getPlacementAt(line, charIdx).chord_name"></span>
                                                                        <button
                                                                            type="button"
                                                                            @click.stop="deletePlacement(line, getPlacementAt(line, charIdx))"
                                                                            class="text-error hover:text-ink ml-4 cursor-pointer"
                                                                            title="Hapus chord"
                                                                            aria-label="Hapus chord"
                                                                        >
                                                                            <x-icon name="x" class="w-icon-chip h-icon-chip" />
                                                                        </button>
                                                                    </span>
                                                                </template>

                                                                <template x-if="!getPlacementAt(line, charIdx)">
                                                                    <span
                                                                        class="inline-block w-[12px] h-[22px] rounded-sm border border-transparent hover:border-subtle"
                                                                        title="Jatuhkan chord di sini"
                                                                    ></span>
                                                                </template>
                                                            </div>

                                                            <!-- Huruf Lirik (Putih Terang) -->
                                                            <span
                                                                class="font-mono text-title text-white leading-base select-none whitespace-pre"
                                                                x-text="ch === ' ' ? '\u00A0' : ch"
                                                            ></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Mode Edit Teks Baris Lirik -->
                                        <template x-if="editingLineId === line.id">
                                            <div class="flex-1 flex flex-wrap items-center gap-8">
                                                <input
                                                    type="text"
                                                    x-model="editingLineContent"
                                                    @keydown.enter.prevent="saveLineContent(line)"
                                                    class="flex-1 min-w-[200px] h-[36px] px-12 rounded-sm bg-surface-2 border border-accent text-ink font-mono text-body focus:outline-none"
                                                >
                                                <button
                                                    type="button"
                                                    @click="saveLineContent(line)"
                                                    class="h-[36px] px-14 rounded-sm bg-accent text-accent-on text-caption font-medium cursor-pointer"
                                                >
                                                    Simpan
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="cancelEditLine()"
                                                    class="h-[36px] px-12 rounded-sm bg-surface-2 border border-subtle text-ink-secondary text-caption cursor-pointer"
                                                >
                                                    Batal
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Tombol Aksi Baris (Edit Lirik & Hapus Baris) -->
                                        <div class="flex items-center gap-6 shrink-0 pt-4">
                                            <button
                                                type="button"
                                                @click="startEditLine(line)"
                                                class="h-[32px] w-[32px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink inline-flex items-center justify-center cursor-pointer transition-colors"
                                                title="Edit teks baris lirik"
                                            >
                                                <x-icon name="pencil" class="w-icon-chip h-icon-chip" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteLine(section, line)"
                                                class="h-[32px] w-[32px] rounded-sm bg-surface-2 hover:bg-error-tint border border-subtle hover:border-error/30 text-ink-secondary hover:text-error inline-flex items-center justify-center cursor-pointer transition-colors"
                                                title="Hapus baris lirik"
                                            >
                                                <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Form Tambah Baris Baru -->
                            <template x-if="addingLineSectionId === section.id">
                                <div class="p-12 rounded-md bg-surface-2 border border-accent/50 flex flex-wrap items-center gap-8">
                                    <input
                                        type="text"
                                        x-model="newLineContent"
                                        @keydown.enter.prevent="addLine(section)"
                                        placeholder="Ketik teks baris lirik baru..."
                                        class="flex-1 min-w-[200px] h-[36px] px-12 rounded-sm bg-surface-1 border border-subtle text-ink font-mono text-body focus:border-accent focus:outline-none"
                                    >
                                    <button
                                        type="button"
                                        @click="addLine(section)"
                                        class="h-[36px] px-14 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-caption font-medium cursor-pointer"
                                    >
                                        Simpan Baris
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelAddLine()"
                                        class="h-[36px] px-12 rounded-sm bg-surface-1 border border-subtle text-ink-secondary text-caption cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Tombol / Form Tambah Section Baru -->
                <div class="pt-4">
                    <template x-if="!showNewSectionForm">
                        <button
                            type="button"
                            @click="showNewSectionForm = true"
                            class="h-[40px] px-16 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium inline-flex items-center gap-6 cursor-pointer transition-colors"
                        >
                            <x-icon name="plus" class="w-icon-control h-icon-control" />
                            <span>Tambah Section</span>
                        </button>
                    </template>

                    <template x-if="showNewSectionForm">
                        <div class="bg-surface-1 border border-subtle rounded-lg p-16 flex flex-wrap items-center gap-10">
                            <input
                                type="text"
                                x-model="newSectionName"
                                @keydown.enter.prevent="addSection()"
                                placeholder="Nama section (contoh: Verse 1, Chorus, Bridge)"
                                class="flex-1 min-w-[220px] h-[40px] px-14 rounded-sm bg-surface-2 border border-subtle text-ink text-body focus:border-accent focus:outline-none"
                            >
                            <button
                                type="button"
                                @click="addSection()"
                                class="h-[40px] px-16 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium cursor-pointer"
                            >
                                Simpan Section
                            </button>
                            <button
                                type="button"
                                @click="showNewSectionForm = false; newSectionName = ''"
                                class="h-[40px] px-14 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary text-body cursor-pointer"
                            >
                                Batal
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            @include('songs.partials.editor-chord-sidebar', [
                'mode' => 'simple',
                'hint' => 'Seret chord ke atas huruf lirik',
            ])
        </div>

        <!-- Modal Konfirmasi Timpa (409 Conflict) -->
        <div
            x-show="conflictModal.open"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto flex items-center justify-center p-16"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="cancelConflictModal()"></div>

            <div class="relative w-full max-w-dialog bg-surface-1 border border-subtle rounded-lg p-24 space-y-16">
                <h3 class="text-heading font-medium text-ink">Posisi Huruf Sudah Terisi</h3>
                <p class="text-body text-ink-secondary leading-base">
                    Posisi huruf ini sudah ditempati chord
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

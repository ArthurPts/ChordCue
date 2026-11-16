<x-app-layout>
    <div
        x-data="simpleEditor(@js($editorData))"
        class="max-w-content mx-auto w-full px-16 py-12 pb-[64px] space-y-12"
    >
        <!-- Baris Navigasi Atas & Pengalih Mode -->
        <div class="flex flex-wrap items-center justify-between gap-8">
            <a
                href="{{ route('my.songs') }}"
                class="inline-flex items-center gap-6 text-caption text-ink-secondary hover:text-accent font-medium transition-colors"
            >
                <x-icon name="arrow-left" class="w-icon-chip h-icon-chip" />
                <span>Kembali ke Lagu Saya</span>
            </a>

            <div class="flex flex-wrap items-center gap-8">
                <!-- Switch Mode Editor -->
                <div class="inline-flex items-center gap-4 p-4 rounded-md bg-surface-1 border border-subtle">
                    <span class="h-[28px] px-10 rounded-sm bg-accent text-accent-on font-medium text-caption inline-flex items-center">
                        Simple Editor
                    </span>
                    <span
                        class="h-[28px] px-10 rounded-sm text-ink-disabled font-medium text-caption inline-flex items-center cursor-not-allowed"
                        title="Editor Beat akan tersedia pada tahap berikutnya"
                    >
                        Beat Editor
                    </span>
                </div>

                <!-- Buka Player -->
                <a
                    href="{{ route('songs.show', $song) }}"
                    class="h-[32px] px-12 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 transition-colors"
                >
                    <x-icon name="play" class="w-icon-chip h-icon-chip text-accent" />
                    <span>Buka Player</span>
                </a>
            </div>
        </div>

        <!-- Panel Atas Ringkas: Metadata + Backing Track MP3 (Satu Tombol Simpan) + Offset Nudge -->
        <section class="bg-surface-1 border border-subtle rounded-lg p-12 space-y-10">
            <div class="flex flex-wrap items-center justify-between gap-8 pb-8 border-b border-subtle">
                <h1 class="text-body font-medium text-ink">Editor Simple</h1>

                <div class="flex flex-wrap items-center gap-8">
                    <template x-if="metaMessage">
                        <span class="text-caption text-success font-medium" x-text="metaMessage"></span>
                    </template>
                    <template x-if="metaError">
                        <span class="text-caption text-error font-medium" x-text="metaError"></span>
                    </template>
                    <template x-if="audioMessage">
                        <span class="text-caption text-success font-medium" x-text="audioMessage"></span>
                    </template>
                    <template x-if="audioError">
                        <span class="text-caption text-error font-medium" x-text="audioError"></span>
                    </template>
                </div>
            </div>

            <form @submit.prevent="saveMetadata()" class="space-y-10">
                <!-- Baris 1: Metadata Lagu + Tombol Simpan Gabungan -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-end">
                    <!-- Judul Lagu -->
                    <div class="md:col-span-3 space-y-4">
                        <label for="editor_song_title" class="block text-caption font-medium text-ink-secondary">
                            Judul Lagu
                        </label>
                        <input
                            id="editor_song_title"
                            type="text"
                            x-model="metaForm.title"
                            maxlength="120"
                            required
                            class="w-full h-[32px] px-10 rounded-sm bg-surface-2 border border-subtle text-ink placeholder:text-ink-tertiary focus:border-accent focus:outline-none text-caption"
                            placeholder="Masukkan judul lagu"
                        >
                    </div>

                    <!-- Artist -->
                    <div class="md:col-span-2 space-y-4">
                        <label for="editor_song_artist" class="block text-caption font-medium text-ink-secondary">
                            Artist
                        </label>
                        <input
                            id="editor_song_artist"
                            type="text"
                            x-model="metaForm.artist"
                            list="editor-artists-list"
                            required
                            class="w-full h-[32px] px-10 rounded-sm bg-surface-2 border border-subtle text-ink placeholder:text-ink-tertiary focus:border-accent focus:outline-none text-caption"
                            placeholder="Nama artist"
                        >
                        <datalist id="editor-artists-list">
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->name }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Nada Dasar (Key) -->
                    <div class="md:col-span-1 space-y-4">
                        <label for="editor_song_key" class="block text-caption font-medium text-ink-secondary">
                            Nada Dasar
                        </label>
                        <input
                            id="editor_song_key"
                            type="text"
                            x-model="metaForm.default_key"
                            maxlength="45"
                            class="w-full h-[32px] px-8 rounded-sm bg-surface-2 border border-subtle text-ink font-mono placeholder:text-ink-tertiary focus:border-accent focus:outline-none text-caption"
                            placeholder="C / Am"
                        >
                    </div>

                    <!-- BPM (40 - 240) -->
                    <div class="md:col-span-1 space-y-4">
                        <label for="editor_song_bpm" class="block text-caption font-medium text-ink-secondary">
                            BPM
                        </label>
                        <input
                            id="editor_song_bpm"
                            type="number"
                            min="40"
                            max="240"
                            x-model.number="metaForm.bpm"
                            required
                            class="w-full h-[32px] px-8 rounded-sm bg-surface-2 border border-subtle text-ink font-mono focus:border-accent focus:outline-none text-caption"
                        >
                    </div>

                    <!-- Time Signature Dropdown (4/4, 3/4, 2/4, 6/8) -->
                    <div class="md:col-span-2 space-y-4">
                        <label for="editor_song_time_signature" class="block text-caption font-medium text-ink-secondary">
                            Time Signature
                        </label>
                        <div class="relative">
                            <select
                                id="editor_song_time_signature"
                                x-model="metaForm.time_signature"
                                required
                                class="w-full h-[32px] px-10 pr-[28px] rounded-sm bg-surface-2 border border-subtle text-ink font-mono text-caption appearance-none focus:border-accent focus:outline-none cursor-pointer"
                            >
                                <option value="4/4">4/4</option>
                                <option value="3/4">3/4</option>
                                <option value="2/4">2/4</option>
                                <option value="6/8">6/8</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-8 flex items-center pointer-events-none text-ink-secondary">
                                <x-icon name="chevron-down" class="w-icon-chip h-icon-chip" />
                            </div>
                        </div>
                    </div>

                    <!-- Visibilitas (Public / Private) -->
                    <div class="md:col-span-2 space-y-4">
                        <span class="block text-caption font-medium text-ink-secondary">Visibilitas</span>
                        <div class="grid grid-cols-2 gap-4 p-4 rounded-sm bg-surface-2 border border-subtle h-[32px]">
                            <button
                                type="button"
                                @click="metaForm.visibility = 'public'"
                                :class="metaForm.visibility === 'public' ? 'bg-accent text-accent-on font-medium' : 'text-ink-secondary hover:text-ink'"
                                class="rounded-sm text-caption inline-flex items-center justify-center transition-colors cursor-pointer"
                            >
                                Publik
                            </button>
                            <button
                                type="button"
                                @click="metaForm.visibility = 'private'"
                                :class="metaForm.visibility === 'private' ? 'bg-accent text-accent-on font-medium' : 'text-ink-secondary hover:text-ink'"
                                class="rounded-sm text-caption inline-flex items-center justify-center transition-colors cursor-pointer"
                            >
                                Privat
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Simpan Gabungan (Metadata + Backing Track MP3) -->
                    <div class="md:col-span-1">
                        <button
                            type="submit"
                            :disabled="isSavingMeta"
                            class="w-full h-[32px] px-10 rounded-sm bg-accent hover:bg-accent-hover text-accent-on font-medium text-caption inline-flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <span x-text="isSavingMeta ? '...' : 'Simpan'">Simpan</span>
                        </button>
                    </div>
                </div>

                <!-- Baris 2: Backing Track MP3 (Disimpan lewat tombol Simpan di atas) -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 pt-8 border-t border-subtle">
                    <div class="flex items-center gap-8 flex-wrap">
                        <div class="flex items-center gap-6">
                            <x-icon name="disc" class="w-icon-chip h-icon-chip text-accent shrink-0" />
                            <span class="text-caption font-medium text-ink">Backing Track</span>
                            <span class="text-caption text-ink-tertiary">
                                (MP3 maks. {{ config('chordcue.backing_track_max_mb', 15) }} MB)
                            </span>
                        </div>

                        <template x-if="backingTrackUrl">
                            <div class="flex items-center gap-8 flex-wrap">
                                <span class="px-8 py-4 rounded-sm bg-success-tint text-success text-caption font-medium inline-flex items-center gap-4">
                                    <x-icon name="check" class="w-icon-chip h-icon-chip" />
                                    <span>Terpasang</span>
                                </span>

                                <audio
                                    x-ref="backingAudioEl"
                                    :src="backingTrackUrl"
                                    preload="metadata"
                                    @loadedmetadata="onAudioMetadataLoaded($event)"
                                    @timeupdate="onAudioTimeUpdate($event)"
                                    @ended="onAudioEnded()"
                                    class="hidden"
                                ></audio>

                                <!-- Pemutar Audio Tema Gelap Ringkas -->
                                <div class="h-[28px] px-8 rounded-sm bg-surface-2 border border-subtle inline-flex items-center gap-8">
                                    <button
                                        type="button"
                                        @click="togglePreviewAudio()"
                                        class="text-accent hover:text-accent-hover inline-flex items-center justify-center cursor-pointer transition-colors"
                                        :aria-label="audioIsPlaying ? 'Jeda audio' : 'Putar audio'"
                                    >
                                        <span x-show="!audioIsPlaying" class="inline-flex">
                                            <x-icon name="play" class="w-icon-chip h-icon-chip" />
                                        </span>
                                        <span x-show="audioIsPlaying" class="inline-flex">
                                            <x-icon name="pause" class="w-icon-chip h-icon-chip" />
                                        </span>
                                    </button>

                                    <span
                                        class="font-mono text-caption text-ink-secondary whitespace-nowrap"
                                        x-text="formatAudioTime(audioCurrentTime) + ' / ' + formatAudioTime(audioDuration)"
                                    >0:00 / 0:00</span>

                                    <div class="ui-slider w-[84px]">
                                        <progress
                                            class="ui-progress"
                                            :max="Math.max(1, audioDuration)"
                                            :value="audioCurrentTime"
                                        ></progress>
                                        <input
                                            type="range"
                                            min="0"
                                            :max="Math.max(1, audioDuration)"
                                            step="0.1"
                                            :value="audioCurrentTime"
                                            @input="seekPreviewAudio($event.target.value)"
                                            aria-label="Posisi audio backing track"
                                        >
                                    </div>

                                    <button
                                        type="button"
                                        @click="toggleMutePreviewAudio()"
                                        class="text-ink-secondary hover:text-ink inline-flex items-center justify-center cursor-pointer transition-colors"
                                        :aria-label="audioIsMuted ? 'Nyalakan suara audio' : 'Bisukan audio'"
                                    >
                                        <span x-show="!audioIsMuted" class="inline-flex">
                                            <x-icon name="volume" class="w-icon-chip h-icon-chip" />
                                        </span>
                                        <span x-show="audioIsMuted" class="inline-flex">
                                            <x-icon name="volume-off" class="w-icon-chip h-icon-chip text-ink-tertiary" />
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!backingTrackUrl">
                            <span class="px-8 py-4 rounded-sm bg-surface-2 border border-subtle text-ink-tertiary text-caption">
                                Belum ada audio
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-8">
                        <label
                            for="editor_backing_track_input"
                            class="h-[28px] px-10 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                        >
                            <x-icon name="upload" class="w-icon-chip h-icon-chip text-accent" />
                            <span
                                class="max-w-[180px] truncate"
                                x-text="selectedAudioName ? selectedAudioName : (backingTrackUrl ? 'Ganti MP3...' : 'Pilih MP3...')"
                            >Pilih MP3...</span>
                            <input
                                id="editor_backing_track_input"
                                type="file"
                                x-ref="backingTrackInput"
                                accept=".mp3,audio/mpeg"
                                @change="onBackingTrackSelected($refs.backingTrackInput)"
                                class="sr-only"
                            >
                        </label>

                        <template x-if="selectedAudioName">
                            <span class="text-caption text-accent font-medium">Klik Simpan untuk menyimpan audio</span>
                        </template>

                        <template x-if="backingTrackUrl">
                            <button
                                type="button"
                                @click="deleteBackingTrack()"
                                :disabled="isUploadingAudio || isSavingMeta"
                                class="h-[28px] px-10 rounded-sm bg-error-tint hover:bg-error-tint border border-error/30 text-error text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                <span>Hapus Audio</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Baris 2b: Kategori Lagu (Genre, Mood, Wilayah — Disimpan lewat tombol Simpan) -->
                <div class="pt-8 border-t border-subtle space-y-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-8">
                        <div class="flex items-center gap-8 flex-wrap">
                            <div class="flex items-center gap-6 shrink-0">
                                <x-icon name="tag" class="w-icon-chip h-icon-chip text-accent shrink-0" />
                                <span class="text-caption font-medium text-ink">Kategori</span>
                            </div>

                            <template x-if="selectedCategories().length === 0">
                                <span class="px-8 py-4 rounded-sm bg-surface-2 border border-subtle text-ink-tertiary text-caption">
                                    Belum ada kategori
                                </span>
                            </template>

                            <template x-if="selectedCategories().length > 0">
                                <div class="flex flex-wrap items-center gap-4">
                                    <template x-for="cat in selectedCategories()" :key="cat.id">
                                        <span
                                            class="px-8 py-4 rounded-sm bg-accent-soft-bg border border-accent/30 text-accent-soft-text text-caption font-medium"
                                            x-text="cat.name"
                                        ></span>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <button
                            type="button"
                            @click="showCategories = !showCategories"
                            class="h-[28px] px-10 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 shrink-0 cursor-pointer transition-colors"
                        >
                            <span x-text="'Atur Kategori (' + metaForm.category_ids.length + ')'">Atur Kategori (0)</span>
                            <x-icon name="chevron-down" class="w-icon-chip h-icon-chip text-ink-secondary" />
                        </button>
                    </div>

                    <div
                        x-show="showCategories"
                        x-cloak
                        class="p-10 rounded-md bg-surface-2 border border-subtle space-y-8"
                    >
                        @php
                            $categoryGroups = [
                                'genre'    => 'Genre',
                                'mood'     => 'Mood',
                                'location' => 'Wilayah',
                            ];
                        @endphp

                        @foreach ($categoryGroups as $groupType => $groupLabel)
                            @php
                                $groupItems = $categories->where('type', $groupType);
                            @endphp
                            @if ($groupItems->isNotEmpty())
                                <div class="flex flex-col sm:flex-row sm:items-start gap-8">
                                    <span class="w-[64px] shrink-0 text-caption font-medium text-ink-secondary pt-4">
                                        {{ $groupLabel }}
                                    </span>
                                    <div class="flex flex-wrap gap-6">
                                        @foreach ($groupItems as $category)
                                            <button
                                                type="button"
                                                @click="toggleCategory({{ $category->id }})"
                                                :class="hasCategory({{ $category->id }}) ? 'bg-accent border-accent text-accent-on font-medium' : 'bg-surface-1 hover:bg-surface-3 border-subtle text-ink-secondary hover:text-ink'"
                                                class="h-[28px] px-8 rounded-sm border text-caption inline-flex items-center transition-colors cursor-pointer"
                                            >
                                                {{ $category->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </form>

            <!-- Baris 3: Offset Nudge Satu Baris Ringkas -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 pt-8 border-t border-subtle">
                <div class="flex items-center gap-8 shrink-0">
                    <span class="text-caption font-medium text-ink-secondary">Offset (Detik)</span>
                    <span class="text-caption text-ink-tertiary">min 0.000 dtk</span>
                </div>

                <div class="flex items-center gap-4 overflow-x-auto pb-4 md:pb-0">
                    <button
                        type="button"
                        @click="nudgeOffset(-1)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        -1s
                    </button>
                    <button
                        type="button"
                        @click="nudgeOffset(-0.1)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        -100ms
                    </button>
                    <button
                        type="button"
                        @click="nudgeOffset(-0.01)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        -10ms
                    </button>

                    <div class="inline-flex items-center gap-4 px-4 shrink-0">
                        <input
                            type="number"
                            min="0"
                            step="0.001"
                            x-model="offsetInput"
                            @input="onOffsetInputTyped()"
                            @change="commitOffsetInput()"
                            aria-label="Offset Backing Track dalam detik"
                            class="h-[28px] w-[84px] px-8 rounded-sm bg-surface-2 border border-subtle text-ink font-mono text-caption text-center focus:border-accent focus:outline-none"
                        >
                        <span class="font-mono text-caption text-ink-secondary">dtk</span>
                    </div>

                    <button
                        type="button"
                        @click="nudgeOffset(0.01)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        +10ms
                    </button>
                    <button
                        type="button"
                        @click="nudgeOffset(0.1)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        +100ms
                    </button>
                    <button
                        type="button"
                        @click="nudgeOffset(1)"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        +1s
                    </button>
                    <button
                        type="button"
                        @click="resetOffset()"
                        class="bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink font-mono text-caption rounded-sm px-8 h-[28px] whitespace-nowrap shrink-0 cursor-pointer transition-colors"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <template x-if="offsetStatus || offsetError">
                <div class="text-caption font-mono">
                    <span x-show="offsetStatus" class="text-success" x-text="offsetStatus"></span>
                    <span x-show="offsetError" class="text-error" x-text="offsetError"></span>
                </div>
            </template>
        </section>

        <!-- Umpan Balik Aksi Lirik / Placement -->
        <template x-if="editorMessage || editorError">
            <div
                class="p-12 rounded-md border text-caption font-medium flex items-center justify-between"
                :class="editorError ? 'bg-error-tint border-error/30 text-error' : 'bg-success-tint border-success/30 text-success'"
            >
                <span x-text="editorError || editorMessage"></span>
                <button type="button" @click="clearFeedback()" class="text-ink-secondary hover:text-ink cursor-pointer">
                    <x-icon name="x" class="w-icon-chip h-icon-chip" />
                </button>
            </div>
        </template>

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

            <!-- KOLOM KANAN (4 cols): Pustaka Chord + Chord yang belum digunakan di bawahnya -->
            <aside class="lg:col-span-4 lg:sticky lg:top-[80px] space-y-16">
                <!-- Panel Pustaka Chord -->
                <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 space-y-14">
                    <div class="flex items-center gap-8">
                        <x-icon name="music" class="w-icon-control h-icon-control text-accent shrink-0" />
                        <div>
                            <h2 class="text-title font-medium text-ink">Pustaka Chord</h2>
                            <p class="text-caption text-ink-tertiary">Seret chord ke atas huruf lirik</p>
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

                <!-- Panel Chord yang Belum Digunakan (Tersembunyi jika kosong, Read-Only) -->
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
                            <span
                                class="bg-surface-2 border border-subtle text-ink-secondary font-mono text-caption px-10 py-4 rounded-sm select-none"
                                x-text="item.chord_name"
                            ></span>
                        </template>
                    </div>
                </div>
            </aside>
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

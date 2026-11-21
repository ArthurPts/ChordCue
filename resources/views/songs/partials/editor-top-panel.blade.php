@props([
    'mode' => 'simple',
    'song',
    'artists',
    'categories',
])

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
        <!-- Switch Mode Editor (Simple Editor <-> Beat Editor) -->
        <div class="inline-flex items-center gap-4 p-4 rounded-md bg-surface-1 border border-subtle">
            @if ($mode === 'simple')
                <span class="h-[28px] px-10 rounded-sm bg-accent text-accent-on font-medium text-caption inline-flex items-center">
                    Simple Editor
                </span>
                <a
                    href="{{ route('songs.editor.beat', $song) }}"
                    class="h-[28px] px-10 rounded-sm text-ink-secondary hover:text-ink hover:bg-surface-2 font-medium text-caption inline-flex items-center transition-colors"
                >
                    Beat Editor
                </a>
            @else
                <a
                    href="{{ route('songs.editor.simple', $song) }}"
                    class="h-[28px] px-10 rounded-sm text-ink-secondary hover:text-ink hover:bg-surface-2 font-medium text-caption inline-flex items-center transition-colors"
                >
                    Simple Editor
                </a>
                <span class="h-[28px] px-10 rounded-sm bg-accent text-accent-on font-medium text-caption inline-flex items-center">
                    Beat Editor
                </span>
            @endif
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

<!-- Panel Atas Ringkas: Metadata + Backing Track MP3 (Satu Tombol Simpan) + Kategori + Offset Nudge -->
<section class="bg-surface-1 border border-subtle rounded-lg p-12 space-y-10">
    <div class="flex flex-wrap items-center justify-between gap-8 pb-8 border-b border-subtle">
        <h1 class="text-body font-medium text-ink">
            {{ $mode === 'beat' ? 'Editor Beat' : 'Editor Simple' }}
        </h1>

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

            <!-- Tombol Simpan Gabungan (Metadata + Kategori + Backing Track MP3) -->
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

<!-- Umpan Balik Aksi Lirik / Beat / Placement -->
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

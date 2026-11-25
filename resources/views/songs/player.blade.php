<x-app-layout>
    @php
        $initialOffsetFloat = $song->backing_track_offset !== null ? (float) $song->backing_track_offset : 0.0;
        $initialOffsetMs = (int) round($initialOffsetFloat * 1000);
        if ($initialOffsetMs === 0) {
            $initialOffsetFormatted = '0 ms (0.000s)';
        } else {
            $sign = $initialOffsetMs > 0 ? '+' : '-';
            $initialOffsetFormatted = $sign . abs($initialOffsetMs) . ' ms (' . $sign . number_format(abs($initialOffsetFloat), 3, '.', '') . 's)';
        }
        $cueMarginMs = (int) config('chordcue.cue_margin_ms', 1000);
        $speechSilenceThresholdDb = (float) config('chordcue.speech_silence_threshold_db', -40.0);
        $chordCueTrimMs = (int) config('chordcue.chord_cue_trim_ms', 0);
    @endphp

    <div
        x-data="chordsheetPlayer({
            songId: {{ $song->id }},
            songBpm: {{ $song->bpm ?? 80 }},
            cueMarginMs: {{ $cueMarginMs }},
            speechSilenceThresholdDb: {{ $speechSilenceThresholdDb }},
            chordCueTrimMs: {{ $chordCueTrimMs }},
            initialKey: @js($song->default_key ?? '-'),
            hasInitialBackingTrack: {{ $song->backing_track_path ? 'true' : 'false' }},
            initialBackingOffset: {{ $initialOffsetFloat }},
            initialTimeSigNum: {{ $song->time_signature_numerator ?? 4 }},
            initialTimeSigDen: {{ $song->time_signature_denominator ?? 4 }},
            hasInitialLyrics: {{ $song->sections->filter(fn($s) => $s->lines->isNotEmpty())->isNotEmpty() ? 'true' : 'false' }}
        })"
        @backing-track-offset-updated.window="setOffset($event.detail.offset)"
        class="max-w-content mx-auto w-full px-16 sm:px-24 py-24 sm:py-32 pb-[88px]"
    >
        <!-- Navigasi Kembali & Header Metadata Lagu (Rack-Mount Panel) -->
        <div class="mb-20">
            <div class="flex items-center justify-between mb-12">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center gap-6 text-caption text-ink-secondary hover:text-accent font-medium transition-colors"
                >
                    <x-icon name="arrow-left" class="w-icon-control h-icon-control" />
                    <span>Kembali ke Katalog Lagu</span>
                </a>

                @can('update', $song)
                    <div class="flex items-center gap-8">
                        <a
                            href="{{ route('songs.editor.simple', $song) }}"
                            class="h-[32px] px-14 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 transition-colors"
                        >
                            <x-icon name="pencil" class="w-icon-chip h-icon-chip text-ink-secondary" />
                            <span>Edit Lagu</span>
                        </a>
                    </div>
                @endcan
            </div>
        </div>

        <!-- Bilah Kontrol Tape Deck Sticky (Transport, LCD Counter + LED Beat Strip, Tempo, Transpose, Mute, Gulir Otomatis) -->
        <div x-ref="tapeDeck" class="sticky top-[72px] z-sticky bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 mb-24 space-y-16 shadow-lg">
            <!-- Baris 1: Tombol Putar/Berhenti & Layar LCD Counter -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-12 w-full min-w-0">
                <!-- Transport Buttons -->
                <div class="flex items-center gap-10 shrink-0">
                    <!-- Tombol Putar -->
                    <button
                        type="button"
                        disabled
                        @click="if (!isPlayDisabled) play()"
                        :disabled="isPlayDisabled"
                        class="h-[40px] px-24 rounded-sm bg-accent hover:bg-accent-hover text-accent-on font-medium text-nav-title inline-flex items-center gap-8 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="isPlayDisabled ? 'cursor-not-allowed' : 'cursor-pointer'"
                        :title="!hasCompletePlacements ? 'Putar metronom dan lirik (tanpa aba-aba chord)' : 'Putar sesi latihan'"
                    >
                        <x-icon name="play" class="w-icon-control h-icon-control fill-current shrink-0" />
                        <span>Putar</span>
                    </button>

                    <!-- Tombol Berhenti -->
                    <button
                        type="button"
                        disabled
                        @click="stop()"
                        :disabled="!isPlaying"
                        class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-medium text-nav-title inline-flex items-center gap-8 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="!isPlaying ? 'cursor-not-allowed' : 'cursor-pointer'"
                    >
                        <x-icon name="player-stop" class="w-icon-control h-icon-control fill-current shrink-0" />
                        <span>Berhenti</span>
                    </button>
                </div>

                <!-- Tampilan Layar LCD Tape Counter (Pesan Status Utama + Meter Birama/Beat + LED) -->
                <div class="flex-1 min-w-0 min-h-[40px] bg-surface-2 border border-subtle rounded-sm px-16 py-8 flex items-center justify-between gap-12 font-mono text-body">
                    <div class="flex items-center gap-8 min-w-0">
                        <span
                            class="w-[8px] h-[8px] rounded-full shrink-0"
                            :class="isPlaying ? 'bg-success animate-pulse' : (isPlayReady ? (!hasCompletePlacements ? 'bg-warning' : 'bg-success') : 'bg-ink-tertiary')"
                        ></span>
                        <span class="text-ink font-medium truncate" :title="statusMessage" x-text="statusMessage">Memuat data lagu...</span>
                    </div>

                    <!-- Indikator Bar/Beat & Strip LED Ketukan Birama -->
                    <div class="flex items-center gap-10 shrink-0">
                        <div class="hidden sm:flex items-center gap-6 text-caption text-ink-secondary border-r border-subtle pr-10">
                            <span>Birama <strong class="text-ink font-medium" x-text="isPlaying ? (isCountIn ? '00' : String(currentBar).padStart(2, '0')) : '--'">--</strong></span>
                            <span>Beat <strong class="text-accent font-medium" x-text="isPlaying ? currentBeat : '-'">-</strong></span>
                        </div>

                        <div class="flex items-center gap-4">
                            <template x-for="b in timeSignatureNumerator" :key="b">
                                <span
                                    class="w-[20px] h-[24px] rounded-sm border text-caption font-mono font-medium inline-flex items-center justify-center transition-colors"
                                    :class="isPlaying && currentBeat === b ? (b === 1 ? 'bg-accent border-accent text-accent-on' : 'bg-accent-soft-bg border-accent text-accent-soft-text') : 'bg-surface-3 border-subtle text-ink-tertiary'"
                                    x-text="b"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 mb-24 space-y-16">
            <!-- Baris 2: Pengaturan Tempo Sesi, Transpose Sesi, dan Tombol Mute -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
                <!-- Kontrol Tempo Sesi (BPM 40 - 240) -->
                <div class="lg:col-span-6 space-y-6">
                    <div class="flex items-center justify-between text-caption">
                        <label class="font-medium text-ink-secondary">Tempo Sesi (BPM)</label>
                        <span class="font-mono text-ink-tertiary">40 - 240 · Bawaan: {{ $song->bpm ?? 80 }} BPM</span>
                    </div>

                    <div class="flex items-center gap-8">
                        <!-- Nudge BPM Buttons -->
                        <div class="flex items-center gap-4 shrink-0">
                            <button
                                type="button"
                                @click="nudgeBpm(-5)"
                                :disabled="isPlaying || sessionBpm <= 40"
                                class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                title="-5 BPM"
                            >
                                -5
                            </button>
                            <button
                                type="button"
                                @click="nudgeBpm(-1)"
                                :disabled="isPlaying || sessionBpm <= 40"
                                class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                title="-1 BPM"
                            >
                                -1
                            </button>
                        </div>

                        <!-- Slider BPM (DESIGN_SYSTEM 6.17: <progress> memakai rentang 0..200 agar sejajar presisi dengan range 40..240) -->
                        <div class="ui-slider flex-1 min-w-0">
                            <progress
                                class="ui-progress"
                                max="200"
                                value="{{ max(0, ($song->bpm ?? 80) - 40) }}"
                                :value="Math.max(0, sessionBpm - 40)"
                            ></progress>
                            <input
                                type="range"
                                min="40"
                                max="240"
                                value="{{ $song->bpm ?? 80 }}"
                                x-model.number="sessionBpm"
                                @input="setBpm($event.target.value)"
                                :disabled="isPlaying"
                                aria-label="Tempo Sesi BPM"
                                class="disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                        </div>

                        <!-- Nilai BPM & Nudge Up -->
                        <div class="flex items-center gap-4 shrink-0">
                            <button
                                type="button"
                                @click="nudgeBpm(1)"
                                :disabled="isPlaying || sessionBpm >= 240"
                                class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                title="+1 BPM"
                            >
                                +1
                            </button>
                            <button
                                type="button"
                                @click="nudgeBpm(5)"
                                :disabled="isPlaying || sessionBpm >= 240"
                                class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                title="+5 BPM"
                            >
                                +5
                            </button>

                            <input
                                type="number"
                                min="40"
                                max="240"
                                x-model.number="sessionBpm"
                                @change="setBpm($event.target.value)"
                                :disabled="isPlaying"
                                aria-label="Input Numerik Tempo Sesi BPM"
                                class="w-[58px] h-[32px] px-6 rounded-sm bg-surface-2 border border-subtle text-ink font-mono text-caption text-center focus:border-accent focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed"
                            >

                            <!-- Reset BPM -->
                            <button
                                type="button"
                                @click="resetBpm()"
                                :disabled="isPlaying || sessionBpm === songBpm"
                                class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Kembali ke BPM lagu asli"
                            >
                                <x-icon name="refresh" class="w-icon-chip h-icon-chip" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Transpose Sesi (-11 s/d +11) -->
                <div class="lg:col-span-3 space-y-6">
                    <div class="flex items-center justify-between text-caption">
                        <label class="font-medium text-ink-secondary">Transpose Sesi</label>
                        <span class="font-mono text-ink-tertiary">-11 s/d +11</span>
                    </div>

                    <div class="flex items-center gap-6">
                        <button
                            type="button"
                            @click="stepTranspose(-1)"
                            :disabled="isPlaying || currentTranspose <= -11"
                            class="h-[32px] px-10 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            title="Turunkan 1 semitone"
                        >
                            -1
                        </button>

                        <div class="flex-1 h-[32px] bg-surface-2 border border-subtle rounded-sm px-10 flex items-center justify-center font-mono text-caption font-medium text-accent">
                            <span x-text="(currentTranspose > 0 ? '+' : '') + currentTranspose + ' semitone'">0 semitone</span>
                        </div>

                        <button
                            type="button"
                            @click="stepTranspose(1)"
                            :disabled="isPlaying || currentTranspose >= 11"
                            class="h-[32px] px-10 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            title="Naikkan 1 semitone"
                        >
                            +1
                        </button>

                        <button
                            type="button"
                            @click="resetTranspose()"
                            :disabled="isPlaying || currentTranspose === 0"
                            class="h-[32px] px-8 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink text-caption font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            title="Reset Transpose (0)"
                        >
                            <x-icon name="refresh" class="w-icon-chip h-icon-chip" />
                        </button>
                    </div>
                </div>

            </div>
            
            <!-- Baris 3: Kontrol Mute Toggles & Gulir Otomatis -->
            <div class="lg:col-span-3 space-y-6 pt-16 border-t border-subtle">
                <label class="block text-caption font-medium text-ink-secondary">Jalur Audio &amp; Gulir</label>

                <div class="flex items-center gap-8 flex-wrap">
                    <!-- Mute Metronom -->
                    <button
                        type="button"
                        @click="toggleMuteMetronome()"
                        class="h-[32px] px-12 rounded-sm border text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                        :class="isMetronomeMuted ? 'bg-surface-2/60 border-subtle text-ink-tertiary line-through' : 'bg-surface-2 hover:bg-surface-3 border-accent/40 text-accent'"
                    >
                        <x-icon name="metronome" class="w-icon-chip h-icon-chip shrink-0" />
                        <span>Metronom</span>
                    </button>

                    <!-- Mute Suara Chord (TTS) - Hanya aktif jika lagu memiliki chord -->
                    <button
                        type="button"
                        @click="toggleMuteChordVoice()"
                        :disabled="!hasCompletePlacements"
                        class="h-[32px] px-12 rounded-sm border text-caption font-medium inline-flex items-center gap-6 transition-colors"
                        :class="!hasCompletePlacements ? 'bg-surface-2/60 border-subtle text-ink-tertiary opacity-40 cursor-not-allowed' : (isChordVoiceMuted ? 'bg-surface-2/60 border-subtle text-ink-tertiary line-through cursor-pointer' : 'bg-surface-2 hover:bg-surface-3 border-accent/40 text-accent cursor-pointer')"
                        :title="!hasCompletePlacements ? 'Lagu tidak memiliki chord' : (isChordVoiceMuted ? 'Nyalakan Suara Chord' : 'Mute Suara Chord')"
                    >
                        <x-icon name="volume" class="w-icon-chip h-icon-chip shrink-0" />
                        <span>Suara Chord</span>
                    </button>

                    <!-- Mute Backing Track -->
                    <button
                        type="button"
                        @click="toggleMuteBackingTrack()"
                        :disabled="!hasBackingTrack || sessionBpm !== songBpm"
                        class="h-[32px] px-12 rounded-sm border text-caption font-medium inline-flex items-center gap-6 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="(!hasBackingTrack || sessionBpm !== songBpm || isBackingTrackMuted) ? 'bg-surface-2/60 border-subtle text-ink-tertiary line-through' : 'bg-surface-2 hover:bg-surface-3 border-accent/40 text-accent cursor-pointer'"
                        :title="sessionBpm !== songBpm ? 'Backing track dinonaktifkan otomatis karena BPM sesi berbeda dari BPM lagu' : (!hasBackingTrack ? 'Lagu tidak memiliki backing track' : 'Mute/Unmute Musik Pengiring')"
                    >
                        <x-icon name="disc" class="w-icon-chip h-icon-chip shrink-0" />
                        <span>Musik Pengiring</span>
                    </button>

                    <!-- Toggle Gulir Otomatis (Diatur sebelum Putar, terkunci saat diputar) -->
                    <button
                        type="button"
                        @click="toggleAutoScroll()"
                        :disabled="isPlaying"
                        :aria-pressed="autoScrollEnabled ? 'true' : 'false'"
                        class="h-[32px] px-12 rounded-sm border text-caption font-medium inline-flex items-center gap-6 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="autoScrollEnabled ? 'bg-surface-2 hover:bg-surface-3 border-accent/40 text-accent cursor-pointer' : 'bg-surface-2/60 border-subtle text-ink-tertiary cursor-pointer'"
                        :title="isPlaying ? 'Hentikan pemutaran terlebih dahulu untuk mengubah gulir otomatis' : (autoScrollEnabled ? 'Matikan gulir otomatis' : 'Aktifkan gulir otomatis')"
                    >
                        <x-icon name="chevron-down" class="w-icon-chip h-icon-chip shrink-0" />
                        <span>Gulir Otomatis</span>
                    </button>
                </div>

                <template x-if="sessionBpm !== songBpm && hasBackingTrack">
                    <p class="text-caption text-ink-tertiary">
                        Musik pengiring dinonaktifkan otomatis karena tempo sesi (<span class="font-mono text-ink" x-text="sessionBpm"></span> BPM) berbeda dari BPM asli lagu.
                    </p>
                </template>
            </div>
        </div>


        <div class="bg-surface-1 border border-subtle rounded-lg p-20 sm:p-24 space-y-16 mb-24">
            <!-- Judul & Artis -->
            <div>
                <div class="flex items-center gap-8 flex-wrap">
                    <h1 class="text-display font-medium text-ink">
                        {{ $song->title }}
                    </h1>
                    <span class="rounded-full px-10 py-4 text-caption font-medium border {{ $song->visibility === 'public' ? 'border-accent/40 bg-accent-soft-bg text-accent-soft-text' : 'border-subtle bg-surface-2 text-ink-tertiary' }}">
                        {{ $song->visibility === 'public' ? 'Publik' : 'Privat' }}
                    </span>
                </div>
                <p class="text-body text-ink-secondary font-medium mt-4">
                    {{ $song->artist ? $song->artist->name : 'Artis Tidak Diketahui' }}
                </p>
            </div>

            <!-- Indikator Metrik Musik & Offset Aktif (Hardware Display Rack) -->
            <div class="pt-12 border-t border-subtle flex flex-wrap items-center gap-8">
                <!-- Nada Dasar -->
                <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption flex items-center gap-6">
                    <span class="text-ink-tertiary font-medium">Nada:</span>
                    <span class="font-mono font-medium text-accent text-body" x-text="displayKey">{{ $song->default_key ?? '-' }}</span>
                </div>

                <!-- BPM Lagu Asli -->
                <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption flex items-center gap-6">
                    <x-icon name="metronome" class="w-icon-chip h-icon-chip text-ink-secondary shrink-0" />
                    <span class="font-mono font-medium text-ink">{{ $song->bpm ?? 80 }} <span class="text-ink-tertiary">BPM</span></span>
                </div>

                <!-- Birama -->
                <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption flex items-center gap-6">
                    <span class="text-ink-tertiary font-medium">Birama:</span>
                    <span class="font-mono font-medium text-ink">{{ $song->time_signature_numerator ?? 4 }}/{{ $song->time_signature_denominator ?? 4 }}</span>
                </div>

                <!-- Informasi Offset Backing Track (Selalu Tampil dalam ms & dtk) -->
                <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption flex items-center gap-6">
                    <x-icon name="disc" class="w-icon-chip h-icon-chip text-accent shrink-0" />
                    <span class="text-ink-secondary">Offset Backing Track:</span>
                    <span class="font-mono font-medium text-ink" x-text="formattedBackingOffset">{{ $initialOffsetFormatted }}</span>
                    @if (!$song->backing_track_path)
                        <span class="text-ink-tertiary">(Tanpa Audio)</span>
                    @endif
                </div>

                <!-- Informasi Offset Aba-aba (Cue Margin Config) -->
                <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption flex items-center gap-6">
                    <span class="text-ink-secondary">Offset Aba-aba:</span>
                    <span class="font-mono font-medium text-ink" x-text="cueMarginMs + ' ms'">{{ $cueMarginMs }} ms</span>
                </div>

                <!-- Status Kesiapan Audio (Hanya ditampilkan jika lagu memiliki penempatan chord) -->
                <template x-if="hasCompletePlacements">
                    <div>
                        <template x-if="audioReady && isPreloaded && !preloadFailed">
                            <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption text-success font-medium inline-flex items-center gap-6">
                                <x-icon name="check" class="w-icon-chip h-icon-chip text-success shrink-0" />
                                <span>Audio Siap</span>
                            </div>
                        </template>

                        <template x-if="isPreloading">
                            <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption text-accent font-medium inline-flex items-center gap-6">
                                <span class="w-[8px] h-[8px] rounded-full bg-accent animate-ping shrink-0"></span>
                                <span>Mengunduh Audio (<span x-text="preloadProgress.percent"></span>%)...</span>
                            </div>
                        </template>

                        <template x-if="!audioReady && !pollingTimeoutReached">
                            <div class="bg-surface-2 border border-subtle rounded-sm px-12 py-6 text-caption text-ink-secondary font-medium inline-flex items-center gap-6">
                                <span class="w-[8px] h-[8px] rounded-full bg-accent animate-ping shrink-0"></span>
                                <span>Menyiapkan Audio... (<span x-text="pendingChords"></span>)</span>
                            </div>
                        </template>

                        <template x-if="pollingTimeoutReached || preloadFailed">
                            <div class="bg-error-tint border border-error/30 rounded-sm px-12 py-6 text-caption text-error font-medium inline-flex items-center gap-6">
                                <x-icon name="alert-circle" class="w-icon-chip h-icon-chip text-error shrink-0" />
                                <span>Audio Belum Tersedia</span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Area Chord Sheet Interaktif & Lirik Lagu -->
        <div x-ref="chordSheet" class="bg-surface-1 border border-subtle rounded-lg p-24 sm:p-32">
            @php
                $hasSectionsWithLines = $song->sections->filter(fn($s) => $s->lines->isNotEmpty())->isNotEmpty();
            @endphp

            @if ($hasSectionsWithLines)
                <!-- Render Seluruh Section & Baris Lirik (TETAP TAMPIL UTUH MESKI TANPA CHORD) -->
                <div class="space-y-32">
                    @foreach ($song->sections as $section)
                        @if ($section->lines->isNotEmpty())
                            <div class="space-y-16">
                                <!-- Label Section dengan Garis Pemisah -->
                                <div class="flex items-center gap-12">
                                    <span class="text-caption font-mono font-medium tracking-wider text-accent-soft-text bg-accent-soft-bg border border-accent/20 px-10 py-4 rounded-sm uppercase shrink-0">
                                        {{ $section->name }}
                                    </span>
                                    <div class="flex-1 h-[1px] bg-subtle"></div>
                                </div>

                                <!-- Baris-baris Lirik & Chord (Setiap chord terikat vertikal langsung di atas huruf lirik target) -->
                                <div class="space-y-20">
                                    @foreach ($section->lines as $line)
                                        <div>
                                            <!-- Baris Dengan Penempatan Chord -->
                                            <template x-if="getLinePlacements({{ $line->id }}).length > 0">
                                                <div class="flex flex-wrap items-end gap-y-16">
                                                    <template x-for="unit in buildLineUnitsForLine({{ $line->id }})" :key="unit.key">
                                                        <div class="inline-flex flex-col items-start max-w-full">
                                                            <template x-if="unit.placement !== null">
                                                                <span
                                                                    :id="'placement-' + unit.placement.id"
                                                                    :class="getChordClass(unit.placement.id)"
                                                                    x-text="unit.placement.chord.name"
                                                                ></span>
                                                            </template>
                                                            <template x-if="unit.placement === null">
                                                                <span
                                                                    class="font-mono text-body font-medium px-6 py-4 border border-transparent rounded-sm leading-none mb-4 select-none opacity-0"
                                                                    aria-hidden="true"
                                                                >&nbsp;</span>
                                                            </template>
                                                            <span
                                                                class="font-mono text-title text-white whitespace-pre-wrap select-text leading-base transition-colors"
                                                                :class="unit.placement ? getLyricSliceClass(unit.placement.id) : 'text-white'"
                                                                x-text="unit.text"
                                                            ></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- Baris Lirik Polos Tanpa Chord (Tetap tampil utuh & terang) -->
                                            <template x-if="getLinePlacements({{ $line->id }}).length === 0">
                                                <div class="font-mono text-title text-white whitespace-pre-wrap select-text leading-base">{{ $line->content }}</div>
                                            </template>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <!-- Empty State HANYA jika lagu belum memiliki lirik sama sekali -->
                <div class="text-center py-32 px-16 max-w-[460px] mx-auto">
                    <x-icon name="music-off" class="w-icon-nav h-icon-nav text-ink-tertiary mx-auto mb-12" />
                    <h3 class="text-heading font-medium text-ink">Belum Ada Lirik</h3>
                    <p class="text-body text-ink-secondary mt-4 leading-base">
                        Lagu ini belum memiliki section atau baris lirik. Silakan tambahkan lirik pada editor lagu.
                    </p>

                    @can('update', $song)
                        <div class="mt-20">
                            <a
                                href="{{ route('songs.editor.simple', $song) }}"
                                class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-nav-title font-medium inline-flex items-center gap-6 transition-colors"
                            >
                                <x-icon name="pencil" class="w-icon-control h-icon-control" />
                                <span>Buka Editor Lagu</span>
                            </a>
                        </div>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

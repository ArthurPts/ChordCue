import { AudioEngine } from '../audio/audio-engine';

/**
 * Komponen Alpine.js untuk Halaman Player Latihan ChordCue.
 * Mengikuti arsitektur "Stage & Tape Deck" dan SPESIFIKASI_SISTEM_ChordCue.md §5.6, §7.4, & §9.3.
 *
 * @param {Object} config
 * @returns {Object}
 */
export function chordsheetPlayer(config = {}) {
    return {
        songId: config.songId,
        songBpm: Number(config.songBpm || 80),
        cueMarginMs: Number(config.cueMarginMs ?? 1000),
        speechSilenceThresholdDb: Number(config.speechSilenceThresholdDb ?? -40.0),
        chordCueTrimMs: Number(config.chordCueTrimMs ?? 0),
        hasInitialLyrics: Boolean(config.hasInitialLyrics || false),

        // Parameter Sesi Latihan
        sessionBpm: Number(config.songBpm || 80),
        currentTranspose: 0,
        displayKey: config.initialKey || '-',
        hasBackingTrack: Boolean(config.hasInitialBackingTrack || false),
        backingTrackOffset: Number(config.initialBackingOffset ?? 0.0),

        // Status Playback
        isPlaying: false,
        isStarting: false,
        playbackState: 'stopped', // 'stopped' | 'preloading' | 'playing'
        statusMessage: 'Memuat data lagu...',
        currentBeat: 0,
        currentBar: 0,
        isCountIn: false,
        timeSignatureNumerator: Number(config.initialTimeSigNum || 4),
        timeSignatureDenominator: Number(config.initialTimeSigDen || 4),

        // Status Mute Jalur Audio
        isMetronomeMuted: false,
        isChordVoiceMuted: false,
        isBackingTrackMuted: false,
        wasBackingTrackMutedBeforeTempoChange: false,

        // Data Timeline & Kesiapan Audio
        timelineData: null,
        isLoading: true,
        isPreloading: false,
        isPreloaded: false,
        preloadFailed: false,
        audioReady: false,
        pendingChords: 0,
        preloadProgress: { loaded: 0, total: 0, percent: 0 },

        // Polling Kesiapan Audio (Setiap 3 detik, maksimal 60 detik)
        pollingTimer: null,
        pollingSeconds: 0,
        pollingTimeoutReached: false,

        // Status Visual Penempatan Chord { [placementId]: 'normal' | 'active' | 'played' }
        chordStates: {},

        // Instance Audio Engine
        engine: null,

        /**
         * Inisialisasi komponen Alpine dan AudioEngine.
         */
        init() {
            this.engine = new AudioEngine({
                cueMarginMs: this.cueMarginMs,
                speechSilenceThresholdDb: this.speechSilenceThresholdDb,
                chordCueTrimMs: this.chordCueTrimMs,
                onChordStateChange: (placementId, state) => {
                    this.chordStates[placementId] = state;
                },
                onIntroTick: (timeToCountIn, timeToSong) => {
                    this.isCountIn = false;
                    this.currentBeat = 0;
                    this.currentBar = 0;
                    this.statusMessage = `Offset: ${timeToSong.toFixed(1)} dtk · metronom dalam ${timeToCountIn.toFixed(1)} dtk`;
                },
                onCountInTick: (countBeat, totalBeats, remainingOffset, offsetStarted, timeUntilOffsetStarts) => {
                    if (offsetStarted) {
                        this.statusMessage = `Hitungan Mundur: ${countBeat} / ${totalBeats} · Offset ${remainingOffset.toFixed(1)} dtk`;
                    } else {
                        this.statusMessage = `Hitungan Mundur: ${countBeat} / ${totalBeats} · Offset dalam ${timeUntilOffsetStarts.toFixed(1)} dtk`;
                    }
                },
                onBeatTick: (beatNumber, barNumber, isAccent) => {
                    const B = this.timeSignatureNumerator;
                    if (beatNumber <= 0) {
                        this.isCountIn = true;
                        const countBeat = beatNumber + B;
                        this.currentBeat = countBeat;
                        this.currentBar = 0;
                        this.statusMessage = `Hitungan Mundur: ${countBeat} / ${B}`;
                    } else {
                        this.isCountIn = false;
                        this.currentBeat = ((beatNumber - 1) % B) + 1;
                        this.currentBar = barNumber;
                        this.statusMessage = `Bar ${barNumber} • Beat ${this.currentBeat}`;
                    }
                },
                onPlaybackStateChange: (state) => {
                    this.playbackState = state;
                    this.isPlaying = (state === 'playing');
                    if (state === 'stopped') {
                        this.isCountIn = false;
                        this.currentBeat = 0;
                        this.currentBar = 0;
                        this.updateStatusMessage();
                    }
                },
                onSongEnded: () => {
                    this.statusMessage = 'Lagu selesai';
                    this.isPlaying = false;
                    this.playbackState = 'stopped';
                    this.isCountIn = false;
                },
            });

            this.fetchTimeline();
        },

        /**
         * Memeriksa apakah lagu memiliki setidaknya satu section dan baris lirik.
         */
        get hasLyrics() {
            if (this.timelineData && this.timelineData.sections) {
                for (const section of this.timelineData.sections) {
                    if (section.lines && section.lines.length > 0) {
                        return true;
                    }
                }
                return false;
            }
            return this.hasInitialLyrics;
        },

        /**
         * Memeriksa apakah lagu memiliki setidaknya satu placement lengkap (position & start_beat)
         * yang membutuhkan pemuatan dan penjadwalan audio aba-aba chord.
         */
        get hasCompletePlacements() {
            if (!this.timelineData || !this.timelineData.sections) return false;
            for (const section of this.timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.placements) {
                            for (const placement of line.placements) {
                                if (placement.position !== null && placement.start_beat !== null) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
            return false;
        },

        /**
         * Menentukan apakah player siap diputar:
         * - Jika lagu memiliki chord lengkap: wajib lirik ada, audio chord & backing track siap serta ter-preload.
         * - Jika lagu tanpa chord lengkap: metronom (dan backing track jika ada) tetap dapat diputar.
         */
        get isPlayReady() {
            if (this.isLoading || this.isPreloading || this.preloadFailed) {
                return false;
            }

            if (this.hasCompletePlacements) {
                return this.audioReady && this.isPreloaded && !this.pollingTimeoutReached;
            }

            return this.isPreloaded;
        },

        /**
         * Menentukan apakah tombol Play harus dinonaktifkan.
         */
        get isPlayDisabled() {
            return this.isPlaying || this.isStarting || !this.isPlayReady;
        },

        /**
         * Format tampilan offset aktif dengan satuan milidetik dan detik secara akurat.
         */
        get formattedBackingOffset() {
            const sec = Number(this.backingTrackOffset || 0);
            const ms = Math.round(sec * 1000);
            const sign = ms > 0 ? '+' : (ms < 0 ? '-' : '');
            const absMs = Math.abs(ms);
            const absSec = Math.abs(sec).toFixed(3);
            if (ms === 0) {
                return '0 ms (0.000s)';
            }
            return `${sign}${absMs} ms (${sign}${absSec}s)`;
        },

        /**
         * Memperbarui nilai offset playback secara reaktif dan menyinkronkannya ke AudioEngine.
         *
         * @param {number|string} newOffset
         */
        setOffset(newOffset) {
            const numericOffset = Number(newOffset);
            if (Number.isNaN(numericOffset)) return;
            this.backingTrackOffset = numericOffset;
            if (this.timelineData && this.timelineData.song) {
                this.timelineData.song.backing_track_offset = numericOffset;
            }
            if (this.engine) {
                this.engine.setBackingTrackOffset(numericOffset);
            }
        },

        /**
         * Memperbarui teks status LCD counter secara akurat sesuai keadaan aktual sistem.
         */
        updateStatusMessage() {
            if (this.isPlaying) return;

            if (this.isLoading) {
                this.statusMessage = 'Memuat data lagu...';
                return;
            }

            if (!this.hasLyrics) {
                this.statusMessage = 'Belum ada lirik';
                return;
            }

            if (!this.hasCompletePlacements) {
                this.statusMessage = 'Lagu ini belum memiliki penempatan chord yang lengkap. Pemutaran aba-aba chord dinonaktifkan.';
                return;
            }

            if (this.pollingTimeoutReached) {
                this.statusMessage = 'Audio chord belum tersedia (timeout)';
                return;
            }

            if (this.preloadFailed) {
                this.statusMessage = 'Gagal mengunduh file audio chord. Pemutaran dinonaktifkan.';
                return;
            }

            if (!this.audioReady || this.pendingChords > 0) {
                this.statusMessage = `Menyiapkan audio chord (${this.pendingChords})...`;
                return;
            }

            if (this.isPreloading) {
                this.statusMessage = `Mengunduh audio: ${this.preloadProgress.loaded}/${this.preloadProgress.total}`;
                return;
            }

            if (this.isPlayReady) {
                this.statusMessage = 'Siap diputar';
                return;
            }

            this.statusMessage = 'Menunggu kesiapan player...';
        },

        /**
         * Mengambil data timeline lagu dari server via AJAX.
         */
        async fetchTimeline() {
            this.isLoading = true;
            this.isPreloaded = false;
            this.preloadFailed = false;
            this.stopPolling();
            this.updateStatusMessage();

            try {
                const response = await fetch(`/songs/${this.songId}/timeline?transpose=${this.currentTranspose}`);
                if (!response.ok) {
                    throw new Error(`Gagal memuat timeline (Status: ${response.status})`);
                }

                const data = await response.json();
                this.timelineData = data;
                this.displayKey = data.song?.key || '-';
                this.audioReady = Boolean(data.audio_ready);
                this.pendingChords = Number(data.pending_chords || 0);
                this.timeSignatureNumerator = Number(data.song?.time_signature?.numerator || 4);
                this.timeSignatureDenominator = Number(data.song?.time_signature?.denominator || 4);
                this.hasBackingTrack = Boolean(data.song?.backing_track);

                const rawOffset = data.song?.backing_track_offset ?? data.song?.backing_track?.offset;
                this.backingTrackOffset = rawOffset !== null && rawOffset !== undefined ? Number(rawOffset) : 0.0;

                this.engine.setSessionData(data, this.sessionBpm);

                // Polling hanya jika lagu memiliki placement lengkap dan audio masih pending
                if (this.hasCompletePlacements && !this.audioReady) {
                    this.startPollingAudio();
                } else {
                    // Tetap jalankan preload (untuk backing track bila ada, atau langsung siap metronom tanpa chord)
                    await this.preloadAssets();
                }
            } catch (err) {
                console.error(err);
                this.statusMessage = 'Gagal memuat data lagu.';
            } finally {
                this.isLoading = false;
                this.updateStatusMessage();
            }
        },

        /**
         * Memulai polling pemeriksaan kesiapan audio setiap 3 detik (maks 60 detik).
         */
        startPollingAudio() {
            if (this.pollingTimer) return;

            this.pollingSeconds = 0;
            this.pollingTimeoutReached = false;
            this.updateStatusMessage();

            this.pollingTimer = setInterval(async () => {
                this.pollingSeconds += 3;

                if (this.pollingSeconds >= 60) {
                    this.stopPolling();
                    this.pollingTimeoutReached = true;
                    this.updateStatusMessage();
                    return;
                }

                try {
                    const response = await fetch(`/songs/${this.songId}/timeline?transpose=${this.currentTranspose}`);
                    if (!response.ok) return;

                    const data = await response.json();
                    this.timelineData = data;
                    this.audioReady = Boolean(data.audio_ready);
                    this.pendingChords = Number(data.pending_chords || 0);

                    if (this.audioReady) {
                        this.stopPolling();
                        await this.preloadAssets();
                    } else {
                        this.updateStatusMessage();
                    }
                } catch (e) {
                    console.error('Error saat polling audio:', e);
                }
            }, 3000);
        },

        /**
         * Menghentikan interval polling kesiapan audio.
         */
        stopPolling() {
            if (this.pollingTimer) {
                clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }
        },

        /**
         * Mengunduh dan mendecode seluruh aset audio sebelum sesi diputar.
         */
        async preloadAssets() {
            if (!this.timelineData) {
                this.isPreloaded = false;
                this.preloadFailed = false;
                this.updateStatusMessage();
                return;
            }

            this.isPreloading = true;
            this.isPreloaded = false;
            this.preloadFailed = false;
            this.playbackState = 'preloading';
            this.updateStatusMessage();

            try {
                const result = await this.engine.preloadTimelineAssets(this.timelineData, (loaded, total) => {
                    this.preloadProgress.loaded = loaded;
                    this.preloadProgress.total = total;
                    this.preloadProgress.percent = total > 0 ? Math.round((loaded / total) * 100) : 100;
                    this.statusMessage = `Mengunduh audio: ${loaded}/${total}`;
                });

                if (result && result.success && this.engine.canPlay()) {
                    this.isPreloaded = true;
                    this.preloadFailed = false;
                } else {
                    this.isPreloaded = false;
                    this.preloadFailed = true;
                }
            } catch (err) {
                console.error('Gagal saat preload:', err);
                this.isPreloaded = false;
                this.preloadFailed = true;
            } finally {
                this.isPreloading = false;
                this.playbackState = 'stopped';
                this.updateStatusMessage();
            }
        },

        /**
         * Memulai pemutaran audio sesi latihan.
         * Mengizinkan pemutaran metronom (dan backing track jika ada) meski lagu belum memiliki chord,
         * serta memblokir pemutaran jika audio chord yang dibutuhkan atau backing track gagal dimuat.
         */
        async play() {
            if (
                this.isPlayDisabled ||
                !this.isPlayReady ||
                !this.isPreloaded ||
                this.preloadFailed
            ) {
                return;
            }

            if (this.hasCompletePlacements && !this.audioReady) {
                return;
            }

            this.isStarting = true;
            try {
                // Bangkitkan AudioContext secara sinkron tepat di dalam click user gesture
                this.engine.initContext();
                this.engine.setSessionData(this.timelineData, this.sessionBpm);

                this.engine.setMuteMetronome(this.isMetronomeMuted);
                this.engine.setMuteChordVoice(this.isChordVoiceMuted);

                const isDifferentBpm = (this.sessionBpm !== this.songBpm);
                const shouldMuteBacking = this.isBackingTrackMuted || isDifferentBpm || !this.hasBackingTrack;
                this.engine.setMuteBackingTrack(shouldMuteBacking);

                this.chordStates = {};
                const started = await this.engine.play();
                if (!started) {
                    this.isPlaying = false;
                    this.playbackState = 'stopped';
                    this.updateStatusMessage();
                }
            } finally {
                this.isStarting = false;
            }
        },

        /**
         * Menghentikan pemutaran audio dan mereset visual chord sheet.
         */
        stop() {
            if (this.engine) {
                this.engine.stop();
            }
            this.chordStates = {};
            this.isPlaying = false;
            this.playbackState = 'stopped';
            this.updateStatusMessage();
        },

        /**
         * Mengubah tempo sesi latihan (BPM 40 - 240).
         * Hanya dapat diubah saat lagu berhenti.
         * Jika tempo sesi ≠ tempo lagu, backing track otomatis di-mute.
         *
         * @param {number|string} newBpm
         */
        setBpm(newBpm) {
            if (this.isPlaying) return;

            const clamped = Math.min(240, Math.max(40, parseInt(newBpm, 10) || this.songBpm));
            const wasDifferent = (this.sessionBpm !== this.songBpm);
            this.sessionBpm = clamped;
            const isNowDifferent = (this.sessionBpm !== this.songBpm);

            if (isNowDifferent && !wasDifferent) {
                this.wasBackingTrackMutedBeforeTempoChange = this.isBackingTrackMuted;
                this.isBackingTrackMuted = true;
            } else if (!isNowDifferent && wasDifferent) {
                this.isBackingTrackMuted = this.wasBackingTrackMutedBeforeTempoChange;
            }

            if (this.engine && this.timelineData) {
                this.engine.setSessionData(this.timelineData, this.sessionBpm);
            }
        },

        nudgeBpm(delta) {
            this.setBpm(this.sessionBpm + delta);
        },

        resetBpm() {
            this.setBpm(this.songBpm);
        },

        /**
         * Mengubah transpose nada sesi latihan (-11 s/d +11 semitone).
         * Hanya dapat diubah saat lagu berhenti.
         *
         * @param {number|string} steps
         */
        setTranspose(steps) {
            if (this.isPlaying) return;

            const clamped = Math.min(11, Math.max(-11, parseInt(steps, 10) || 0));
            if (clamped === this.currentTranspose) return;

            this.currentTranspose = clamped;
            this.fetchTimeline();
        },

        stepTranspose(delta) {
            this.setTranspose(this.currentTranspose + delta);
        },

        resetTranspose() {
            this.setTranspose(0);
        },

        /**
         * Mengatur status mute pada metronom.
         */
        toggleMuteMetronome() {
            this.isMetronomeMuted = !this.isMetronomeMuted;
            if (this.engine) {
                this.engine.setMuteMetronome(this.isMetronomeMuted);
            }
        },

        /**
         * Mengatur status mute pada suara aba-aba chord.
         */
        toggleMuteChordVoice() {
            if (!this.hasCompletePlacements) return;
            this.isChordVoiceMuted = !this.isChordVoiceMuted;
            if (this.engine) {
                this.engine.setMuteChordVoice(this.isChordVoiceMuted);
            }
        },

        /**
         * Mengatur status mute pada musik pengiring (backing track).
         * Tidak dapat diaktifkan jika tempo sesi ≠ BPM lagu atau tanpa backing track.
         */
        toggleMuteBackingTrack() {
            if (!this.hasBackingTrack || this.sessionBpm !== this.songBpm) return;
            this.isBackingTrackMuted = !this.isBackingTrackMuted;
            if (this.engine) {
                this.engine.setMuteBackingTrack(this.isBackingTrackMuted);
            }
        },

        /**
         * Mengambil data placement lengkap untuk sebuah baris lirik.
         *
         * @param {number} lineId
         * @returns {Array}
         */
        getLinePlacements(lineId) {
            if (!this.timelineData || !this.timelineData.sections) return [];
            for (const section of this.timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.id === lineId) {
                            return line.placements || [];
                        }
                    }
                }
            }
            return [];
        },

        /**
         * Membentuk unit berpasangan (chord + potongan lirik di bawahnya) untuk baris lirik tertentu.
         *
         * @param {number} lineId
         * @returns {Array}
         */
        buildLineUnitsForLine(lineId) {
            if (!this.timelineData || !this.timelineData.sections) return [];
            for (const section of this.timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.id === lineId) {
                            return this.buildLineUnits(line);
                        }
                    }
                }
            }
            return [];
        },

        /**
         * Menentukan kelas CSS visual untuk lencana penempatan chord (3 status seragam lebar):
         * - Normal: lencana gelap subtle agar tidak menggeser teks berikutnya
         * - Aktif: sorotan biru aksen solid saat beat berlangsung
         * - Sudah Dimainkan: redup transparan setelah beat lewat
         *
         * @param {number} placementId
         * @returns {string}
         */
        getChordClass(placementId) {
            const state = this.chordStates[placementId] || 'normal';
            if (state === 'active') {
                return 'bg-accent border border-accent text-accent-on font-mono text-body font-medium px-6 py-4 rounded-sm leading-none mb-4 shadow-md transition-colors';
            }
            if (state === 'played') {
                return 'bg-transparent border border-transparent text-ink-tertiary opacity-60 font-mono text-body font-medium px-6 py-4 rounded-sm leading-none mb-4 transition-colors';
            }
            return 'bg-surface-2 border border-subtle text-accent font-mono text-body font-medium px-6 py-4 rounded-sm leading-none mb-4 cursor-default transition-colors';
        },

        /**
         * Menentukan kelas CSS visual untuk potongan lirik tepat di bawah chord.
         * Sesuai ketentuan: warna teks lirik tidak berubah warna saat pergantian chord dan selalu putih terang kontras.
         *
         * @param {number} placementId
         * @returns {string}
         */
        getLyricSliceClass(placementId) {
            return 'text-white';
        },

        /**
         * Memecah baris lirik menjadi unit kolom vertikal (chord di atas potongan liriknya)
         * berdasarkan indeks karakter `position`, sehingga setiap chord menempel tepat
         * di atas huruf target tanpa pernah terdorong melenceng ke kanan oleh panjang chord/padding.
         *
         * @param {Object} line
         * @returns {Array}
         */
        buildLineUnits(line) {
            const content = line?.content ?? '';
            const placements = (line?.placements ?? []).filter(
                (p) => p && p.position !== null && p.position !== undefined
            );

            if (placements.length === 0) {
                return [];
            }

            const sorted = [...placements].sort((a, b) => Number(a.position) - Number(b.position));
            const units = [];
            let cursor = 0;

            for (let i = 0; i < sorted.length; i++) {
                const placement = sorted[i];
                const pos = Math.max(cursor, Number(placement.position));
                const nextPos = (i + 1 < sorted.length)
                    ? Math.max(pos, Number(sorted[i + 1].position))
                    : content.length;

                // Teks lirik sebelum placement pertama (tanpa badge chord)
                if (pos > cursor) {
                    const gapEnd = Math.min(pos, content.length);
                    let leadText = content.slice(cursor, gapEnd);
                    if (pos > content.length) {
                        leadText += ' '.repeat(pos - Math.max(cursor, content.length));
                    }
                    units.push({
                        key: `lead-${line.id}-${cursor}`,
                        placement: null,
                        text: leadText.length > 0 ? leadText : '\u00A0',
                    });
                    cursor = pos;
                }

                // Potongan lirik milik chord ini sampai tepat sebelum chord berikutnya
                const sliceStart = Math.min(pos, content.length);
                const sliceEnd = Math.min(nextPos, content.length);
                let lyricSlice = content.slice(sliceStart, sliceEnd);

                if (nextPos > Math.max(pos, content.length)) {
                    lyricSlice += ' '.repeat(nextPos - Math.max(pos, content.length));
                }

                if (lyricSlice.length === 0) {
                    lyricSlice = '\u00A0';
                }

                units.push({
                    key: `p-${placement.id}`,
                    placement,
                    text: lyricSlice,
                });

                cursor = nextPos;
            }

            return units;
        },
    };
}

/**
 * ChordCue Web Audio Engine
 *
 * Modul Vanilla JS mandiri untuk penjadwalan presisi audio lirik, metronom count-in,
 * aba-aba ucapan chord (TTS), dan sinkronisasi backing track menggunakan Web Audio API.
 *
 * Mematuhi CHORDCUE_RULES.md §8 dan SPESIFIKASI_SISTEM_ChordCue.md §7.4 & §10.1.
 */

export class AudioEngine {
    constructor(options = {}) {
        this.cueMarginMs = options.cueMarginMs ?? 50;
        this.onChordStateChange = options.onChordStateChange || null;
        this.onBeatTick = options.onBeatTick || null;
        this.onPlaybackStateChange = options.onPlaybackStateChange || null;
        this.onSongEnded = options.onSongEnded || null;

        this.audioContext = null;
        this.masterGain = null;
        this.metronomeGain = null;
        this.chordVoiceGain = null;
        this.backingTrackGain = null;

        // Status Mute
        this.isMetronomeMuted = false;
        this.isChordVoiceMuted = false;
        this.isBackingTrackMuted = false;

        // Buffer audio yang di-cache
        this.chordBuffers = new Map(); // audio_url -> AudioBuffer
        this.backingTrackBuffer = null;

        // Data Sesi Aktif
        this.timelineData = null;
        this.bpm = 80;
        this.timeSignatureNumerator = 4;
        this.timeSignatureDenominator = 4;
        this.backingTrackOffset = 0.0; // Detik

        // State Penjadwalan
        this.isPlaying = false;
        this.t0 = 0; // Waktu audioContext saat sesi dimulai
        this.mulaiLagu = 0;
        this.mulaiCountIn = 0;
        this.waktuSelesaiLagu = 0;

        this.lookaheadMs = 25.0; // Interval pemeriksaan timer (ms)
        this.scheduleAheadTime = 0.15; // Jendela lookahead scheduling (detik)
        this.schedulerIntervalId = null;

        // Antrean kejadian penjadwalan
        this.scheduledBeats = [];
        this.scheduledChords = [];
        this.activeBackingSource = null;
        this.activeSources = [];
    }

    /**
     * Inisialisasi AudioContext dan node routing.
     */
    initContext() {
        if (!this.audioContext) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContextClass();

            this.masterGain = this.audioContext.createGain();
            this.masterGain.connect(this.audioContext.destination);

            this.metronomeGain = this.audioContext.createGain();
            this.metronomeGain.connect(this.masterGain);

            this.chordVoiceGain = this.audioContext.createGain();
            this.chordVoiceGain.connect(this.masterGain);

            this.backingTrackGain = this.audioContext.createGain();
            this.backingTrackGain.connect(this.masterGain);

            this.updateMuteGains();
        }

        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }
    }

    /**
     * Mengatur nilai mute pada masing-masing gain node.
     */
    updateMuteGains() {
        if (!this.audioContext) return;
        const now = this.audioContext.currentTime;

        if (this.metronomeGain) {
            this.metronomeGain.gain.setValueAtTime(this.isMetronomeMuted ? 0 : 1, now);
        }
        if (this.chordVoiceGain) {
            this.chordVoiceGain.gain.setValueAtTime(this.isChordVoiceMuted ? 0 : 1, now);
        }
        if (this.backingTrackGain) {
            this.backingTrackGain.gain.setValueAtTime(this.isBackingTrackMuted ? 0 : 1, now);
        }
    }

    setMuteMetronome(muted) {
        this.isMetronomeMuted = Boolean(muted);
        this.updateMuteGains();
    }

    setMuteChordVoice(muted) {
        this.isChordVoiceMuted = Boolean(muted);
        this.updateMuteGains();
    }

    setMuteBackingTrack(muted) {
        this.isBackingTrackMuted = Boolean(muted);
        this.updateMuteGains();
    }

    /**
     * Memuat dan mendecode satu berkas audio dari URL menjadi AudioBuffer.
     *
     * @param {string} url
     * @returns {Promise<AudioBuffer>}
     */
    async loadAudioBuffer(url) {
        this.initContext();
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Gagal mengunduh audio dari ${url} (status: ${response.status})`);
        }
        const arrayBuffer = await response.arrayBuffer();
        return await this.audioContext.decodeAudioData(arrayBuffer);
    }

    /**
     * Melakukan preload buffer untuk semua audio chord dan backing track.
     *
     * @param {Object} timelineData
     * @param {Function} onProgress (loaded, total)
     */
    async preloadTimelineAssets(timelineData, onProgress = null) {
        this.timelineData = timelineData;
        this.initContext();

        const uniqueUrls = new Set();
        if (timelineData.sections) {
            for (const section of timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.placements) {
                            for (const placement of line.placements) {
                                if (placement.chord && placement.chord.audio_url) {
                                    uniqueUrls.add(placement.chord.audio_url);
                                }
                            }
                        }
                    }
                }
            }
        }

        const backingTrackUrl = timelineData.song?.backing_track?.url;
        let totalItems = uniqueUrls.size + (backingTrackUrl ? 1 : 0);
        let loadedItems = 0;

        // Preload seluruh audio chord
        for (const url of uniqueUrls) {
            if (!this.chordBuffers.has(url)) {
                try {
                    const buffer = await this.loadAudioBuffer(url);
                    this.chordBuffers.set(url, buffer);
                } catch (err) {
                    console.error(`Gagal preload chord audio: ${url}`, err);
                }
            }
            loadedItems++;
            if (onProgress) onProgress(loadedItems, totalItems);
        }

        // Preload backing track jika ada
        if (backingTrackUrl) {
            try {
                this.backingTrackBuffer = await this.loadAudioBuffer(backingTrackUrl);
            } catch (err) {
                console.error(`Gagal preload backing track: ${backingTrackUrl}`, err);
                this.backingTrackBuffer = null;
            }
            loadedItems++;
            if (onProgress) onProgress(loadedItems, totalItems);
        }
    }

    /**
     * Memperbarui data timeline dan parameter sesi (BPM, Transpose).
     */
    setSessionData(timelineData, sessionBpm = null) {
        this.timelineData = timelineData;
        const song = timelineData.song || {};
        this.bpm = Number(sessionBpm || song.bpm || 80);
        this.timeSignatureNumerator = Number(song.time_signature?.numerator || 4);
        this.timeSignatureDenominator = Number(song.time_signature?.denominator || 4);

        const rawOffset = song.backing_track_offset ?? song.backing_track?.offset;
        this.backingTrackOffset = rawOffset !== null && rawOffset !== undefined ? Number(rawOffset) : 0.0;
    }

    /**
     * Menyesuaikan nilai offset backing track secara dinamis.
     * Jika sesi sedang berjalan, anchor penjadwalan dikalibrasi ulang tanpa memutus audio context.
     *
     * @param {number} newOffset
     */
    setBackingTrackOffset(newOffset) {
        this.backingTrackOffset = Number(newOffset);

        if (this.isPlaying && this.timelineData) {
            const spb = 60.0 / this.bpm;
            const countInDuration = this.timeSignatureNumerator * spb;

            // Recalibrate start anchors
            this.mulaiLagu = this.t0 + this.backingTrackOffset;
            this.mulaiCountIn = this.mulaiLagu - countInDuration;

            // Update remaining events in queue
            this.recalibrateScheduledEvents();
        }
    }

    /**
     * Mengatur ulang anchor waktu event yang belum berbunyi ketika offset berubah live.
     */
    recalibrateScheduledEvents() {
        if (!this.timelineData) return;
        const spb = 60.0 / this.bpm;
        const cueMarginSec = this.cueMarginMs / 1000.0;

        for (const chordEvent of this.scheduledChords) {
            chordEvent.waktuBeat = this.mulaiLagu + (chordEvent.start_beat - 1) * spb;
            chordEvent.waktuMulai = chordEvent.waktuBeat - chordEvent.duration - cueMarginSec;
        }

        for (const beatEvent of this.scheduledBeats) {
            beatEvent.time = this.mulaiLagu + (beatEvent.beatNumber - 1) * spb;
        }
    }

    /**
     * Menghasilkan bunyi klik sintetis metronom berkualitas tinggi.
     */
    playMetronomeClick(time, isAccent = false) {
        if (!this.audioContext || this.isMetronomeMuted) return;

        const osc = this.audioContext.createOscillator();
        const envelope = this.audioContext.createGain();

        // Frekuensi lebih tinggi untuk downbeat (beat 1 bar)
        osc.frequency.setValueAtTime(isAccent ? 1200 : 800, time);
        osc.type = 'triangle';

        envelope.gain.setValueAtTime(1.0, time);
        envelope.gain.exponentialRampToValueAtTime(0.001, time + 0.04);

        osc.connect(envelope);
        envelope.connect(this.metronomeGain);

        osc.start(time);
        osc.stop(time + 0.05);

        this.activeSources.push(osc);
    }

    /**
     * Memulai sesi latihan dari awal.
     */
    play() {
        if (!this.timelineData) {
            throw new Error('Data timeline lagu belum dimuat.');
        }

        this.initContext();
        this.stop(); // Hentikan jika sedang berjalan

        this.isPlaying = true;
        const bufferSafety = 0.1; // Buffer awal audio context
        this.t0 = this.audioContext.currentTime + bufferSafety;

        const B = this.timeSignatureNumerator;
        const spb = 60.0 / this.bpm;
        const countInDuration = B * spb;

        // Sesuai ketetapan §0:
        // Beat 0 diselaraskan dengan t0 + backing_track_offset
        // Count-in dimulai pada t0 + backing_track_offset - countInDuration
        // Lagu mulai (beat 1) pada t0 + backing_track_offset
        this.mulaiLagu = this.t0 + this.backingTrackOffset;
        this.mulaiCountIn = this.mulaiLagu - countInDuration;

        // Persiapkan daftar semua placement lengkap
        const allPlacements = [];
        if (this.timelineData.sections) {
            for (const section of this.timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.placements) {
                            for (const p of line.placements) {
                                if (p.position !== null && p.start_beat !== null) {
                                    allPlacements.push(p);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Urutkan placement berdasarkan nomor beat global
        allPlacements.sort((a, b) => a.start_beat - b.start_beat);

        const cueMarginSec = this.cueMarginMs / 1000.0;
        this.scheduledChords = [];

        for (let i = 0; i < allPlacements.length; i++) {
            const placement = allPlacements[i];
            const buffer = placement.chord?.audio_url
                ? this.chordBuffers.get(placement.chord.audio_url)
                : null;
            const duration = buffer ? buffer.duration : 0.6; // Estimasi default durasi klip ucapan

            const waktuBeat = this.mulaiLagu + (placement.start_beat - 1) * spb;
            const waktuMulai = waktuBeat - duration - cueMarginSec;

            const nextPlacement = allPlacements[i + 1];
            const nextBeat = nextPlacement ? nextPlacement.start_beat : placement.start_beat + B;
            const waktuSelesaiAktif = this.mulaiLagu + (nextBeat - 1) * spb;

            this.scheduledChords.push({
                placementId: placement.id,
                chordName: placement.chord?.name || '',
                audioUrl: placement.chord?.audio_url || null,
                start_beat: placement.start_beat,
                buffer: buffer,
                duration: duration,
                waktuMulai: waktuMulai,
                waktuBeat: waktuBeat,
                waktuSelesaiAktif: waktuSelesaiAktif,
                hasPlayedAudio: false,
                currentState: 'normal',
            });
        }

        // Hitung akhir lagu (§7.4): akhir bar yang memuat start_beat tertinggi
        let highestBeat = 1;
        for (const p of allPlacements) {
            if (p.start_beat > highestBeat) {
                highestBeat = p.start_beat;
            }
        }
        const totalBars = Math.ceil(highestBeat / B);
        const endBeat = totalBars * B;
        this.waktuSelesaiLagu = this.mulaiLagu + endBeat * spb;

        // Persiapkan beat metronom dari count-in (-B sampai endBeat)
        this.scheduledBeats = [];
        for (let n = -B + 1; n <= endBeat; n++) {
            const beatTime = this.mulaiLagu + (n - 1) * spb;
            const isCountIn = n <= 0;
            const barNumber = isCountIn ? 0 : Math.floor((n - 1) / B) + 1;
            const beatInBar = isCountIn ? (n + B) : ((n - 1) % B) + 1;
            const isAccent = beatInBar === 1;

            this.scheduledBeats.push({
                beatNumber: n,
                time: beatTime,
                barNumber: barNumber,
                beatInBar: beatInBar,
                isAccent: isAccent,
                hasTriggered: false,
            });
        }

        // Putar backing track jika ada
        const hasBacking = Boolean(this.backingTrackBuffer);
        const originalSongBpm = Number(this.timelineData.song?.bpm || this.bpm);
        const bpmMatches = Math.abs(this.bpm - originalSongBpm) < 0.01;

        if (hasBacking && bpmMatches && !this.isBackingTrackMuted) {
            this.activeBackingSource = this.audioContext.createBufferSource();
            this.activeBackingSource.buffer = this.backingTrackBuffer;
            this.activeBackingSource.connect(this.backingTrackGain);

            // Backing track mulai di t0 (beat 0 selaras dengan t0 + offset)
            this.activeBackingSource.start(this.t0, 0);
        }

        if (this.onPlaybackStateChange) {
            this.onPlaybackStateChange('playing');
        }

        // Jalankan loop lookahead
        this.schedulerIntervalId = setInterval(() => this.runSchedulerLoop(), this.lookaheadMs);
    }

    /**
     * Loop utama pemeriksa waktu Web Audio API.
     */
    runSchedulerLoop() {
        if (!this.isPlaying || !this.audioContext) return;

        const currentTime = this.audioContext.currentTime;
        const horizon = currentTime + this.scheduleAheadTime;

        // 1. Jadwalkan ketukan metronom
        for (const beat of this.scheduledBeats) {
            if (!beat.hasTriggered && beat.time <= horizon) {
                if (beat.time >= this.audioContext.currentTime - 0.05) {
                    this.playMetronomeClick(beat.time, beat.isAccent);
                }
                beat.hasTriggered = true;

                if (this.onBeatTick) {
                    const delay = Math.max(0, (beat.time - currentTime) * 1000);
                    setTimeout(() => {
                        if (this.isPlaying && this.onBeatTick) {
                            this.onBeatTick(beat.beatNumber, beat.barNumber, beat.isAccent);
                        }
                    }, delay);
                }
            }
        }

        // 2. Jadwalkan audio klip ucapan chord
        for (const chord of this.scheduledChords) {
            if (!chord.hasPlayedAudio && chord.waktuMulai <= horizon) {
                if (chord.waktuMulai >= this.t0 && chord.buffer && !this.isChordVoiceMuted) {
                    const source = this.audioContext.createBufferSource();
                    source.buffer = chord.buffer;
                    source.connect(this.chordVoiceGain);
                    source.start(Math.max(chord.waktuMulai, this.audioContext.currentTime));
                    this.activeSources.push(source);
                }
                chord.hasPlayedAudio = true;
            }

            // 3. Update status visual highlight chord
            // Status Aktif: dari waktuBeat sampai waktuSelesaiAktif
            if (currentTime >= chord.waktuBeat && currentTime < chord.waktuSelesaiAktif) {
                if (chord.currentState !== 'active') {
                    chord.currentState = 'active';
                    if (this.onChordStateChange) {
                        this.onChordStateChange(chord.placementId, 'active');
                    }
                }
            } else if (currentTime >= chord.waktuSelesaiAktif) {
                if (chord.currentState !== 'played') {
                    chord.currentState = 'played';
                    if (this.onChordStateChange) {
                        this.onChordStateChange(chord.placementId, 'played');
                    }
                }
            }
        }

        // 4. Periksa apakah lagu sudah selesai
        if (currentTime >= this.waktuSelesaiLagu) {
            this.stop();
            if (this.onSongEnded) {
                this.onSongEnded();
            }
        }
    }

    /**
     * Menghentikan seluruh audio playback dan mereset status.
     */
    stop() {
        if (this.schedulerIntervalId) {
            clearInterval(this.schedulerIntervalId);
            this.schedulerIntervalId = null;
        }

        if (this.activeBackingSource) {
            try {
                this.activeBackingSource.stop();
                this.activeBackingSource.disconnect();
            } catch (e) {
                // Ignore if already stopped
            }
            this.activeBackingSource = null;
        }

        for (const src of this.activeSources) {
            try {
                src.stop();
                src.disconnect();
            } catch (e) {
                // Ignore
            }
        }
        this.activeSources = [];

        this.isPlaying = false;

        // Reset status chord visual ke normal
        if (this.scheduledChords) {
            for (const c of this.scheduledChords) {
                if (c.currentState !== 'normal' && this.onChordStateChange) {
                    this.onChordStateChange(c.placementId, 'normal');
                }
                c.currentState = 'normal';
                c.hasPlayedAudio = false;
            }
        }

        if (this.onPlaybackStateChange) {
            this.onPlaybackStateChange('stopped');
        }
    }
}


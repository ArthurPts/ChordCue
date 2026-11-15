/**
 * ChordCue Web Audio Engine
 *
 * Modul Vanilla JS mandiri untuk penjadwalan presisi audio lirik, metronom count-in,
 * aba-aba ucapan chord (TTS), dan sinkronisasi backing track menggunakan Web Audio API.
 *
 * Mematuhi CHORDCUE_RULES.md §8 dan SPESIFIKASI_SISTEM_ChordCue.md §7.4 & §10.1.
 */

/**
 * Mengukur detik ketika suara ucapan benar-benar selesai di dalam AudioBuffer
 * menggunakan pemindaian RMS per 10 ms dari akhir buffer untuk membuang trailing silence MP3.
 *
 * @param {AudioBuffer|{getChannelData:Function, sampleRate:number, duration:number}} buffer
 * @param {number} thresholdDb
 * @returns {number}
 */
export function measureSpeechEnd(buffer, thresholdDb = -40.0) {
    if (!buffer || typeof buffer.getChannelData !== 'function' || !buffer.sampleRate) {
        return Number(buffer?.duration || 0);
    }

    const data = buffer.getChannelData(0);
    if (!data || data.length === 0) {
        return Number(buffer.duration || 0);
    }

    const thresholdAmp = Math.pow(10, Number(thresholdDb) / 20);
    const win = Math.max(1, Math.round(buffer.sampleRate * 0.01));

    for (let end = data.length; end > 0; end -= win) {
        const start = Math.max(0, end - win);
        let sum = 0;
        for (let i = start; i < end; i++) {
            const sample = data[i];
            sum += sample * sample;
        }
        const rms = Math.sqrt(sum / (end - start));
        if (rms > thresholdAmp) {
            return end / buffer.sampleRate;
        }
    }

    return Number(buffer.duration || 0);
}

/**
 * Menghitung seluruh anchor waktu sinkronisasi antara countdown offset, count-in metronom 1 bar,
 * dan Beat 1 lagu agar kedua hitungan mundur selalu selesai bersamaan di Beat 1.
 *
 * @param {Object} params
 * @returns {Object}
 */
export function computeAnchors({
    t0 = 0,
    bpm = 80,
    beatsPerBar = 4,
    offset = 0,
    hasBacking = false,
    isBackingTrackMuted = false,
    songBpm = null,
} = {}) {
    const B = Number(beatsPerBar || 4);
    const safeBpm = Number(bpm || 80);
    const spb = 60.0 / safeBpm;
    const barDuration = B * spb;
    const countInDuration = barDuration;

    const numericOffset = Number(offset || 0);
    const originalSongBpm = Number(songBpm || safeBpm);
    const bpmMatches = Math.abs(safeBpm - originalSongBpm) < 0.01;
    const adaBacking = Boolean(hasBacking) && bpmMatches && !isBackingTrackMuted;

    let leadTime = countInDuration;
    let mulaiLagu = t0 + countInDuration;
    let mulaiCountIn = t0;
    let offsetStartTime = t0 + countInDuration;
    let backingStartAudioTime = t0 + countInDuration;
    let backingBufferOffset = 0;

    if (numericOffset >= 0) {
        // Offset >= 0 selalu menggerakkan countdown menuju Beat 1 (dengan atau tanpa backing track).
        leadTime = Math.max(numericOffset, countInDuration);
        mulaiLagu = t0 + leadTime;
        mulaiCountIn = mulaiLagu - countInDuration;
        offsetStartTime = mulaiLagu - numericOffset;
        backingStartAudioTime = adaBacking ? offsetStartTime : mulaiLagu;
        backingBufferOffset = 0;
    } else {
        // Offset negatif: awalan audio backing track dipotong mulai dari Beat 1.
        leadTime = countInDuration;
        mulaiLagu = t0 + leadTime;
        mulaiCountIn = t0;
        offsetStartTime = mulaiLagu;
        backingStartAudioTime = mulaiLagu;
        backingBufferOffset = adaBacking ? Math.abs(numericOffset) : 0;
    }

    return {
        spb,
        barDuration,
        countInDuration,
        leadTime,
        mulaiLagu,
        mulaiCountIn,
        offsetStartTime,
        backingStartAudioTime,
        backingBufferOffset,
        adaBacking,
    };
}

/**
 * Menghitung waktu mulai dan akhir ucapan aba-aba chord (Tstart = Tbeat - Dspeech - Mcue)
 * dengan batas bawah minStartTime = max(t0, prevBeatTime) agar chord rapat tidak
 * bersuara mendahului ketukan chord sebelumnya.
 *
 * @param {Object} params
 * @returns {Object}
 */
export function computeChordScheduleTime({
    waktuBeat,
    speechDuration,
    cueMarginSec = 1.0,
    minStartTime = 0,
}) {
    const safeMargin = Math.max(0, Number(cueMarginSec || 0));
    const safeSpeech = Math.max(0, Number(speechDuration || 0));
    const idealStart = waktuBeat - safeSpeech - safeMargin;
    const startTime = Math.max(idealStart, minStartTime);
    const speechEndTime = startTime + safeSpeech;

    return {
        startTime,
        speechEndTime,
        actualMarginSec: waktuBeat - speechEndTime,
    };
}

export class AudioEngine {
    constructor(options = {}) {
        this.cueMarginMs = Number(options.cueMarginMs ?? 1000);
        this.speechSilenceThresholdDb = Number(options.speechSilenceThresholdDb ?? -40.0);
        this.chordCueTrimMs = Number(options.chordCueTrimMs ?? 0);
        this.onChordStateChange = options.onChordStateChange || null;
        this.onBeatTick = options.onBeatTick || null;
        this.onIntroTick = options.onIntroTick || null;
        this.onCountInTick = options.onCountInTick || null;
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
        this.rawChordBuffers = new Map(); // audio_url -> ArrayBuffer
        this.chordBuffers = new Map(); // audio_url -> AudioBuffer
        this.speechEndSec = new Map(); // audio_url -> detik akhir ucapan aktif
        this.rawBackingTrackBuffer = null; // ArrayBuffer
        this.backingTrackBuffer = null; // AudioBuffer
        this.loadedBackingTrackUrl = null;

        // Data Sesi Aktif
        this.timelineData = null;
        this.bpm = 80;
        this.timeSignatureNumerator = 4;
        this.timeSignatureDenominator = 4;
        this.backingTrackOffset = 0.0; // Detik

        // State Penjadwalan
        this.isPlaying = false;
        this.isContinuousMetronome = false;
        this.t0 = 0; // Waktu audioContext saat sesi dimulai
        this.mulaiLagu = 0;
        this.mulaiCountIn = 0;
        this.offsetStartTime = 0;
        this.leadTime = 0;
        this.waktuSelesaiLagu = 0;
        this.endBeat = 0;

        this.lookaheadMs = 25.0; // Interval pemeriksaan timer (ms)
        this.scheduleAheadTime = 0.5; // Jendela lookahead scheduling (detik)
        this.schedulerIntervalId = null;
        this.visualRafId = null;
        this.lastVisualBeatNumber = null;
        this.lastCountdownTenths = null;
        this.currentCountBeat = 0;

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
     * Mengunduh data biner audio dari URL tanpa membuat atau mengaktifkan AudioContext.
     * Mencegah peringatan kebijakan autoplay browser sebelum ada interaksi klik Putar.
     *
     * @param {string} url
     * @returns {Promise<ArrayBuffer>}
     */
    async fetchAudioArrayBuffer(url) {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Gagal mengunduh audio dari ${url} (status: ${response.status})`);
        }
        const arrayBuffer = await response.arrayBuffer();
        if (!arrayBuffer || arrayBuffer.byteLength === 0) {
            throw new Error(`Berkas audio kosong dari ${url}`);
        }
        return arrayBuffer;
    }

    /**
     * Mendecode seluruh ArrayBuffer hasil preload menjadi Web Audio AudioBuffer.
     * Wajib dipanggil setelah AudioContext diinisialisasi oleh user gesture (klik Putar).
     */
    async ensureBuffersDecoded() {
        this.initContext();

        for (const [url, rawBuffer] of this.rawChordBuffers.entries()) {
            if (!this.chordBuffers.has(url)) {
                const decoded = await this.audioContext.decodeAudioData(rawBuffer.slice(0));
                this.chordBuffers.set(url, decoded);
            }
            if (!this.speechEndSec.has(url)) {
                const buf = this.chordBuffers.get(url);
                this.speechEndSec.set(url, measureSpeechEnd(buf, this.speechSilenceThresholdDb));
            }
        }

        if (this.rawBackingTrackBuffer && !this.backingTrackBuffer) {
            this.backingTrackBuffer = await this.audioContext.decodeAudioData(
                this.rawBackingTrackBuffer.slice(0)
            );
        }
    }

    /**
     * Melakukan preload unduhan audio untuk semua suara chord dan backing track
     * tanpa menyentuh AudioContext sebelum tombol Putar diklik.
     *
     * @param {Object} timelineData
     * @param {Function} onProgress (loaded, total)
     * @returns {Promise<Object>}
     */
    async preloadTimelineAssets(timelineData, onProgress = null) {
        this.timelineData = timelineData;

        const uniqueUrls = new Set();
        let hasMissingAudioUrl = false;
        let completePlacementCount = 0;

        if (timelineData.sections) {
            for (const section of timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.placements) {
                            for (const placement of line.placements) {
                                if (placement.position !== null && placement.start_beat !== null) {
                                    completePlacementCount++;
                                    if (placement.chord && placement.chord.audio_url) {
                                        uniqueUrls.add(placement.chord.audio_url);
                                    } else {
                                        hasMissingAudioUrl = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        const backingTrackUrl = timelineData.song?.backing_track?.url;
        const totalItems = uniqueUrls.size + (backingTrackUrl ? 1 : 0);
        let loadedItems = 0;
        const failedUrls = [];

        // Preload seluruh file audio chord ke memori ArrayBuffer
        for (const url of uniqueUrls) {
            if (!this.chordBuffers.has(url) && !this.rawChordBuffers.has(url)) {
                try {
                    const rawBuffer = await this.fetchAudioArrayBuffer(url);
                    this.rawChordBuffers.set(url, rawBuffer);
                } catch (err) {
                    console.error(`Gagal preload chord audio: ${url}`, err);
                    failedUrls.push(url);
                }
            }
            loadedItems++;
            if (onProgress) onProgress(loadedItems, totalItems);
        }

        // Preload backing track jika ada
        if (backingTrackUrl) {
            const needsBackingFetch =
                this.loadedBackingTrackUrl !== backingTrackUrl ||
                (!this.rawBackingTrackBuffer && !this.backingTrackBuffer);

            if (needsBackingFetch) {
                try {
                    this.rawBackingTrackBuffer = await this.fetchAudioArrayBuffer(backingTrackUrl);
                    this.backingTrackBuffer = null;
                    this.loadedBackingTrackUrl = backingTrackUrl;
                } catch (err) {
                    console.error(`Gagal preload backing track: ${backingTrackUrl}`, err);
                    this.rawBackingTrackBuffer = null;
                    this.backingTrackBuffer = null;
                    this.loadedBackingTrackUrl = null;
                    failedUrls.push(backingTrackUrl);
                }
            }
            loadedItems++;
            if (onProgress) onProgress(loadedItems, totalItems);
        } else {
            this.rawBackingTrackBuffer = null;
            this.backingTrackBuffer = null;
            this.loadedBackingTrackUrl = null;
        }

        const chordsValid =
            completePlacementCount === 0 ||
            (Boolean(timelineData.audio_ready) && !hasMissingAudioUrl && failedUrls.length === 0);
        const backingValid =
            !backingTrackUrl || Boolean(this.backingTrackBuffer || this.rawBackingTrackBuffer);
        const success = chordsValid && backingValid && failedUrls.length === 0;

        return {
            loaded: loadedItems,
            total: totalItems,
            failedUrls,
            hasMissingAudioUrl,
            completePlacementCount,
            success,
        };
    }

    /**
     * Memeriksa apakah seluruh syarat pemutaran terpenuhi pada AudioEngine.
     * - Jika lagu memiliki placement chord lengkap: wajib seluruh audio chord & backing track siap.
     * - Jika lagu belum memiliki placement chord: metronom (dan backing track jika ada) tetap dapat diputar.
     *
     * @returns {boolean}
     */
    canPlay() {
        if (!this.timelineData) {
            return false;
        }

        const completePlacements = [];
        if (this.timelineData.sections) {
            for (const section of this.timelineData.sections) {
                if (section.lines) {
                    for (const line of section.lines) {
                        if (line.placements) {
                            for (const p of line.placements) {
                                if (p.position !== null && p.start_beat !== null) {
                                    completePlacements.push(p);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Jika terdapat chord lengkap, pastikan seluruh audio chord telah siap & ter-preload di memori (§5.6)
        if (completePlacements.length > 0) {
            if (!this.timelineData.audio_ready) {
                return false;
            }

            for (const p of completePlacements) {
                const url = p.chord?.audio_url;
                if (!url || (!this.chordBuffers.has(url) && !this.rawChordBuffers.has(url))) {
                    return false;
                }
            }
        }

        // Jika backing track dikonfigurasi pada lagu, pastikan datanya berhasil dimuat
        const backingTrackUrl = this.timelineData.song?.backing_track?.url;
        if (backingTrackUrl && !this.backingTrackBuffer && !this.rawBackingTrackBuffer) {
            return false;
        }

        return true;
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
     * Mengambil durasi efektif akhir ucapan chord (D_speech) dengan trim opsional.
     *
     * @param {string|null} url
     * @param {AudioBuffer|null} buffer
     * @returns {number}
     */
    getEffectiveCueDuration(url, buffer) {
        const detected = url && this.speechEndSec.has(url)
            ? this.speechEndSec.get(url)
            : (buffer ? measureSpeechEnd(buffer, this.speechSilenceThresholdDb) : 0.6);

        return Math.max(0.05, detected - (this.chordCueTrimMs / 1000.0));
    }

    /**
     * Menghitung anchor penjadwalan aktif saat ini menggunakan computeAnchors().
     *
     * @returns {Object}
     */
    getCurrentAnchors() {
        return computeAnchors({
            t0: this.t0,
            bpm: this.bpm,
            beatsPerBar: this.timeSignatureNumerator,
            offset: this.backingTrackOffset,
            hasBacking: Boolean(this.backingTrackBuffer),
            isBackingTrackMuted: this.isBackingTrackMuted,
            songBpm: Number(this.timelineData?.song?.bpm || this.bpm),
        });
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
            const anchors = this.getCurrentAnchors();
            this.mulaiLagu = anchors.mulaiLagu;
            this.mulaiCountIn = anchors.mulaiCountIn;
            this.offsetStartTime = anchors.offsetStartTime;
            this.leadTime = anchors.leadTime;

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
        const cueMarginSec = Math.max(0, Number(this.cueMarginMs ?? 1000) / 1000.0);
        let prevBeatTime = this.t0;

        for (let i = 0; i < this.scheduledChords.length; i++) {
            const chordEvent = this.scheduledChords[i];
            const waktuBeat = this.mulaiLagu + (chordEvent.start_beat - 1) * spb;
            const minStartTime = i === 0 ? this.t0 : Math.max(this.t0, prevBeatTime);
            const { startTime } = computeChordScheduleTime({
                waktuBeat,
                speechDuration: chordEvent.duration,
                cueMarginSec,
                minStartTime,
            });

            chordEvent.waktuBeat = waktuBeat;
            chordEvent.waktuMulai = startTime;

            const nextChord = this.scheduledChords[i + 1];
            const nextBeat = nextChord ? nextChord.start_beat : (this.endBeat || chordEvent.start_beat) + 1;
            chordEvent.waktuSelesaiAktif = this.mulaiLagu + (nextBeat - 1) * spb;
            prevBeatTime = waktuBeat;
        }

        for (const beatEvent of this.scheduledBeats) {
            beatEvent.time = this.mulaiLagu + (beatEvent.beatNumber - 1) * spb;
        }
    }

    /**
     * Menghasilkan bunyi klik sintetis metronom berkualitas tinggi (synthesized Web Audio).
     */
    playMetronomeClick(time, isAccent = false) {
        if (!this.audioContext || !this.isPlaying) return;

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
     * Menolak memulai pemutaran (termasuk metronom) apabila syarat pemutaran belum terpenuhi.
     *
     * @returns {Promise<boolean>}
     */
    async play() {
        if (!this.timelineData) {
            return false;
        }

        if (!this.canPlay()) {
            this.stop();
            return false;
        }

        // Inisialisasi dan resume AudioContext secara sinkron dari interaksi klik Putar
        this.initContext();
        this.stop(); // Hentikan jika sedang berjalan

        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        try {
            await this.ensureBuffersDecoded();
        } catch (err) {
            console.error('Gagal mendecode aset audio:', err);
            this.stop();
            return false;
        }

        this.isPlaying = true;
        this.lastVisualBeatNumber = null;
        this.lastCountdownTenths = null;
        this.currentCountBeat = 0;
        const bufferSafety = 0.1; // Buffer awal audio context
        this.t0 = this.audioContext.currentTime + bufferSafety;

        const B = this.timeSignatureNumerator;
        const anchors = this.getCurrentAnchors();
        const {
            spb,
            leadTime,
            mulaiLagu,
            mulaiCountIn,
            offsetStartTime,
            backingStartAudioTime,
            backingBufferOffset,
            adaBacking,
        } = anchors;

        this.leadTime = leadTime;
        this.mulaiLagu = mulaiLagu;
        this.mulaiCountIn = mulaiCountIn;
        this.offsetStartTime = offsetStartTime;

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

        // Hitung akhir lagu (§7.4):
        // - Jika ada placement chord: akhir bar yang memuat start_beat tertinggi
        // - Jika tanpa chord & ada backing track: panjang durasi backing track
        // - Jika tanpa chord & tanpa backing track: mode metronom kontinyu hingga Stop ditekan
        let endBeat = B;
        if (allPlacements.length > 0) {
            this.isContinuousMetronome = false;
            let highestBeat = 1;
            for (const p of allPlacements) {
                if (p.start_beat > highestBeat) {
                    highestBeat = p.start_beat;
                }
            }
            const totalBars = Math.ceil(highestBeat / B);
            endBeat = totalBars * B;
            this.waktuSelesaiLagu = this.mulaiLagu + endBeat * spb;
        } else if (adaBacking && this.backingTrackBuffer) {
            this.isContinuousMetronome = false;
            const remainingBackingSeconds = Math.max(spb, this.backingTrackBuffer.duration - this.backingTrackOffset);
            const totalBars = Math.max(1, Math.ceil((remainingBackingSeconds / spb) / B));
            endBeat = totalBars * B;
            this.waktuSelesaiLagu = this.mulaiLagu + endBeat * spb;
        } else {
            this.isContinuousMetronome = true;
            endBeat = B * 2;
            this.waktuSelesaiLagu = Infinity;
        }

        // 1. Persiapkan & jadwalkan beat metronom dari count-in (-B+1 sampai endBeat)
        this.scheduledBeats = [];
        for (let n = -B + 1; n <= endBeat; n++) {
            const beatTime = this.mulaiLagu + (n - 1) * spb;
            const isCountIn = n <= 0;
            const barNumber = isCountIn ? 0 : Math.floor((n - 1) / B) + 1;
            const beatInBar = isCountIn ? (n + B) : ((n - 1) % B) + 1;
            const isAccent = beatInBar === 1;

            const shouldScheduleUpfront = !this.isContinuousMetronome && beatTime >= this.t0 - 0.001;
            if (shouldScheduleUpfront) {
                this.playMetronomeClick(Math.max(beatTime, this.t0), isAccent);
            }

            this.scheduledBeats.push({
                beatNumber: n,
                time: beatTime,
                barNumber: barNumber,
                beatInBar: beatInBar,
                isAccent: isAccent,
                hasTriggered: shouldScheduleUpfront,
            });
        }

        // 2. Putar backing track jika ada
        if (adaBacking) {
            this.activeBackingSource = this.audioContext.createBufferSource();
            this.activeBackingSource.buffer = this.backingTrackBuffer;
            this.activeBackingSource.connect(this.backingTrackGain);

            // Backing track mulai tepat pada backingStartAudioTime (offset selesai bersamaan dengan count-in)
            this.activeBackingSource.start(backingStartAudioTime, backingBufferOffset);
        }

        // 3. Jadwalkan seluruh suara chord TTS: Tstart = Tbeat - D_speech - Mcue
        this.endBeat = endBeat;
        this.scheduledChords = [];
        const cueMarginSec = Math.max(0, Number(this.cueMarginMs ?? 1000) / 1000.0);
        let prevBeatTime = this.t0;

        for (let i = 0; i < allPlacements.length; i++) {
            const placement = allPlacements[i];
            const url = placement.chord?.audio_url || null;
            const buffer = url ? this.chordBuffers.get(url) : null;
            const duration = this.getEffectiveCueDuration(url, buffer);

            const waktuBeat = this.mulaiLagu + (placement.start_beat - 1) * spb;
            const minStartTime = i === 0 ? this.t0 : Math.max(this.t0, prevBeatTime);
            const { startTime: scheduleTime } = computeChordScheduleTime({
                waktuBeat,
                speechDuration: duration,
                cueMarginSec,
                minStartTime,
            });

            const nextPlacement = allPlacements[i + 1];
            const nextBeat = nextPlacement ? nextPlacement.start_beat : endBeat + 1;
            const waktuSelesaiAktif = this.mulaiLagu + (nextBeat - 1) * spb;

            let hasScheduledAudio = false;
            if (buffer && scheduleTime < this.waktuSelesaiLagu) {
                const source = this.audioContext.createBufferSource();
                source.buffer = buffer;
                source.connect(this.chordVoiceGain);
                source.start(scheduleTime);
                this.activeSources.push(source);
                hasScheduledAudio = true;
            }

            this.scheduledChords.push({
                placementId: placement.id,
                chordName: placement.chord?.name || '',
                audioUrl: url,
                start_beat: placement.start_beat,
                buffer: buffer,
                rawDuration: buffer ? buffer.duration : null,
                duration: duration,
                waktuMulai: scheduleTime,
                waktuBeat: waktuBeat,
                waktuSelesaiAktif: waktuSelesaiAktif,
                hasPlayedAudio: hasScheduledAudio,
                currentState: 'normal',
            });

            prevBeatTime = waktuBeat;
        }

        if (this.onPlaybackStateChange) {
            this.onPlaybackStateChange('playing');
        }

        // Jalankan loop lookahead segera hanya jika mode metronom kontinyu aktif
        if (this.isContinuousMetronome) {
            this.runSchedulerLoop();
            this.schedulerIntervalId = setInterval(() => this.runSchedulerLoop(), this.lookaheadMs);
        }

        // Jalankan loop visual 60fps tersinkronisasi langsung dengan AudioContext.currentTime
        this.visualRafId = requestAnimationFrame(() => this.runVisualLoop());

        return true;
    }

    /**
     * Loop lookahead khusus untuk memperpanjang ketukan pada mode metronom kontinyu.
     */
    runSchedulerLoop() {
        if (!this.isPlaying || !this.audioContext || !this.isContinuousMetronome) return;

        const currentTime = this.audioContext.currentTime;
        const horizon = currentTime + this.scheduleAheadTime;
        const spb = 60.0 / this.bpm;
        const B = this.timeSignatureNumerator;

        if (this.scheduledBeats.length > 0) {
            let lastBeat = this.scheduledBeats[this.scheduledBeats.length - 1];

            while (lastBeat.time <= horizon + 1.0) {
                const nextNum = lastBeat.beatNumber + 1;
                const beatTime = this.mulaiLagu + (nextNum - 1) * spb;
                const barNumber = Math.floor((nextNum - 1) / B) + 1;
                const beatInBar = ((nextNum - 1) % B) + 1;
                const nextBeatObj = {
                    beatNumber: nextNum,
                    time: beatTime,
                    barNumber: barNumber,
                    beatInBar: beatInBar,
                    isAccent: beatInBar === 1,
                    hasTriggered: false,
                };
                this.scheduledBeats.push(nextBeatObj);
                lastBeat = nextBeatObj;
            }
        }

        for (const beat of this.scheduledBeats) {
            if (!beat.hasTriggered && beat.time <= horizon) {
                if (beat.time >= currentTime - 0.05) {
                    this.playMetronomeClick(Math.max(beat.time, currentTime), beat.isAccent);
                }
                beat.hasTriggered = true;
            }
        }

        // Bersihkan ketukan yang sudah terlewati agar memori tetap stabil
        while (this.scheduledBeats.length > 32 && this.scheduledBeats[0].hasTriggered && this.scheduledBeats[0].time < currentTime - spb) {
            this.scheduledBeats.shift();
        }
    }

    /**
     * Loop pembaruan antarmuka visual (counter birama/ketukan, highlight chord, akhir lagu)
     * yang membaca AudioContext.currentTime langsung agar bebas timer drift.
     */
    runVisualLoop() {
        if (!this.isPlaying || !this.audioContext) return;

        const currentTime = this.audioContext.currentTime;

        // 1. Akhiri sesi tepat di akhir birama terakhir lagu (untuk lagu berdurasi terbatas)
        if (!this.isContinuousMetronome && currentTime >= this.waktuSelesaiLagu) {
            this.stop();
            if (this.onSongEnded) {
                this.onSongEnded();
            }
            return;
        }

        // 2. Perbarui indikator beat/birama aktif berdasarkan AudioContext.currentTime
        const effectiveNow = Math.max(currentTime, this.t0);
        const tenthTick = Math.round(Math.max(0, this.mulaiLagu - effectiveNow) * 10);

        if (currentTime < this.mulaiCountIn) {
            if (this.onIntroTick && tenthTick !== this.lastCountdownTenths) {
                this.lastCountdownTenths = tenthTick;
                const remainingToCountIn = Math.max(0, this.mulaiCountIn - effectiveNow);
                const remainingToSong = Math.max(0, this.mulaiLagu - effectiveNow);
                this.onIntroTick(remainingToCountIn, remainingToSong);
            }
        } else if (this.onBeatTick && this.scheduledBeats.length > 0) {
            let activeBeat = null;
            for (let i = 0; i < this.scheduledBeats.length; i++) {
                const beat = this.scheduledBeats[i];
                if (currentTime >= beat.time) {
                    activeBeat = beat;
                } else {
                    break;
                }
            }

            if (activeBeat && activeBeat.beatNumber !== this.lastVisualBeatNumber) {
                this.lastVisualBeatNumber = activeBeat.beatNumber;
                this.currentCountBeat = activeBeat.beatNumber <= 0
                    ? activeBeat.beatNumber + this.timeSignatureNumerator
                    : activeBeat.beatInBar;
                this.onBeatTick(activeBeat.beatNumber, activeBeat.barNumber, activeBeat.isAccent);
            }

            if (
                currentTime < this.mulaiLagu &&
                this.backingTrackOffset > 0 &&
                this.onCountInTick &&
                tenthTick !== this.lastCountdownTenths
            ) {
                this.lastCountdownTenths = tenthTick;
                const remainingOffset = Math.max(0, this.mulaiLagu - effectiveNow);
                const offsetStarted = effectiveNow >= this.offsetStartTime;
                const timeUntilOffsetStarts = Math.max(0, this.offsetStartTime - effectiveNow);
                this.onCountInTick(
                    this.currentCountBeat || 1,
                    this.timeSignatureNumerator,
                    remainingOffset,
                    offsetStarted,
                    timeUntilOffsetStarts
                );
            }
        }

        // 3. Perbarui sorotan visual chord aktif dan sudah dimainkan
        for (const chord of this.scheduledChords) {
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

        this.visualRafId = requestAnimationFrame(() => this.runVisualLoop());
    }

    /**
     * Menghentikan seluruh audio playback dan mereset status.
     */
    stop() {
        if (this.schedulerIntervalId) {
            clearInterval(this.schedulerIntervalId);
            this.schedulerIntervalId = null;
        }

        if (this.visualRafId) {
            cancelAnimationFrame(this.visualRafId);
            this.visualRafId = null;
        }

        this.lastVisualBeatNumber = null;
        this.lastCountdownTenths = null;
        this.currentCountBeat = 0;

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
        this.isContinuousMetronome = false;

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


/**
 * ChordCue Player Auto-Scroll Module (Opsi C — Interpolasi Linear Per-Segmen Beat -> DOM Y)
 *
 * Modul Vanilla JS mandiri untuk menggulir halaman Player secara otomatis mengikuti BPM
 * sejak Beat 1 (`mulaiLagu`) hingga chord terakhir selesai dibacakan oleh TTS.
 * Memetakan `start_beat` tiap baris visual chord (`#placement-{id}`) ke koordinat Y DOM
 * secara monotonik agar sorotan chord aktif selalu sejajar di area baca walau tinggi
 * section, wrapping baris, kepadatan chord, maupun urutan data beat bervariasi.
 *
 * Mematuhi CHORDCUE_RULES.md §8 (terpisah dari Alpine.js) dan AUTOSCROLL_PLAN.md.
 */

export const AUTO_SCROLL_STORAGE_KEY = 'chordcue.player.autoScroll';
export const DEFAULT_PX_PER_BEAT = 12;
const SCROLL_EPSILON_PX = 0.5;
const MANUAL_SCROLL_TOLERANCE_PX = 12;
const ROW_MERGE_TOLERANCE_PX = 8;
const RESIZE_DEBOUNCE_MS = 150;
const MANUAL_SCROLL_KEYS = new Set([
    'ArrowUp',
    'ArrowDown',
    'PageUp',
    'PageDown',
    'Home',
    'End',
    ' ',
]);

/**
 * Membatasi nilai numerik ke dalam rentang [min, max].
 *
 * @param {number} value
 * @param {number} min
 * @param {number} max
 * @returns {number}
 */
function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

/**
 * Menentukan status awal toggle Gulir Otomatis:
 * - Jika ada preferensi tersimpan di localStorage ('true' / 'false'), pakai itu.
 * - Jika belum ada: default ON, namun otomatis OFF bila `prefers-reduced-motion: reduce`.
 *
 * @param {Object} params
 * @returns {boolean}
 */
export function resolveInitialAutoScroll({ storedValue = null, prefersReducedMotion = false } = {}) {
    if (storedValue === 'true') {
        return true;
    }
    if (storedValue === 'false') {
        return false;
    }
    return !Boolean(prefersReducedMotion);
}

/**
 * Membaca preferensi awal Gulir Otomatis dari browser (localStorage + media query).
 *
 * @returns {boolean}
 */
export function readStoredAutoScroll() {
    let storedValue = null;
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            storedValue = window.localStorage.getItem(AUTO_SCROLL_STORAGE_KEY);
        }
    } catch (e) {
        storedValue = null;
    }

    let prefersReducedMotion = false;
    try {
        if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
            prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
    } catch (e) {
        prefersReducedMotion = false;
    }

    return resolveInitialAutoScroll({ storedValue, prefersReducedMotion });
}

/**
 * Menyimpan preferensi Gulir Otomatis ke localStorage.
 *
 * @param {boolean} enabled
 */
export function writeStoredAutoScroll(enabled) {
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem(AUTO_SCROLL_STORAGE_KEY, enabled ? 'true' : 'false');
        }
    } catch (e) {
        // Abaikan bila localStorage diblokir mode privasi browser
    }
}

/**
 * Menyusun daftar jangkar monotonik `[{ beat, y }, ...]` dari posisi DOM placement chord:
 * - Menggunakan filter suffix-minimum agar satu chord yang beat-nya tidak berurutan terhadap
 *   posisi baris tidak merusak seluruh jangkar berikutnya.
 * - Menggabungkan chord pada baris horizontal yang sama (`±8px`) agar gulir halus tanpa stop-and-go.
 *
 * @param {Object} params
 * @returns {Array<{beat: number, y: number}>}
 */
export function buildBeatScrollAnchors({
    placementPositions = [],
    yStart = 0,
    yEnd = 0,
    readingAnchorY = 0,
    endScrollBeat = 1,
} = {}) {
    const safeEndBeat = Math.max(1.01, Number(endScrollBeat || 1.01));
    const anchors = [{ beat: 1, y: yStart }];

    if (yEnd <= yStart) {
        return anchors;
    }

    // Urutkan placement berdasarkan start_beat naik dan hitung rawY ter-clamp
    const sorted = [...placementPositions]
        .filter((item) => item && Number.isFinite(Number(item.beat)) && Number.isFinite(Number(item.docTop)))
        .sort((a, b) => Number(a.beat) - Number(b.beat))
        .map((item) => ({
            beat: Number(item.beat),
            rawY: clamp(Number(item.docTop) - Number(readingAnchorY), yStart, yEnd),
        }));

    // Suffix-minimum mencegah satu placement out-of-order melonjak ke bawah dan menelan sisa jangkar
    const smoothedY = new Array(sorted.length);
    for (let i = sorted.length - 1; i >= 0; i--) {
        smoothedY[i] = i === sorted.length - 1
            ? sorted[i].rawY
            : Math.min(sorted[i].rawY, smoothedY[i + 1]);
    }

    let lastY = yStart;

    for (let i = 0; i < sorted.length; i++) {
        const beat = sorted[i].beat;
        if (beat <= 1 || beat >= safeEndBeat) {
            continue;
        }

        const candidateY = smoothedY[i];

        // Hanya tambahkan jangkar baru bila posisi Y benar-benar turun melewati toleransi 1 baris,
        // agar chord pada baris yang sama tidak menciptakan segmen datar (stop-and-go).
        if (candidateY > lastY + ROW_MERGE_TOLERANCE_PX) {
            const prevAnchor = anchors[anchors.length - 1];
            if (beat > prevAnchor.beat) {
                anchors.push({ beat, y: candidateY });
                lastY = candidateY;
            }
        }
    }

    // Pastikan jangkar akhir selalu berakhir di yEnd tepat pada endScrollBeat (atau sebelumnya jika sudah mencapai yEnd)
    const lastAnchor = anchors[anchors.length - 1];
    if (lastAnchor.y < yEnd - 0.5) {
        anchors.push({ beat: safeEndBeat, y: yEnd });
    }

    return anchors;
}

/**
 * Menghitung metrik & tabel jangkar auto-scroll (Opsi C) dengan fallback otomatis ke Opsi B/A:
 * - Lagu dengan chord + elemen DOM `#placement-{id}`:
 *   - Garis baca (`readingAnchorY`) diselaraskan dengan posisi awal chord pertama agar tidak macet di awal lagu.
 *   - Batas akhir gulir (`yEnd`) dibatasi pada posisi chord beat terakhir (bukan lirik tanpa beat di bawahnya),
 *     sehingga lagu dengan chord parsial tidak melompat liar di akhir.
 *   - Dimulai di Beat 1 (`yStart`) dan berakhir tepat saat chord terakhir selesai dibacakan (`lastChordSpeechEndTime`).
 * - Lagu tanpa chord + backing track: 2 jangkar `[(1, yStart), (1 + endBeat, yEnd)]` (linear penuh).
 * - Metronom kontinyu tanpa chord/backing track: kecepatan konstan `defaultPxPerBeat`.
 *
 * @param {Object} params
 * @returns {Object}
 */
export function computeAutoScrollMetrics({
    sheetTop = 0,
    sheetBottom = 0,
    stickyBottom = 0,
    viewportHeight = 0,
    scrollHeight = 0,
    placementPositions = [],
    clock = {},
    defaultPxPerBeat = DEFAULT_PX_PER_BEAT,
} = {}) {
    const maxScrollY = Math.max(0, Number(scrollHeight) - Number(viewportHeight));
    const yStart = clamp(Number(sheetTop) - Number(stickyBottom) - 12, 0, maxScrollY);
    const sheetEndY = clamp(Number(sheetBottom) - Number(viewportHeight) + 24, yStart, maxScrollY);

    const bpm = Math.max(1, Number(clock?.bpm || 80));
    const beatOneTime = Number(clock?.beatOneTime || 0);
    const spb = 60.0 / bpm;

    const validPlacements = Array.isArray(placementPositions)
        ? placementPositions
            .filter((item) => item && Number.isFinite(Number(item.beat)) && Number.isFinite(Number(item.docTop)))
            .sort((a, b) => Number(a.beat) - Number(b.beat))
        : [];

    const visibleHeight = Math.max(120, Number(viewportHeight) - Number(stickyBottom));
    const defaultReadingBand = clamp(visibleHeight * 0.22, 32, 120);
    let readingAnchorY = Number(stickyBottom) + defaultReadingBand;

    // Selaraskan readingAnchorY dengan posisi awal chord pertama di layar saat scrollY = yStart
    // agar transisi dari baris 1 ke baris 2 langsung mulus tanpa perlambatan awal.
    if (clock?.hasChords && validPlacements.length > 0) {
        const firstChordViewportY = Number(validPlacements[0].docTop) - yStart;
        const minReadingY = Number(stickyBottom) + 24;
        const maxReadingY = Number(stickyBottom) + Math.min(120, Math.max(48, visibleHeight * 0.28));
        readingAnchorY = clamp(firstChordViewportY, minReadingY, maxReadingY);
    }

    // Untuk lagu ber-chord, akhiri target gulir di posisi chord beat terakhir (ter-clamp ke sheetEndY)
    // agar lagu yang hanya sebagian barisnya diberi beat tidak melompat melewati lirik tanpa beat di bawahnya.
    let yEnd = sheetEndY;
    if (clock?.hasChords && validPlacements.length > 0) {
        const lastChordDocTop = Number(validPlacements[validPlacements.length - 1].docTop);
        const lastChordTargetY = clamp(lastChordDocTop - readingAnchorY, yStart, sheetEndY);
        yEnd = lastChordTargetY;
    }

    const totalDistance = Math.max(0, yEnd - yStart);

    if (totalDistance <= 0) {
        return {
            canScroll: false,
            needsInitialAlign: yStart > 0 && Number(sheetBottom) > Number(viewportHeight),
            yStart,
            yEnd,
            totalDistance: 0,
            readingAnchorY,
            totalScrollBeats: 0,
            targetEndTime: beatOneTime,
            anchors: [{ beat: 1, y: yStart }],
        };
    }

    let totalScrollBeats = 0;
    let targetEndTime = beatOneTime;

    if (clock?.hasChords) {
        const speechEnd = Number(clock.lastChordSpeechEndTime);
        const songEnd = Number(clock.songEndTime);

        if (Number.isFinite(speechEnd) && speechEnd > beatOneTime) {
            targetEndTime = speechEnd;
        } else if (Number.isFinite(songEnd) && songEnd > beatOneTime) {
            targetEndTime = songEnd;
        } else {
            const fallbackBeats = Math.max(1, Number(clock.endBeat || 1));
            targetEndTime = beatOneTime + fallbackBeats * spb;
        }

        totalScrollBeats = Math.max(0.01, (targetEndTime - beatOneTime) * (bpm / 60.0));
    } else if (!clock?.isContinuousMetronome) {
        totalScrollBeats = Math.max(1, Number(clock?.endBeat || 1));
        targetEndTime = beatOneTime + totalScrollBeats * spb;
    } else {
        const pxPerBeat = Math.max(1, Number(defaultPxPerBeat || DEFAULT_PX_PER_BEAT));
        totalScrollBeats = totalDistance / pxPerBeat;
        targetEndTime = beatOneTime + totalScrollBeats * spb;
    }

    const endScrollBeat = 1 + totalScrollBeats;
    const anchors = buildBeatScrollAnchors({
        placementPositions: clock?.hasChords ? validPlacements : [],
        yStart,
        yEnd,
        readingAnchorY,
        endScrollBeat,
    });

    return {
        canScroll: anchors.length >= 2,
        needsInitialAlign: yStart > 0 && Number(sheetBottom) > Number(viewportHeight),
        yStart,
        yEnd,
        totalDistance,
        readingAnchorY,
        totalScrollBeats,
        targetEndTime,
        anchors,
    };
}

/**
 * Menghitung posisi gulir Y pada frame saat ini menggunakan interpolasi linear
 * di antara dua jangkar `(beat_i, y_i)` dan `(beat_{i+1}, y_{i+1})`.
 * Selama pre-roll (`currentTime < beatOneTime`), mengembalikan `yStart` tanpa pergerakan.
 *
 * @param {Object} params
 * @returns {Object}
 */
export function computeAnchoredScrollY({
    currentTime = 0,
    beatOneTime = 0,
    bpm = 80,
    anchors = [],
} = {}) {
    if (!Array.isArray(anchors) || anchors.length === 0) {
        return { elapsedSec: 0, elapsedBeats: 0, currentBeat: 1, targetY: 0, reachedEnd: true };
    }

    const first = anchors[0];
    const last = anchors[anchors.length - 1];
    const elapsedSec = Math.max(0, Number(currentTime) - Number(beatOneTime));
    const safeBpm = Math.max(1, Number(bpm || 80));
    const elapsedBeats = elapsedSec * (safeBpm / 60.0);
    const currentBeat = 1 + elapsedBeats;

    if (anchors.length === 1 || currentBeat <= first.beat) {
        return {
            elapsedSec,
            elapsedBeats,
            currentBeat,
            targetY: first.y,
            reachedEnd: anchors.length === 1,
        };
    }

    if (currentBeat >= last.beat) {
        return {
            elapsedSec,
            elapsedBeats,
            currentBeat,
            targetY: last.y,
            reachedEnd: true,
        };
    }

    for (let i = 0; i < anchors.length - 1; i++) {
        const a = anchors[i];
        const b = anchors[i + 1];
        if (currentBeat >= a.beat && currentBeat <= b.beat) {
            const beatSpan = Math.max(1e-6, b.beat - a.beat);
            const ratio = (currentBeat - a.beat) / beatSpan;
            const targetY = a.y + ratio * (b.y - a.y);
            const reachedEnd = targetY >= last.y - 0.001 && i === anchors.length - 2;

            return {
                elapsedSec,
                elapsedBeats,
                currentBeat,
                targetY,
                reachedEnd,
            };
        }
    }

    return {
        elapsedSec,
        elapsedBeats,
        currentBeat,
        targetY: last.y,
        reachedEnd: true,
    };
}

/**
 * Pengendali Auto-Scroll berbasis `requestAnimationFrame` yang membaca jam
 * `AudioContext.currentTime` secara pasif dari `AudioEngine.getClockSnapshot()`.
 */
export class AutoScrollController {
    constructor({ getClock, getTapeDeckEl, getChordSheetEl } = {}) {
        this.getClock = getClock;
        this.getTapeDeckEl = getTapeDeckEl;
        this.getChordSheetEl = getChordSheetEl;

        this.isActive = false;
        this.isApplyingScroll = false;
        this.rafId = null;
        this.resizeTimer = null;
        this.metrics = null;
        this.layoutCache = null;
        this.lastBeatOneTime = null;
        this.lastSpeechEndTime = null;
        this.lastProgrammaticY = 0;

        this.boundOnManualInput = this.handleManualInput.bind(this);
        this.boundOnKeyDown = this.handleKeyDown.bind(this);
        this.boundOnWindowScroll = this.handleWindowScroll.bind(this);
        this.boundOnResize = this.handleResize.bind(this);
    }

    /**
     * Mengukur posisi vertikal chord sheet, tinggi sticky Tape Deck, dan posisi DOM
     * setiap `#placement-{id}` sekali saja di luar rAF (bebas layout thrashing).
     * Memakai `document.documentElement.clientHeight` agar `maxScrollY` identik dengan batas gulir browser.
     *
     * @param {Object|null} clock
     * @returns {Object|null}
     */
    measureLayout(clock) {
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            return null;
        }

        const chordSheetEl = this.getChordSheetEl ? this.getChordSheetEl() : null;
        if (!chordSheetEl) {
            return null;
        }

        const tapeDeckEl = this.getTapeDeckEl ? this.getTapeDeckEl() : null;
        let stickyBottom = 0;
        if (tapeDeckEl) {
            const computedTop = parseFloat(window.getComputedStyle(tapeDeckEl).top);
            if (Number.isFinite(computedTop)) {
                stickyBottom = computedTop + tapeDeckEl.offsetHeight;
            } else {
                stickyBottom = Math.max(0, tapeDeckEl.getBoundingClientRect().bottom);
            }
        }

        const sheetRect = chordSheetEl.getBoundingClientRect();
        const scrollY = window.scrollY || window.pageYOffset || 0;
        const viewportHeight = document.documentElement.clientHeight || window.innerHeight;
        const scrollHeight = document.documentElement.scrollHeight;

        const placementPositions = [];
        const chordAnchors = Array.isArray(clock?.chordAnchors) ? clock.chordAnchors : [];
        for (const item of chordAnchors) {
            const el = document.getElementById(`placement-${item.placementId}`);
            if (el) {
                const rect = el.getBoundingClientRect();
                placementPositions.push({
                    beat: item.start_beat,
                    docTop: rect.top + scrollY,
                });
            }
        }

        return {
            sheetTop: sheetRect.top + scrollY,
            sheetBottom: sheetRect.bottom + scrollY,
            stickyBottom,
            viewportHeight,
            scrollHeight,
            placementPositions,
        };
    }

    /**
     * Memulai sesi auto-scroll: pindah ke awal chord sheet saat pre-roll,
     * tahan hingga Beat 1, lalu gulir halus antar-baris chord hingga chord terakhir selesai dibacakan.
     */
    start() {
        this.stop();

        const clock = this.getClock ? this.getClock() : null;
        if (!clock || !clock.isPlaying) {
            return;
        }

        const layout = this.measureLayout(clock);
        if (!layout) {
            return;
        }

        const metrics = computeAutoScrollMetrics({ ...layout, clock });
        this.layoutCache = layout;
        this.metrics = metrics;
        this.lastBeatOneTime = clock.beatOneTime;
        this.lastSpeechEndTime = clock.lastChordSpeechEndTime;

        if (!metrics.canScroll) {
            // Untuk lagu pendek-sedang yang terpotong di bawah viewport saat scrollY = 0,
            // tetap sejajarkan ke yStart saat pre-roll agar seluruh chord sheet tampil penuh.
            if (metrics.needsInitialAlign) {
                this.scrollProgrammaticallyTo(metrics.yStart);
            }
            return;
        }

        this.isActive = true;
        this.scrollProgrammaticallyTo(metrics.yStart);
        this.attachListeners();
        this.rafId = window.requestAnimationFrame(() => this.tick());
    }

    /**
     * Menghentikan loop auto-scroll dan melepaskan listener tanpa mengubah posisi scroll.
     */
    stop() {
        this.isActive = false;

        if (this.rafId !== null) {
            window.cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }

        if (this.resizeTimer !== null) {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = null;
        }

        this.detachListeners();
    }

    destroy() {
        this.stop();
    }

    /**
     * Memindahkan scroll secara programatik lalu membaca kembali `window.scrollY` aktual
     * agar pembulatan DPI / batas clamp browser tidak pernah salah terdeteksi sebagai gulir manual.
     *
     * @param {number} targetY
     */
    scrollProgrammaticallyTo(targetY) {
        this.isApplyingScroll = true;
        try {
            window.scrollTo({ top: targetY, behavior: 'instant' });
            this.lastProgrammaticY = window.scrollY || window.pageYOffset || 0;
        } finally {
            this.isApplyingScroll = false;
        }
    }

    tick() {
        if (!this.isActive) {
            return;
        }

        const clock = this.getClock ? this.getClock() : null;
        if (!clock || !clock.isPlaying || !this.metrics) {
            this.stop();
            return;
        }

        const currentScrollY = window.scrollY || window.pageYOffset || 0;
        if (Math.abs(currentScrollY - this.lastProgrammaticY) > MANUAL_SCROLL_TOLERANCE_PX) {
            this.stop();
            return;
        }

        // Kalibrasi ulang metrik ringan bila offset berubah live tanpa reflow DOM
        if (
            this.layoutCache &&
            (clock.beatOneTime !== this.lastBeatOneTime || clock.lastChordSpeechEndTime !== this.lastSpeechEndTime)
        ) {
            this.metrics = computeAutoScrollMetrics({ ...this.layoutCache, clock });
            this.lastBeatOneTime = clock.beatOneTime;
            this.lastSpeechEndTime = clock.lastChordSpeechEndTime;
            if (!this.metrics.canScroll) {
                this.stop();
                return;
            }
        }

        const { targetY, reachedEnd } = computeAnchoredScrollY({
            currentTime: clock.currentTime,
            beatOneTime: clock.beatOneTime,
            bpm: clock.bpm,
            anchors: this.metrics.anchors,
        });

        if (Math.abs(targetY - currentScrollY) >= SCROLL_EPSILON_PX) {
            this.scrollProgrammaticallyTo(targetY);
        }

        if (reachedEnd) {
            this.stop();
            return;
        }

        this.rafId = window.requestAnimationFrame(() => this.tick());
    }

    attachListeners() {
        window.addEventListener('wheel', this.boundOnManualInput, { passive: true });
        window.addEventListener('touchstart', this.boundOnManualInput, { passive: true });
        window.addEventListener('keydown', this.boundOnKeyDown, { passive: true });
        window.addEventListener('scroll', this.boundOnWindowScroll, { passive: true });
        window.addEventListener('resize', this.boundOnResize, { passive: true });
    }

    detachListeners() {
        if (typeof window === 'undefined') {
            return;
        }
        window.removeEventListener('wheel', this.boundOnManualInput);
        window.removeEventListener('touchstart', this.boundOnManualInput);
        window.removeEventListener('keydown', this.boundOnKeyDown);
        window.removeEventListener('scroll', this.boundOnWindowScroll);
        window.removeEventListener('resize', this.boundOnResize);
    }

    handleManualInput() {
        if (!this.isActive) {
            return;
        }
        this.stop();
    }

    handleKeyDown(event) {
        if (!this.isActive || !MANUAL_SCROLL_KEYS.has(event.key)) {
            return;
        }
        const tag = event.target?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return;
        }
        this.stop();
    }

    handleWindowScroll() {
        if (!this.isActive || this.isApplyingScroll) {
            return;
        }
        const currentScrollY = window.scrollY || window.pageYOffset || 0;
        const delta = Math.abs(currentScrollY - this.lastProgrammaticY);

        if (delta > MANUAL_SCROLL_TOLERANCE_PX) {
            this.stop();
        }
    }

    handleResize() {
        if (!this.isActive) {
            return;
        }
        if (this.resizeTimer !== null) {
            clearTimeout(this.resizeTimer);
        }
        this.resizeTimer = setTimeout(() => {
            this.resizeTimer = null;
            if (!this.isActive) {
                return;
            }
            const clock = this.getClock ? this.getClock() : null;
            const layout = this.measureLayout(clock);
            if (!clock || !clock.isPlaying || !layout) {
                return;
            }
            this.layoutCache = layout;
            this.metrics = computeAutoScrollMetrics({ ...layout, clock });
            this.lastProgrammaticY = window.scrollY || window.pageYOffset || 0;
            if (!this.metrics.canScroll) {
                this.stop();
            }
        }, RESIZE_DEBOUNCE_MS);
    }
}

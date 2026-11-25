import test from 'node:test';
import assert from 'node:assert/strict';

import {
    AutoScrollController,
    buildBeatScrollAnchors,
    computeAnchoredScrollY,
    computeAutoScrollMetrics,
    resolveInitialAutoScroll,
} from '../../resources/js/player/auto-scroll.js';

test('resolveInitialAutoScroll: default ON, otomatis OFF saat prefers-reduced-motion, dan menghormati localStorage', () => {
    assert.equal(resolveInitialAutoScroll({ storedValue: null, prefersReducedMotion: false }), true);
    assert.equal(resolveInitialAutoScroll({ storedValue: null, prefersReducedMotion: true }), false);
    assert.equal(resolveInitialAutoScroll({ storedValue: 'true', prefersReducedMotion: true }), true);
    assert.equal(resolveInitialAutoScroll({ storedValue: 'false', prefersReducedMotion: false }), false);
});

test('Opsi C: menyusun jangkar monotonik per baris visual (menggabungkan chord pada baris sama agar tidak stop-and-go)', () => {
    const anchors = buildBeatScrollAnchors({
        yStart: 100,
        yEnd: 1000,
        readingAnchorY: 300,
        endScrollBeat: 33,
        placementPositions: [
            { beat: 1, docTop: 380 },
            { beat: 5, docTop: 380 },
            { beat: 9, docTop: 500 },
            { beat: 13, docTop: 502 },
            { beat: 17, docTop: 760 },
            { beat: 21, docTop: 760 },
            { beat: 25, docTop: 950 },
        ],
    });

    assert.deepEqual(anchors, [
        { beat: 1, y: 100 },
        { beat: 9, y: 200 },
        { beat: 17, y: 460 },
        { beat: 25, y: 650 },
        { beat: 33, y: 1000 },
    ]);
});

test('Opsi C: satu placement dengan beat tidak berurutan (out-of-order) tidak merusak jangkar sisa lagu', () => {
    const anchors = buildBeatScrollAnchors({
        yStart: 100,
        yEnd: 1000,
        readingAnchorY: 300,
        endScrollBeat: 33,
        placementPositions: [
            { beat: 1, docTop: 380 },
            // Outlier: beat 5 keliru berada jauh di bawah (docTop 1500)
            { beat: 5, docTop: 1500 },
            // Baris normal berikutnya tetap terurut rapi
            { beat: 9, docTop: 500 },
            { beat: 17, docTop: 760 },
            { beat: 25, docTop: 950 },
        ],
    });

    assert.deepEqual(anchors, [
        { beat: 1, y: 100 },
        { beat: 5, y: 200 },
        { beat: 17, y: 460 },
        { beat: 25, y: 650 },
        { beat: 33, y: 1000 },
    ]);
});

test('Opsi C: lagu dengan chord parsial (ada banyak lirik tanpa beat di bawah chord terakhir) berhenti tepat di chord terakhir, tidak melonjak ke dasar sheet', () => {
    const metrics = computeAutoScrollMetrics({
        sheetTop: 312,
        sheetBottom: 3200,
        stickyBottom: 200,
        viewportHeight: 800,
        scrollHeight: 3300,
        placementPositions: [
            { beat: 1, docTop: 360 },
            { beat: 9, docTop: 560 },
            { beat: 17, docTop: 860 },
            // Chord beat terakhir hanya sampai docTop 1100 (jauh di atas sheetBottom 3200)
            { beat: 25, docTop: 1100 },
        ],
        clock: {
            bpm: 120,
            beatOneTime: 5.0,
            endBeat: 28,
            songEndTime: 19.0,
            hasChords: true,
            lastChordSpeechEndTime: 16.0,
            isContinuousMetronome: false,
        },
    });

    assert.equal(metrics.canScroll, true);
    // yEnd berhenti pada posisi chord terakhir (1100 - readingAnchorY), bukan sheetBottom - 800 + 24 = 2424
    assert.ok(metrics.yEnd < 1000);
    assert.equal(metrics.yEnd, 1100 - metrics.readingAnchorY);
});

test('Opsi C: diam di yStart saat pre-roll, selaras dengan baris 1..akhir, dan selesai di akhir ucapan chord terakhir', () => {
    const metrics = computeAutoScrollMetrics({
        sheetTop: 312,
        sheetBottom: 2300,
        stickyBottom: 200,
        viewportHeight: 800,
        scrollHeight: 2400,
        placementPositions: [
            { beat: 1, docTop: 360 },   // viewportY saat yStart=100 adalah 260 (stickyBottom+60) -> readingAnchorY = 260
            { beat: 9, docTop: 560 },   // rawY = 560 - 260 = 300
            { beat: 17, docTop: 960 },  // rawY = 960 - 260 = 700
            { beat: 25, docTop: 1360 }, // rawY = 1360 - 260 = 1100
            { beat: 33, docTop: 2200 }, // rawY = 1940 -> clamp sheetEndY = 1524
        ],
        clock: {
            bpm: 120,
            beatOneTime: 5.0,
            endBeat: 36,
            songEndTime: 23.0,
            hasChords: true,
            lastChordSpeechEndTime: 21.0,
            isContinuousMetronome: false,
        },
    });

    assert.equal(metrics.canScroll, true);
    assert.equal(metrics.yStart, 100);
    assert.equal(metrics.yEnd, 1524);
    assert.equal(metrics.readingAnchorY, 260);

    // Pre-roll (t = 3.5s < 5.0s, saat TTS aba-aba pertama berbunyi) => tetap diam di yStart
    const preRoll = computeAnchoredScrollY({
        currentTime: 3.5,
        beatOneTime: 5.0,
        bpm: 120,
        anchors: metrics.anchors,
    });
    assert.equal(preRoll.elapsedBeats, 0);
    assert.equal(preRoll.targetY, 100);
    assert.equal(preRoll.reachedEnd, false);

    // Tepat di Beat 9 (t = 9.0s) => tepat di y = 300
    const atBeat9 = computeAnchoredScrollY({
        currentTime: 9.0,
        beatOneTime: 5.0,
        bpm: 120,
        anchors: metrics.anchors,
    });
    assert.ok(Math.abs(atBeat9.targetY - 300) < 1e-6);

    // Tepat di pertengahan Beat 9..17 (Beat 13, t = 11.0s) => tepat di 500
    const atBeat13 = computeAnchoredScrollY({
        currentTime: 11.0,
        beatOneTime: 5.0,
        bpm: 120,
        anchors: metrics.anchors,
    });
    assert.ok(Math.abs(atBeat13.targetY - 500) < 1e-6);

    // Tepat saat chord terakhir selesai dibacakan (t = 21.0s) => tiba di yEnd & berhenti
    const end = computeAnchoredScrollY({
        currentTime: 21.0,
        beatOneTime: 5.0,
        bpm: 120,
        anchors: metrics.anchors,
    });
    assert.equal(end.targetY, metrics.yEnd);
    assert.equal(end.reachedEnd, true);
});

test('Kecepatan gulir proporsional tepat 2x lebih cepat pada 120 BPM dibanding 60 BPM pada segmen yang sama', () => {
    const anchors = [
        { beat: 1, y: 100 },
        { beat: 17, y: 500 },
    ];
    const at60 = computeAnchoredScrollY({ currentTime: 8.0, beatOneTime: 4.0, bpm: 60, anchors });
    const at120 = computeAnchoredScrollY({ currentTime: 8.0, beatOneTime: 4.0, bpm: 120, anchors });

    assert.equal(at60.targetY - 100, 100);
    assert.equal(at120.targetY - 100, 200);
});

test('Pengaman kasus tepi: 1 chord di Beat 1 saja (TTS selesai sebelum Beat 1) fallback ke songEndTime tanpa pembagian nol', () => {
    const metrics = computeAutoScrollMetrics({
        sheetTop: 250,
        sheetBottom: 1800,
        stickyBottom: 180,
        viewportHeight: 800,
        scrollHeight: 1900,
        placementPositions: [{ beat: 1, docTop: 300 }, { beat: 4, docTop: 1500 }],
        clock: {
            bpm: 80,
            beatOneTime: 3.0,
            endBeat: 4,
            songEndTime: 6.0,
            hasChords: true,
            lastChordSpeechEndTime: 2.0,
            isContinuousMetronome: false,
        },
    });

    assert.equal(metrics.canScroll, true);
    assert.equal(metrics.targetEndTime, 6.0);
});

test('Edge case lagu sedang (mis. Song #25, 4 baris): walau seluruh sheet muat setelah sejajar di yStart, needsInitialAlign = true agar tidak terpotong di bawah viewport', () => {
    const metrics = computeAutoScrollMetrics({
        sheetTop: 532,
        sheetBottom: 1028,
        stickyBottom: 252,
        viewportHeight: 800,
        scrollHeight: 1116,
        placementPositions: [
            { beat: 1, docTop: 580 },
            { beat: 9, docTop: 660 },
            { beat: 17, docTop: 780 },
            { beat: 25, docTop: 860 },
        ],
        clock: {
            bpm: 120,
            beatOneTime: 2.1,
            endBeat: 32,
            songEndTime: 18.1,
            hasChords: true,
            lastChordSpeechEndTime: 15.1,
            isContinuousMetronome: false,
        },
    });

    assert.equal(metrics.canScroll, false);
    assert.equal(metrics.yStart, 268);
    assert.equal(metrics.needsInitialAlign, true);
});

test('Edge case: konten benar-benar pendek di dalam viewport mengembalikan canScroll = false & needsInitialAlign = false', () => {
    const metrics = computeAutoScrollMetrics({
        sheetTop: 250,
        sheetBottom: 600,
        stickyBottom: 180,
        viewportHeight: 900,
        scrollHeight: 900,
        placementPositions: [{ beat: 1, docTop: 300 }, { beat: 5, docTop: 400 }],
        clock: {
            bpm: 100,
            beatOneTime: 2.0,
            endBeat: 16,
            songEndTime: 11.6,
            hasChords: true,
            lastChordSpeechEndTime: 10.0,
            isContinuousMetronome: false,
        },
    });

    assert.equal(metrics.canScroll, false);
    assert.equal(metrics.needsInitialAlign, false);
    assert.equal(metrics.totalDistance, 0);
});

test('Fallback 0 chord: backing track memakai endBeat, metronom kontinyu memakai defaultPxPerBeat', () => {
    const withBacking = computeAutoScrollMetrics({
        sheetTop: 212,
        sheetBottom: 1800,
        stickyBottom: 200,
        viewportHeight: 800,
        scrollHeight: 1900,
        clock: {
            bpm: 100,
            beatOneTime: 4.0,
            endBeat: 32,
            songEndTime: 23.2,
            hasChords: false,
            lastChordSpeechEndTime: null,
            isContinuousMetronome: false,
        },
    });
    assert.equal(withBacking.canScroll, true);
    assert.equal(withBacking.totalScrollBeats, 32);
    assert.equal(withBacking.anchors.length, 2);

    const continuous = computeAutoScrollMetrics({
        sheetTop: 212,
        sheetBottom: 1800,
        stickyBottom: 200,
        viewportHeight: 800,
        scrollHeight: 1900,
        clock: {
            bpm: 100,
            beatOneTime: 4.0,
            endBeat: 8,
            songEndTime: Infinity,
            hasChords: false,
            lastChordSpeechEndTime: null,
            isContinuousMetronome: true,
        },
    });
    assert.equal(continuous.canScroll, true);
    assert.ok(Math.abs(continuous.totalScrollBeats - (continuous.totalDistance / 12)) < 1e-6);
});

test('AutoScrollController: pembulatan/clamp window.scrollTo oleh browser tidak salah menghentikan auto-scroll pada frame pertama', () => {
    const origWindow = globalThis.window;
    const origDocument = globalThis.document;
    let currentY = 0;
    let rafCallback = null;
    const listeners = new Map();

    // Simulasikan browser yang men-clamp scroll maksimal sedikit lebih rendah (mis. 250 vs yStart 268)
    globalThis.window = {
        get scrollY() { return currentY; },
        get pageYOffset() { return currentY; },
        innerHeight: 800,
        getComputedStyle: () => ({ top: '72px' }),
        scrollTo: ({ top }) => {
            currentY = Math.min(250, Math.max(0, top));
        },
        addEventListener: (ev, fn) => listeners.set(ev, fn),
        removeEventListener: (ev) => listeners.delete(ev),
        requestAnimationFrame: (cb) => { rafCallback = cb; return 1; },
        cancelAnimationFrame: () => { rafCallback = null; },
    };
    globalThis.document = {
        documentElement: { clientHeight: 800, scrollHeight: 2000 },
        getElementById: () => null,
    };

    try {
        const clock = {
            isPlaying: true,
            currentTime: 2.0,
            beatOneTime: 4.0,
            bpm: 100,
            endBeat: 32,
            songEndTime: 23.2,
            isContinuousMetronome: false,
            hasChords: false,
            lastChordSpeechEndTime: null,
            chordAnchors: [],
        };

        const controller = new AutoScrollController({
            getClock: () => clock,
            getTapeDeckEl: () => ({ offsetHeight: 180, getBoundingClientRect: () => ({ bottom: 252 }) }),
            getChordSheetEl: () => ({ getBoundingClientRect: () => ({ top: 532, bottom: 1900 }) }),
        });

        controller.start();
        assert.equal(controller.isActive, true);
        assert.equal(controller.lastProgrammaticY, 250);

        // Jalankan 1 tick selama pre-roll: tidak boleh mati karena selisih clamp browser
        rafCallback();
        assert.equal(controller.isActive, true);

        // Simulasikan gulir manual pengguna (wheel) => langsung berhenti untuk sesi ini
        listeners.get('wheel')();
        assert.equal(controller.isActive, false);
    } finally {
        globalThis.window = origWindow;
        globalThis.document = origDocument;
    }
});

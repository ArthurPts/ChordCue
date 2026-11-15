import test from 'node:test';
import assert from 'node:assert/strict';
import { AudioEngine, computeAnchors, computeChordScheduleTime, measureSpeechEnd } from '../../resources/js/audio/audio-engine.js';

function assertClose(actual, expected, tolerance = 1e-6, msg = '') {
    assert.ok(
        Math.abs(actual - expected) <= tolerance,
        `${msg} expected ${expected} ± ${tolerance}, got ${actual}`
    );
}

function makeSyntheticBuffer(sampleRate, segments) {
    const totalSamples = Math.round(
        segments.reduce((acc, s) => acc + s.durationSec, 0) * sampleRate
    );
    const data = new Float32Array(totalSamples);
    let cursor = 0;
    for (const seg of segments) {
        const count = Math.round(seg.durationSec * sampleRate);
        for (let i = 0; i < count && cursor + i < totalSamples; i++) {
            data[cursor + i] = seg.amp
                ? seg.amp * Math.sin((2 * Math.PI * 440 * i) / sampleRate)
                : 0;
        }
        cursor += count;
    }
    return {
        sampleRate,
        duration: totalSamples / sampleRate,
        getChannelData: () => data,
    };
}

test('Case 1: offset > 1 bar (5s vs 4s) memicu metronom saat sisa offset 4s dan selesai bersamaan', () => {
    for (const hasBacking of [true, false]) {
        const a = computeAnchors({
            t0: 10,
            bpm: 60,
            beatsPerBar: 4,
            offset: 5,
            hasBacking,
        });
        assertClose(a.barDuration, 4);
        assertClose(a.leadTime, 5);
        assertClose(a.offsetStartTime, 10, 1e-6, 'offset mulai di t0');
        assertClose(a.mulaiCountIn, 11, 1e-6, 'metronom mulai di t0+1 (sisa 4s)');
        assertClose(a.mulaiLagu, 15, 1e-6, 'keduanya selesai bersamaan di t0+5');
        if (hasBacking) {
            assertClose(a.backingStartAudioTime, 10);
        }
    }
});

test('Case 2: offset < 1 bar (2s vs 4s) memulai bar dulu lalu offset saat sisa 2s dan selesai bersamaan', () => {
    for (const hasBacking of [true, false]) {
        const a = computeAnchors({
            t0: 10,
            bpm: 60,
            beatsPerBar: 4,
            offset: 2,
            hasBacking,
        });
        assertClose(a.barDuration, 4);
        assertClose(a.leadTime, 4);
        assertClose(a.mulaiCountIn, 10, 1e-6, 'metronom mulai dulu di t0');
        assertClose(a.offsetStartTime, 12, 1e-6, 'offset mulai di t0+2 (sisa bar 2s)');
        assertClose(a.mulaiLagu, 14, 1e-6, 'keduanya selesai bersamaan di t0+4');
        if (hasBacking) {
            assertClose(a.backingStartAudioTime, 12);
        }
    }
});

test('Lagu 1 (80 BPM, 4/4, offset 10s, tanpa file backing track): 7s intro lalu 3s count-in selesai di 10s', () => {
    const a = computeAnchors({
        t0: 1.0,
        bpm: 80,
        beatsPerBar: 4,
        offset: 10.0,
        hasBacking: false,
    });
    assertClose(a.spb, 0.75);
    assertClose(a.barDuration, 3.0);
    assertClose(a.leadTime, 10.0);
    assertClose(a.offsetStartTime, 1.0);
    assertClose(a.mulaiCountIn, 8.0);
    assertClose(a.mulaiLagu, 11.0);
    assert.equal(a.adaBacking, false);
});

test('Edge cases: offset 0, offset negatif, beragam birama (3/4, 6/8), BPM berbeda', () => {
    const zero = computeAnchors({ t0: 0, bpm: 120, beatsPerBar: 4, offset: 0, hasBacking: true });
    assertClose(zero.barDuration, 2.0);
    assertClose(zero.mulaiCountIn, 0);
    assertClose(zero.mulaiLagu, 2.0);
    assertClose(zero.backingStartAudioTime, 2.0);

    const neg = computeAnchors({ t0: 0, bpm: 60, beatsPerBar: 4, offset: -1.5, hasBacking: true });
    assertClose(neg.mulaiCountIn, 0);
    assertClose(neg.mulaiLagu, 4.0);
    assertClose(neg.backingStartAudioTime, 4.0);
    assertClose(neg.backingBufferOffset, 1.5);

    const threeFour = computeAnchors({ t0: 0, bpm: 90, beatsPerBar: 3, offset: 5.0 });
    assertClose(threeFour.barDuration, 2.0);
    assertClose(threeFour.mulaiCountIn, 3.0);
    assertClose(threeFour.mulaiLagu, 5.0);

    const fastBpm = computeAnchors({ t0: 0, bpm: 200, beatsPerBar: 6, offset: 1.0, songBpm: 80, hasBacking: true });
    assertClose(fastBpm.barDuration, 1.8);
    assert.equal(fastBpm.adaBacking, false, 'backing track auto-mute jika BPM berubah');
    assertClose(fastBpm.mulaiCountIn, 0);
    assertClose(fastBpm.offsetStartTime, 0.8);
    assertClose(fastBpm.mulaiLagu, 1.8);
});

test('measureSpeechEnd mengabaikan trailing silence MP3 dan mendeteksi akhir ucapan aktif secara akurat', () => {
    const sampleRate = 24000;
    // 0.05s encoder delay + 0.40s ucapan (amp 0.5) + 0.20s suara lantai sangat pelan (-46 dBFS < -40 dBFS) + 1.20s nol
    const buf = makeSyntheticBuffer(sampleRate, [
        { durationSec: 0.05, amp: 0 },
        { durationSec: 0.40, amp: 0.5 },
        { durationSec: 0.20, amp: 0.005 },
        { durationSec: 1.20, amp: 0 },
    ]);

    const detected = measureSpeechEnd(buf, -40.0);
    assertClose(detected, 0.45, 0.015, 'akhir ucapan aktif harus sekitar 0.45s, bukan 1.85s');

    // Dengan ambang lebih sensitif (-50 dBFS), lantai 0.005 (~-49 dBFS RMS) ikut terhitung hingga 0.65s
    const sensitive = measureSpeechEnd(buf, -50.0);
    assertClose(sensitive, 0.65, 0.015);
});

test('computeChordScheduleTime memberi lead time 1 detik sebelum ketukan & menangani celah rapat', () => {
    // 1. Chord pertama Lagu 1 (offset 10s, t0=0.1 -> Beat 1 di 10.1, speech 0.5s, margin 1.0s)
    const first = computeChordScheduleTime({
        waktuBeat: 10.1,
        speechDuration: 0.5,
        cueMarginSec: 1.0,
        minStartTime: 0.1,
    });
    assertClose(first.startTime, 8.6);
    assertClose(first.speechEndTime, 9.1);
    assertClose(first.actualMarginSec, 1.0, 1e-6, 'ucapan selesai 1.0 dtk sebelum Beat 1');

    // 2. Celah standar 4 beat (3.0s @ 80 BPM): selesai tepat 1.0 dtk sebelum beat berikutnya
    const standardGap = computeChordScheduleTime({
        waktuBeat: 13.1,
        speechDuration: 0.5,
        cueMarginSec: 1.0,
        minStartTime: 10.1,
    });
    assertClose(standardGap.startTime, 11.6);
    assertClose(standardGap.speechEndTime, 12.1);
    assertClose(standardGap.actualMarginSec, 1.0);

    // 3. Celah rapat 2 beat (1.5s @ 80 BPM) dengan ucapan agak panjang (0.7s): ter-clamp di prevBeatTime (10.1)
    const tightTwoBeat = computeChordScheduleTime({
        waktuBeat: 11.6,
        speechDuration: 0.7,
        cueMarginSec: 1.0,
        minStartTime: 10.1,
    });
    assertClose(tightTwoBeat.startTime, 10.1, 1e-6, 'tidak mendahului ketukan chord sebelumnya');
    assertClose(tightTwoBeat.speechEndTime, 10.8);
    assertClose(tightTwoBeat.actualMarginSec, 0.8);

    // 4. Kasus ekstrem speechEndTime > waktuBeat (120 BPM celah 1 beat = 0.5s, speech 0.7s)
    const ultraTight = computeChordScheduleTime({
        waktuBeat: 2.5,
        speechDuration: 0.7,
        cueMarginSec: 1.0,
        minStartTime: 2.0,
    });
    assertClose(ultraTight.startTime, 2.0, 1e-6, 'ter-clamp rapi pada prevBeatTime');
    assertClose(ultraTight.speechEndTime, 2.7);
    assert.ok(ultraTight.speechEndTime > 2.5, 'speechEndTime > waktuBeat tertangani tanpa error');
    assertClose(ultraTight.actualMarginSec, -0.2);
});

test('AudioEngine.recalibrateScheduledEvents memakai computeChordScheduleTime secara konsisten', () => {
    const engine = new AudioEngine({ cueMarginMs: 1000 });
    engine.timelineData = { song: { bpm: 80, time_signature: { numerator: 4 } } };
    engine.bpm = 80;
    engine.timeSignatureNumerator = 4;
    engine.t0 = 1.0;
    engine.mulaiLagu = 11.0; // offset 10s
    engine.endBeat = 8;
    engine.scheduledChords = [
        { start_beat: 1, duration: 0.5, waktuMulai: 0, waktuBeat: 0, waktuSelesaiAktif: 0 },
        { start_beat: 3, duration: 0.7, waktuMulai: 0, waktuBeat: 0, waktuSelesaiAktif: 0 },
    ];
    engine.scheduledBeats = [{ beatNumber: 1, time: 0 }];

    engine.recalibrateScheduledEvents();

    // Beat 1 = 11.0 -> speech 0.5s, margin 1.0s -> start = 9.5
    assertClose(engine.scheduledChords[0].waktuBeat, 11.0);
    assertClose(engine.scheduledChords[0].waktuMulai, 9.5);
    assertClose(engine.scheduledChords[0].waktuSelesaiAktif, 12.5);

    // Beat 3 = 12.5 (1.5s sesudah Beat 1) -> ideal = 10.8 < prevBeatTime (11.0) -> ter-clamp ke 11.0
    assertClose(engine.scheduledChords[1].waktuBeat, 12.5);
    assertClose(engine.scheduledChords[1].waktuMulai, 11.0);
    assertClose(engine.scheduledBeats[0].time, 11.0);
});


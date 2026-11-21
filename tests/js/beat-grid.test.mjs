import assert from 'node:assert/strict';
import test from 'node:test';
import { barOfBeat, buildBeatGrid } from '../../resources/js/beat-editor.js';

test('barOfBeat menghitung nomor bar global 1-indexed dengan benar', () => {
    assert.equal(barOfBeat(1, 4), 1);
    assert.equal(barOfBeat(4, 4), 1);
    assert.equal(barOfBeat(5, 4), 2);
    assert.equal(barOfBeat(9, 4), 3);
    assert.equal(barOfBeat(6, 6), 1);
    assert.equal(barOfBeat(7, 6), 2);
});

test('buildBeatGrid mengikuti contoh §7.2 (4/4) dan memperbarui baris berikutnya saat bar kosong terisi', () => {
    const sections = [
        {
            id: 1,
            name: 'Verse',
            lines: [
                {
                    id: 10,
                    content: 'Baris 1',
                    placements: [
                        { id: 1, chord_name: 'C', position: 0, start_beat: 1 },
                        { id: 2, chord_name: 'G', position: 4, start_beat: 3 },
                        { id: 3, chord_name: 'Am', position: 8, start_beat: 5 },
                    ],
                },
                {
                    id: 20,
                    content: 'Baris 2',
                    placements: [],
                },
            ],
        },
    ];

    const grid = buildBeatGrid(sections, 4);
    assert.equal(grid[10].barAwal, 1);
    assert.equal(grid[10].barAkhirTerpakai, 2);
    assert.equal(grid[10].bars.length, 3);
    assert.equal(grid[10].bars[2].isEmptyTrailing, true);
    assert.equal(grid[10].bars[2].beats[0].beatGlobal, 9);

    assert.equal(grid[20].barAwal, 3);
    assert.equal(grid[20].bars.length, 1);
    assert.equal(grid[20].bars[0].barLokal, 1);
    assert.equal(grid[20].bars[0].beats[0].beatGlobal, 9);

    // Tambah chord beat 9 pada Baris 1 -> Baris 2 bergeser mulai bar global 4 (beat 13..16)
    sections[0].lines[0].placements.push({ id: 4, chord_name: 'F', position: null, start_beat: 9 });
    const updated = buildBeatGrid(sections, 4);
    assert.equal(updated[10].bars.length, 4);
    assert.equal(updated[20].barAwal, 4);
    assert.equal(updated[20].bars[0].beats[0].beatGlobal, 13);
});

test('buildBeatGrid mendukung birama 6/8 dan 3/4 dengan mempertahankan nilai start_beat global', () => {
    const sections = [
        {
            id: 1,
            name: 'Chorus',
            lines: [
                {
                    id: 100,
                    content: 'Lirik waltz / 6/8',
                    placements: [
                        { id: 1, chord_name: 'D', position: 0, start_beat: 1 },
                        { id: 2, chord_name: 'A', position: 6, start_beat: 7 },
                    ],
                },
            ],
        },
    ];

    const grid68 = buildBeatGrid(sections, 6);
    assert.equal(grid68[100].bars.length, 3);
    assert.equal(grid68[100].bars[0].beats.length, 6);
    assert.equal(grid68[100].bars[1].beats[0].beatGlobal, 7);
    assert.equal(grid68[100].bars[1].beats[0].placement.chord_name, 'A');

    const grid34 = buildBeatGrid(sections, 3);
    assert.equal(grid34[100].bars.length, 4);
    assert.equal(grid34[100].bars[0].beats.length, 3);
    assert.equal(grid34[100].bars[2].beats[0].beatGlobal, 7);
    assert.equal(grid34[100].bars[2].beats[0].placement.chord_name, 'A');
});

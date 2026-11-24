import { createSharedEditorState, mergeEditorMixins } from './editor-shared.js';

/**
 * Menghitung nomor bar global (1-indexed) yang memuat suatu nomor beat global (§7.2).
 *
 * @param {number} beat
 * @param {number} beatsPerBar
 * @returns {number}
 */
export function barOfBeat(beat, beatsPerBar) {
    const safeBeats = Math.max(1, Number(beatsPerBar || 4));
    return Math.ceil(Number(beat) / safeBeats);
}

/**
 * Membangun peta grid birama berlapis per baris lirik sesuai spesifikasi §7.2.
 * Penomoran beat tetap global (1-indexed) dan tidak dipengaruhi oleh offset backing track.
 *
 * @param {Array} sections
 * @param {number} beatsPerBar
 * @returns {Object<number, {barAwal: number, barAkhirTerpakai: number, bars: Array}>}
 */
export function buildBeatGrid(sections, beatsPerBar) {
    const safeBeats = Math.max(1, Number(beatsPerBar || 4));
    let highestBarUsed = 0;
    const gridByLine = {};

    for (const section of sections || []) {
        for (const line of section.lines || []) {
            const placementsByBeat = new Map();

            for (const placement of line.placements || []) {
                if (placement.start_beat !== null && placement.start_beat !== undefined) {
                    placementsByBeat.set(Number(placement.start_beat), placement);
                }
            }

            let barAwal;
            let barAkhirTerpakai;

            if (placementsByBeat.size > 0) {
                const beats = Array.from(placementsByBeat.keys());
                const minBeat = Math.min(...beats);
                const maxBeat = Math.max(...beats);
                barAwal = barOfBeat(minBeat, safeBeats);
                barAkhirTerpakai = barOfBeat(maxBeat, safeBeats);
            } else {
                barAwal = highestBarUsed + 1;
                barAkhirTerpakai = barAwal - 1;
            }

            const lastBarToRender = barAkhirTerpakai + 1;
            const bars = [];

            for (let globalBar = barAwal; globalBar <= lastBarToRender; globalBar++) {
                const localBar = globalBar - barAwal + 1;
                const cells = [];

                for (let beatInBar = 1; beatInBar <= safeBeats; beatInBar++) {
                    const globalBeat = (globalBar - 1) * safeBeats + beatInBar;
                    cells.push({
                        beatGlobal: globalBeat,
                        beatInBar,
                        isDownbeat: beatInBar === 1,
                        placement: placementsByBeat.get(globalBeat) || null,
                    });
                }

                bars.push({
                    barGlobal: globalBar,
                    barLokal: localBar,
                    isEmptyTrailing: globalBar === lastBarToRender,
                    beats: cells,
                });
            }

            if (barAkhirTerpakai > highestBarUsed) {
                highestBarUsed = barAkhirTerpakai;
            }

            gridByLine[line.id] = {
                barAwal,
                barAkhirTerpakai,
                bars,
            };
        }
    }

    return gridByLine;
}

/**
 * Komponen Alpine.js untuk halaman Beat Editor (/songs/{song}/editor/beat).
 */
export function beatEditor(initialConfig) {
    const shared = createSharedEditorState(initialConfig);

    return mergeEditorMixins(shared, {
        // Drag and drop state untuk beat cells
        dragPayload: null,
        dragOverBeatKey: null,

        // Modal konfirmasi tabrakan beat global (409 Conflict)
        conflictModal: {
            open: false,
            mode: 'create',
            lineId: null,
            chordId: null,
            placementId: null,
            conflictPlacementId: null,
            startBeat: null,
            incomingChordName: '',
            existingChordName: '',
        },

        get beatsPerBar() {
            const parts = String(this.metaForm.time_signature || '4/4').split('/');
            const parsed = parseInt(parts[0], 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : 4;
        },

        get beatGridMap() {
            return buildBeatGrid(this.sections, this.beatsPerBar);
        },

        getBeatGridColsClass() {
            const B = this.beatsPerBar;
            if (B === 2) return 'grid-cols-2';
            if (B === 3) return 'grid-cols-3';
            if (B === 6) return 'grid-cols-3 sm:grid-cols-6';
            return 'grid-cols-4';
        },

        getLineGrid(lineId) {
            return this.beatGridMap[lineId] || { barAwal: 1, barAkhirTerpakai: 0, bars: [] };
        },

        startDragLibraryChord(event, chord) {
            this.dragPayload = {
                type: 'library',
                chordId: chord.id,
                chordName: chord.name,
            };
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('text/plain', chord.name);
            }
        },

        startDragUnbeatedPlacement(event, placement) {
            this.dragPayload = {
                type: 'unbeated-placement',
                placementId: placement.id,
                lineId: placement.lyric_line_id,
                chordId: placement.chord_id,
                chordName: placement.chord_name,
            };
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', placement.chord_name);
            }
        },

        startDragPlacedBeat(event, placement) {
            this.dragPayload = {
                type: 'beat-placement',
                placementId: placement.id,
                lineId: placement.lyric_line_id,
                chordId: placement.chord_id,
                chordName: placement.chord_name,
                currentBeat: placement.start_beat,
            };
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', placement.chord_name);
            }
        },

        endDrag() {
            this.dragPayload = null;
            this.dragOverBeatKey = null;
        },

        canDropOnBeatLine(lineId) {
            if (!this.dragPayload) {
                return false;
            }
            if (this.dragPayload.type === 'library') {
                return true;
            }
            return Number(this.dragPayload.lineId) === Number(lineId);
        },

        onBeatDragOver(event, lineId, beatGlobal) {
            if (!this.canDropOnBeatLine(lineId)) {
                return;
            }
            event.preventDefault();
            this.dragOverBeatKey = `${lineId}:${beatGlobal}`;
        },

        onBeatDragLeave(lineId, beatGlobal) {
            if (this.dragOverBeatKey === `${lineId}:${beatGlobal}`) {
                this.dragOverBeatKey = null;
            }
        },

        isBeatHighlighted(lineId, beatGlobal) {
            return this.dragOverBeatKey === `${lineId}:${beatGlobal}`;
        },

        async onBeatDrop(event, line, beatGlobal) {
            event.preventDefault();
            this.dragOverBeatKey = null;

            if (!this.dragPayload || !this.canDropOnBeatLine(line.id)) {
                this.dragPayload = null;
                return;
            }

            const payload = { ...this.dragPayload };
            this.dragPayload = null;
            this.clearFeedback();

            if (payload.type === 'library') {
                await this.submitCreateBeatPlacement(line, payload.chordId, payload.chordName, beatGlobal, false);
            } else {
                await this.submitMoveBeatPlacement(line, payload.placementId, payload.chordName, beatGlobal, false);
            }
        },

        removePlacementFromAllLines(placementId) {
            if (!placementId) return;
            for (const section of this.sections) {
                for (const line of section.lines) {
                    line.placements = line.placements.filter((item) => Number(item.id) !== Number(placementId));
                }
            }
        },

        async submitCreateBeatPlacement(line, chordId, chordName, startBeat, force = false, conflictPlacementId = null) {
            const { status, ok, data } = await this.requestJson(
                `/lines/${line.id}/placements`,
                'POST',
                { chord_id: chordId, start_beat: startBeat, force }
            );

            if (status === 409) {
                this.conflictModal = {
                    open: true,
                    mode: 'create',
                    lineId: line.id,
                    chordId,
                    placementId: null,
                    conflictPlacementId: data.conflict?.id || null,
                    startBeat,
                    incomingChordName: chordName,
                    existingChordName: data.conflict?.chord_name || 'chord lain',
                };
                return;
            }

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menempatkan chord pada beat.'));
                return;
            }

            if (force) {
                this.removePlacementFromAllLines(conflictPlacementId);
            }

            line.placements.push(data.placement);
            this.showSuccess(`Chord ${data.placement.chord_name} ditempatkan di beat ${startBeat}.`);
        },

        async submitMoveBeatPlacement(line, placementId, chordName, startBeat, force = false, conflictPlacementId = null) {
            const existing = line.placements.find((item) => Number(item.id) === Number(placementId));
            if (existing && Number(existing.start_beat) === Number(startBeat)) {
                return;
            }

            const { status, ok, data } = await this.requestJson(
                `/placements/${placementId}`,
                'PATCH',
                { start_beat: startBeat, force }
            );

            if (status === 409) {
                this.conflictModal = {
                    open: true,
                    mode: 'move',
                    lineId: line.id,
                    chordId: existing ? existing.chord_id : null,
                    placementId,
                    conflictPlacementId: data.conflict?.id || null,
                    startBeat,
                    incomingChordName: chordName,
                    existingChordName: data.conflict?.chord_name || 'chord lain',
                };
                return;
            }

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal memperbarui beat chord.'));
                return;
            }

            if (force && conflictPlacementId) {
                this.removePlacementFromAllLines(conflictPlacementId);
            }

            if (existing) {
                existing.start_beat = data.placement.start_beat;
            } else {
                line.placements.push(data.placement);
            }

            this.showSuccess(`Beat chord ${data.placement.chord_name} diatur ke beat ${startBeat}.`);
        },

        async confirmOverwriteConflict() {
            const modal = { ...this.conflictModal };
            this.conflictModal.open = false;

            const line = this.findLineById(modal.lineId);
            if (!line) {
                return;
            }

            if (modal.mode === 'create') {
                await this.submitCreateBeatPlacement(
                    line,
                    modal.chordId,
                    modal.incomingChordName,
                    modal.startBeat,
                    true,
                    modal.conflictPlacementId
                );
            } else {
                await this.submitMoveBeatPlacement(
                    line,
                    modal.placementId,
                    modal.incomingChordName,
                    modal.startBeat,
                    true,
                    modal.conflictPlacementId
                );
            }
        },

        cancelConflictModal() {
            this.conflictModal.open = false;
        },

        async releaseBeat(line, placement) {
            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/placements/${placement.id}/release`,
                'POST',
                { coordinate: 'start_beat' }
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal melepas beat chord.'));
                return;
            }

            placement.start_beat = null;
            this.showSuccess(`Beat chord ${placement.chord_name} dilepas tanpa mengubah posisi lirik.`);
        },

        async deletePlacement(line, placement) {
            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/placements/${placement.id}`,
                'DELETE'
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menghapus chord.'));
                return;
            }

            line.placements = line.placements.filter((item) => Number(item.id) !== Number(placement.id));
            this.showSuccess('Chord dihapus dari kedua editor.');
        },
    });
}

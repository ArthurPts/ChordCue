import { createSharedEditorState, mergeEditorMixins } from './editor-shared.js';

/**
 * Komponen Alpine.js untuk halaman Simple Editor (/songs/{song}/editor/simple).
 * Menggunakan state panel atas bersama dan menangani CRUD section & baris lirik
 * serta drag-and-drop chord per karakter.
 */
export function simpleEditor(initialConfig) {
    const shared = createSharedEditorState(initialConfig);

    return mergeEditorMixins(shared, {
        // Section & Line inline editors
        newSectionName: '',
        showNewSectionForm: false,
        editingSectionId: null,
        editingSectionName: '',

        addingLineSectionId: null,
        newLineContent: '',
        editingLineId: null,
        editingLineContent: '',

        // Drag and drop state
        dragPayload: null,
        dragOverTarget: null,

        // Conflict 409 modal state
        conflictModal: {
            open: false,
            mode: 'create',
            lineId: null,
            chordId: null,
            placementId: null,
            position: null,
            incomingChordName: '',
            existingChordName: '',
        },

        // --- Section CRUD ---
        async addSection() {
            const name = this.newSectionName.trim();
            if (!name) {
                this.showError('Nama section tidak boleh kosong.');
                return;
            }

            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/songs/${this.songId}/sections`,
                'POST',
                { name }
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menambah section.'));
                return;
            }

            this.sections.push(data.section);
            this.newSectionName = '';
            this.showNewSectionForm = false;
            this.showSuccess('Section berhasil ditambahkan.');
        },

        startEditSection(section) {
            this.editingSectionId = section.id;
            this.editingSectionName = section.name;
        },

        cancelEditSection() {
            this.editingSectionId = null;
            this.editingSectionName = '';
        },

        async saveSectionName(section) {
            const name = this.editingSectionName.trim();
            if (!name) {
                this.showError('Nama section tidak boleh kosong.');
                return;
            }

            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/sections/${section.id}`,
                'PATCH',
                { name }
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal memperbarui nama section.'));
                return;
            }

            section.name = data.section.name;
            this.cancelEditSection();
            this.showSuccess('Nama section diperbarui.');
        },

        async deleteSection(section) {
            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/sections/${section.id}`,
                'DELETE'
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menghapus section.'));
                return;
            }

            this.sections = this.sections.filter((item) => item.id !== section.id);
            this.showSuccess('Section berhasil dihapus.');
        },

        // --- Lyric Line CRUD ---
        openAddLine(sectionId) {
            this.addingLineSectionId = sectionId;
            this.newLineContent = '';
        },

        cancelAddLine() {
            this.addingLineSectionId = null;
            this.newLineContent = '';
        },

        async addLine(section) {
            const content = this.newLineContent;
            if (!content.trim()) {
                this.showError('Teks baris lirik tidak boleh kosong.');
                return;
            }

            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/sections/${section.id}/lines`,
                'POST',
                { content }
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menambah baris lirik.'));
                return;
            }

            section.lines.push(data.line);
            this.cancelAddLine();
            this.showSuccess('Baris lirik berhasil ditambahkan.');
        },

        startEditLine(line) {
            this.editingLineId = line.id;
            this.editingLineContent = line.content;
        },

        cancelEditLine() {
            this.editingLineId = null;
            this.editingLineContent = '';
        },

        async saveLineContent(line) {
            const content = this.editingLineContent;
            if (!content.trim()) {
                this.showError('Teks baris lirik tidak boleh kosong.');
                return;
            }

            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/lines/${line.id}`,
                'PATCH',
                { content }
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal memperbarui baris lirik.'));
                return;
            }

            line.content = data.line.content;
            line.placements = data.line.placements || [];
            this.cancelEditLine();
            this.showSuccess('Baris lirik berhasil diperbarui.');
        },

        async deleteLine(section, line) {
            this.clearFeedback();
            const { ok, data } = await this.requestJson(
                `/lines/${line.id}`,
                'DELETE'
            );

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menghapus baris lirik.'));
                return;
            }

            section.lines = section.lines.filter((item) => item.id !== line.id);
            this.showSuccess('Baris lirik berhasil dihapus.');
        },

        // --- Character Helpers & Drag-and-Drop Placements ---
        getChars(line) {
            return Array.from(line.content || '');
        },

        getPlacementAt(line, charIndex) {
            if (!line || !Array.isArray(line.placements)) {
                return null;
            }
            for (const placement of line.placements) {
                if (placement.position === charIndex) {
                    return placement;
                }
            }
            return null;
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

        startDragPlacedChord(event, placement) {
            this.dragPayload = {
                type: 'placement',
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

        endDrag() {
            this.dragPayload = null;
            this.dragOverTarget = null;
        },

        canDropOnLine(lineId) {
            if (!this.dragPayload) {
                return false;
            }
            if (this.dragPayload.type === 'library') {
                return true;
            }
            return Number(this.dragPayload.lineId) === Number(lineId);
        },

        onSlotDragOver(event, lineId, charIndex) {
            if (!this.canDropOnLine(lineId)) {
                return;
            }
            event.preventDefault();
            this.dragOverTarget = `${lineId}:${charIndex}`;
        },

        onSlotDragLeave(lineId, charIndex) {
            if (this.dragOverTarget === `${lineId}:${charIndex}`) {
                this.dragOverTarget = null;
            }
        },

        isSlotHighlighted(lineId, charIndex) {
            return this.dragOverTarget === `${lineId}:${charIndex}`;
        },

        async onSlotDrop(event, line, charIndex) {
            event.preventDefault();
            this.dragOverTarget = null;

            if (!this.dragPayload || !this.canDropOnLine(line.id)) {
                this.dragPayload = null;
                return;
            }

            const payload = { ...this.dragPayload };
            const safePosition = Math.max(0, Number(charIndex));
            this.dragPayload = null;
            this.clearFeedback();

            if (payload.type === 'library') {
                await this.submitCreatePlacement(line, payload.chordId, payload.chordName, safePosition, false);
            } else if (payload.type === 'placement') {
                await this.submitMovePlacement(line, payload.placementId, payload.chordName, safePosition, false);
            }
        },

        async submitCreatePlacement(line, chordId, chordName, position, force = false) {
            const { status, ok, data } = await this.requestJson(
                `/lines/${line.id}/placements`,
                'POST',
                { chord_id: chordId, position, force }
            );

            if (status === 409) {
                this.conflictModal = {
                    open: true,
                    mode: 'create',
                    lineId: line.id,
                    chordId,
                    placementId: null,
                    position,
                    incomingChordName: chordName,
                    existingChordName: data.conflict?.chord_name || 'chord lain',
                };
                return;
            }

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal menempatkan chord.'));
                return;
            }

            if (force) {
                line.placements = line.placements.filter((item) => item.position !== position);
            }
            line.placements.push(data.placement);
            this.showSuccess(`Chord ${data.placement.chord_name} ditempatkan.`);
        },

        async submitMovePlacement(line, placementId, chordName, position, force = false) {
            const existing = line.placements.find((item) => Number(item.id) === Number(placementId));
            if (existing && existing.position === position) {
                return;
            }

            const { status, ok, data } = await this.requestJson(
                `/placements/${placementId}`,
                'PATCH',
                { position, force }
            );

            if (status === 409) {
                this.conflictModal = {
                    open: true,
                    mode: 'move',
                    lineId: line.id,
                    chordId: existing ? existing.chord_id : null,
                    placementId,
                    position,
                    incomingChordName: chordName,
                    existingChordName: data.conflict?.chord_name || 'chord lain',
                };
                return;
            }

            if (!ok) {
                this.showError(this.firstValidationError(data, 'Gagal memindahkan chord.'));
                return;
            }

            if (force) {
                line.placements = line.placements.filter(
                    (item) => Number(item.id) !== Number(placementId) && item.position !== position
                );
                line.placements.push(data.placement);
            } else if (existing) {
                existing.position = data.placement.position;
            }

            this.showSuccess(`Posisi chord ${data.placement.chord_name} dipindahkan.`);
        },

        async confirmOverwriteConflict() {
            const modal = { ...this.conflictModal };
            this.conflictModal.open = false;

            const line = this.findLineById(modal.lineId);
            if (!line) {
                return;
            }

            if (modal.mode === 'create') {
                await this.submitCreatePlacement(line, modal.chordId, modal.incomingChordName, modal.position, true);
            } else {
                await this.submitMovePlacement(line, modal.placementId, modal.incomingChordName, modal.position, true);
            }
        },

        cancelConflictModal() {
            this.conflictModal.open = false;
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
            this.showSuccess('Chord dihapus dari baris lirik.');
        },
    });
}

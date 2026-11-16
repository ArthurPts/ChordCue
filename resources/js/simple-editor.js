/**
 * Komponen Alpine.js untuk halaman Simple Editor (/songs/{song}/editor/simple).
 * Mengelola UI metadata lagu, upload/hapus backing track, offset nudge bar inline,
 * CRUD section & baris lirik, drag-and-drop chord per karakter, dan panel chord belum digunakan.
 */
export function simpleEditor(initialConfig) {
    const initialOffset = Number(initialConfig.song?.backing_track_offset ?? 0);

    return {
        songId: initialConfig.song.id,
        chords: initialConfig.chords || [],
        categories: initialConfig.categories || [],
        sections: initialConfig.sections || [],

        // Metadata form state
        metaForm: {
            title: initialConfig.song.title || '',
            artist: initialConfig.song.artist || '',
            default_key: initialConfig.song.default_key || '',
            bpm: Number(initialConfig.song.bpm ?? 80),
            time_signature: initialConfig.song.time_signature || '4/4',
            visibility: initialConfig.song.visibility || 'public',
            category_ids: (initialConfig.song.category_ids || []).map((id) => Number(id)),
        },
        showCategories: false,
        isSavingMeta: false,
        metaMessage: '',
        metaError: '',

        // Backing track state
        backingTrackPath: initialConfig.song.backing_track_path || null,
        backingTrackUrl: initialConfig.song.backing_track_url || null,
        selectedAudioName: '',
        isUploadingAudio: false,
        audioMessage: '',
        audioError: '',
        audioIsPlaying: false,
        audioIsMuted: false,
        audioCurrentTime: 0,
        audioDuration: 0,

        // Offset state
        savedInitialOffset: Number.isFinite(initialOffset) ? Math.max(0, initialOffset) : 0,
        offsetInput: (Number.isFinite(initialOffset) ? Math.max(0, initialOffset) : 0).toFixed(3),
        offsetStatus: '',
        offsetError: '',
        offsetDebounceTimer: null,

        // Pustaka chord search
        chordSearch: '',

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

        // General toast/feedback
        editorMessage: '',
        editorError: '',

        get csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        },

        get filteredChords() {
            const keyword = this.chordSearch.trim().toLowerCase();
            if (!keyword) {
                return this.chords;
            }
            const results = [];
            for (const chord of this.chords) {
                if (chord.name.toLowerCase().includes(keyword)) {
                    results.push(chord);
                }
            }
            return results;
        },

        /**
         * Daftar chord yang baru ada di salah satu editor saja:
         * (position terisi && start_beat kosong) ATAU (position kosong && start_beat terisi).
         */
        get unusedPlacements() {
            const items = [];
            for (const section of this.sections) {
                for (const line of section.lines) {
                    for (const placement of line.placements) {
                        const hasPosition = placement.position !== null && placement.position !== undefined;
                        const hasBeat = placement.start_beat !== null && placement.start_beat !== undefined;
                        if ((hasPosition && !hasBeat) || (!hasPosition && hasBeat)) {
                            items.push(placement);
                        }
                    }
                }
            }
            return items;
        },

        clearFeedback() {
            this.editorMessage = '';
            this.editorError = '';
        },

        showSuccess(message) {
            this.editorError = '';
            this.editorMessage = message;
        },

        showError(message) {
            this.editorMessage = '';
            this.editorError = message;
        },

        async requestJson(url, method, body = null, isFormData = false) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            };

            const options = {
                method,
                headers,
                credentials: 'same-origin',
            };

            if (body !== null) {
                if (isFormData) {
                    options.body = body;
                } else {
                    headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }
            }

            const response = await fetch(url, options);
            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                data = {};
            }

            return { status: response.status, ok: response.ok, data };
        },

        firstValidationError(data, fallback) {
            if (data && data.errors) {
                const keys = Object.keys(data.errors);
                if (keys.length > 0 && Array.isArray(data.errors[keys[0]]) && data.errors[keys[0]].length > 0) {
                    return data.errors[keys[0]][0];
                }
            }
            return (data && data.message) ? data.message : fallback;
        },

        hasCategory(categoryId) {
            return this.metaForm.category_ids.includes(Number(categoryId));
        },

        toggleCategory(categoryId) {
            const id = Number(categoryId);
            if (this.metaForm.category_ids.includes(id)) {
                this.metaForm.category_ids = this.metaForm.category_ids.filter((item) => item !== id);
            } else {
                this.metaForm.category_ids = [...this.metaForm.category_ids, id];
            }
        },

        selectedCategories() {
            return this.categories.filter((cat) => this.metaForm.category_ids.includes(Number(cat.id)));
        },

        onBackingTrackSelected(fileInput) {
            this.audioError = '';
            this.audioMessage = '';
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                this.selectedAudioName = fileInput.files[0].name;
            } else {
                this.selectedAudioName = '';
            }
        },

        // --- Metadata Lagu + Kategori + Upload Audio MP3 Gabungan ---
        async saveMetadata() {
            this.isSavingMeta = true;
            this.metaMessage = '';
            this.metaError = '';
            this.audioMessage = '';
            this.audioError = '';

            try {
                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('title', this.metaForm.title);
                formData.append('artist', this.metaForm.artist);
                formData.append('default_key', this.metaForm.default_key || '');
                formData.append('bpm', String(Number(this.metaForm.bpm)));
                formData.append('time_signature', this.metaForm.time_signature);
                formData.append('visibility', this.metaForm.visibility);
                formData.append('sync_categories', '1');

                for (const catId of this.metaForm.category_ids) {
                    formData.append('category_ids[]', String(catId));
                }

                const fileInput = this.$refs.backingTrackInput;
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    formData.append('backing_track', fileInput.files[0]);
                }

                const { ok, data } = await this.requestJson(
                    `/songs/${this.songId}`,
                    'POST',
                    formData,
                    true
                );

                if (!ok) {
                    this.metaError = this.firstValidationError(data, 'Gagal menyimpan perubahan lagu.');
                    return;
                }

                if (data.song) {
                    this.metaForm.title = data.song.title;
                    this.metaForm.artist = data.song.artist;
                    this.metaForm.default_key = data.song.default_key || '';
                    this.metaForm.bpm = Number(data.song.bpm);
                    this.metaForm.time_signature = data.song.time_signature;
                    this.metaForm.visibility = data.song.visibility;
                    if (Array.isArray(data.song.category_ids)) {
                        this.metaForm.category_ids = data.song.category_ids.map((id) => Number(id));
                    }
                    this.backingTrackPath = data.song.backing_track_path || null;
                    this.backingTrackUrl = data.song.backing_track_url || null;
                    if (fileInput && fileInput.files && fileInput.files.length > 0) {
                        this.resetPreviewAudio();
                    }
                    if (data.song.backing_track_offset !== undefined && data.song.backing_track_offset !== null) {
                        this.offsetInput = Math.max(0, Number(data.song.backing_track_offset)).toFixed(3);
                    }
                }

                if (fileInput) {
                    fileInput.value = '';
                }
                this.selectedAudioName = '';
                this.metaMessage = data.message || 'Perubahan lagu berhasil disimpan.';
            } catch (error) {
                this.metaError = 'Terjadi gangguan jaringan saat menyimpan perubahan.';
            } finally {
                this.isSavingMeta = false;
            }
        },

        // --- Pemutar Pratinjau Audio Backing Track (Tema Gelap Kustom) ---
        resetPreviewAudio() {
            const el = this.$refs.backingAudioEl;
            if (el) {
                el.pause();
                el.currentTime = 0;
            }
            this.audioIsPlaying = false;
            this.audioCurrentTime = 0;
            this.audioDuration = 0;
        },

        togglePreviewAudio() {
            const el = this.$refs.backingAudioEl;
            if (!el || !this.backingTrackUrl) return;
            if (el.paused) {
                el.play();
                this.audioIsPlaying = true;
            } else {
                el.pause();
                this.audioIsPlaying = false;
            }
        },

        toggleMutePreviewAudio() {
            const el = this.$refs.backingAudioEl;
            this.audioIsMuted = !this.audioIsMuted;
            if (el) {
                el.muted = this.audioIsMuted;
            }
        },

        seekPreviewAudio(value) {
            const el = this.$refs.backingAudioEl;
            const target = Math.max(0, Number(value) || 0);
            this.audioCurrentTime = target;
            if (el) {
                el.currentTime = target;
            }
        },

        onAudioMetadataLoaded(event) {
            const el = event.target;
            this.audioDuration = Number.isFinite(el?.duration) ? el.duration : 0;
            this.audioCurrentTime = 0;
            el.muted = this.audioIsMuted;
        },

        onAudioTimeUpdate(event) {
            const el = event.target;
            this.audioCurrentTime = Number.isFinite(el?.currentTime) ? el.currentTime : 0;
            if (Number.isFinite(el?.duration) && el.duration > 0) {
                this.audioDuration = el.duration;
            }
        },

        onAudioEnded() {
            this.audioIsPlaying = false;
            this.audioCurrentTime = 0;
        },

        formatAudioTime(seconds) {
            const safe = Math.max(0, Math.floor(Number(seconds) || 0));
            const mins = Math.floor(safe / 60);
            const secs = safe % 60;
            return `${mins}:${String(secs).padStart(2, '0')}`;
        },

        // --- Backing Track Hapus ---
        async deleteBackingTrack() {
            this.isUploadingAudio = true;
            this.audioMessage = '';
            this.audioError = '';

            try {
                const { ok, data } = await this.requestJson(
                    `/songs/${this.songId}/backing-track`,
                    'DELETE'
                );

                if (!ok) {
                    this.audioError = this.firstValidationError(data, 'Gagal menghapus backing track.');
                    return;
                }

                this.resetPreviewAudio();
                this.backingTrackPath = null;
                this.backingTrackUrl = null;
                this.offsetInput = '0.000';
                this.savedInitialOffset = 0;
                this.audioMessage = data.message || 'Backing track berhasil dihapus.';
            } catch (error) {
                this.audioError = 'Terjadi gangguan saat menghapus backing track.';
            } finally {
                this.isUploadingAudio = false;
            }
        },

        // --- Offset Nudge Controls (Clamp >= 0) ---
        nudgeOffset(deltaSeconds) {
            const current = Math.max(0, parseFloat(this.offsetInput) || 0);
            const next = Math.max(0, Math.round((current + deltaSeconds) * 1000) / 1000);
            this.offsetInput = next.toFixed(3);
            this.saveOffset(next);
        },

        resetOffset() {
            const base = Math.max(0, this.savedInitialOffset);
            this.offsetInput = base.toFixed(3);
            this.saveOffset(base);
        },

        onOffsetInputTyped() {
            this.offsetStatus = '';
            this.offsetError = '';

            if (this.offsetDebounceTimer) {
                clearTimeout(this.offsetDebounceTimer);
            }

            const raw = String(this.offsetInput).trim();
            if (/^\d+(\.\d{1,3})?$/.test(raw)) {
                const numeric = Math.max(0, parseFloat(raw));
                this.offsetDebounceTimer = setTimeout(() => {
                    this.saveOffset(numeric);
                }, 400);
            }
        },

        commitOffsetInput() {
            if (this.offsetDebounceTimer) {
                clearTimeout(this.offsetDebounceTimer);
                this.offsetDebounceTimer = null;
            }

            const parsed = parseFloat(this.offsetInput);
            const clamped = Number.isFinite(parsed) ? Math.max(0, Math.round(parsed * 1000) / 1000) : 0;
            this.offsetInput = clamped.toFixed(3);
            this.saveOffset(clamped);
        },

        async saveOffset(numericSeconds) {
            this.offsetStatus = 'Menyimpan...';
            this.offsetError = '';

            try {
                const payloadValue = Math.max(0, Math.round(Number(numericSeconds) * 1000) / 1000);
                const { ok, data } = await this.requestJson(
                    `/songs/${this.songId}/backing-track/offset`,
                    'PATCH',
                    { offset: payloadValue }
                );

                if (!ok) {
                    this.offsetStatus = '';
                    this.offsetError = this.firstValidationError(data, 'Gagal menyimpan offset.');
                    return;
                }

                const persisted = Math.max(0, Number(data.backing_track_offset ?? payloadValue));
                this.offsetInput = persisted.toFixed(3);
                this.offsetStatus = 'Tersimpan';
            } catch (error) {
                this.offsetStatus = '';
                this.offsetError = 'Gagal menyimpan offset ke server.';
            }
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
            this.dragPayload = null;
            this.clearFeedback();

            if (payload.type === 'library') {
                await this.submitCreatePlacement(line, payload.chordId, payload.chordName, charIndex, false);
            } else if (payload.type === 'placement') {
                await this.submitMovePlacement(line, payload.placementId, payload.chordName, charIndex, false);
            }
        },

        findLineById(lineId) {
            for (const section of this.sections) {
                for (const line of section.lines) {
                    if (Number(line.id) === Number(lineId)) {
                        return line;
                    }
                }
            }
            return null;
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
    };
}

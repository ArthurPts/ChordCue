/**
 * State dan helper bersama untuk Simple Editor dan Beat Editor ChordCue:
 * mengelola metadata lagu, kategori, upload/hapus/pratinjau backing track MP3,
 * bilah nudge offset (>= 0.000), pencarian Pustaka Chord, dan daftar chord belum digunakan.
 */

/**
 * Menggabungkan objek dasar dan objek ekstensi sambil mempertahankan getter (`get ...()`).
 *
 * @param {Object} base
 * @param {Object} extension
 * @returns {Object}
 */
export function mergeEditorMixins(base, extension) {
    const merged = {};
    Object.defineProperties(merged, Object.getOwnPropertyDescriptors(base));
    Object.defineProperties(merged, Object.getOwnPropertyDescriptors(extension));
    return merged;
}

/**
 * Membangun state serta method bersama panel atas dan Pustaka Chord editor lagu.
 *
 * @param {Object} initialConfig
 * @returns {Object}
 */
export function createSharedEditorState(initialConfig) {
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
         * Daftar chord yang belum memiliki koordinat lengkap
         * (`position` atau `start_beat` masih kosong).
         */
        get unusedPlacements() {
            const items = [];
            for (const section of this.sections) {
                for (const line of section.lines) {
                    for (const placement of line.placements) {
                        const hasPosition = placement.position !== null && placement.position !== undefined;
                        const hasBeat = placement.start_beat !== null && placement.start_beat !== undefined;
                        if (!hasPosition || !hasBeat) {
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
                formData.append('artist', this.metaForm.artist || '');
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
                    this.metaForm.artist = data.song.artist || '';
                    this.metaForm.default_key = data.song.default_key || '';
                    this.metaForm.bpm = Number(data.song.bpm);
                    this.metaForm.time_signature = data.song.time_signature;
                    this.metaForm.visibility = data.song.visibility;
                    if (Array.isArray(data.song.category_ids)) {
                        this.metaForm.category_ids = data.song.category_ids.map((id) => Number(id));
                    }
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
    };
}

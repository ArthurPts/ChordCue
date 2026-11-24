<div
    x-show="showCreateModal"
    x-cloak
    class="fixed inset-0 z-modal overflow-y-auto"
    aria-labelledby="create-chord-title"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="showCreateModal = false"
>
    <!-- Backdrop Overlay -->
    <div
        x-show="showCreateModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-overlay backdrop-blur-sm transition-opacity"
        @click="showCreateModal = false"
    ></div>

    <!-- Modal Centering Wrapper -->
    <div class="flex min-h-full items-center justify-center p-16 sm:p-24 text-center">
        <div
            x-show="showCreateModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-[520px] transform rounded-lg bg-surface-1 border border-subtle p-24 sm:p-28 text-left shadow-2xl transition-all"
            x-data="{
                fileName: '',
                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.fileName = file.name;
                    } else {
                        this.fileName = '';
                    }
                },
                clearFile() {
                    this.fileName = '';
                    this.$refs.fileInput.value = '';
                }
            }"
        >
            <!-- Modal Header -->
            <div>
                <h2 id="create-chord-title" class="text-xl sm:text-2xl font-extrabold text-ink tracking-tight">
                    Buat Chord Baru
                </h2>
                <p class="text-xs sm:text-sm text-ink-secondary mt-1">
                    Masukkan informasi dasar chord.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('chords.store') }}" enctype="multipart/form-data" class="mt-20 space-y-16">
                @csrf

                <!-- Chord Name Input -->
                <div class="space-y-4">
                    <label for="create_chord_name" class="block text-xs font-semibold text-ink-secondary">
                        Chord
                    </label>
                    <input
                        id="create_chord_name"
                        type="text"
                        name="name"
                        required
                        placeholder="contoh: G#m7"
                        class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors font-mono"
                    >
                </div>

                <!-- Cara Baca Input -->
                <div class="space-y-4">
                    <label for="create_chord_pronunciation" class="block text-xs font-semibold text-ink-secondary">
                        Cara Baca (contoh : C Sharp Minor)
                    </label>
                    <input
                        id="create_chord_pronunciation"
                        type="text"
                        name="pronunciation"
                        required
                        placeholder="cara baca"
                        class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors"
                    >
                </div>

                <!-- Upload Diagram (Opsional) -->
                <div class="space-y-4">
                    <label class="block text-xs font-semibold text-ink-secondary">
                        Upload Diagram (Opsional)
                    </label>

                    <div
                        @click="$refs.fileInput.click()"
                        class="group relative flex flex-col items-center justify-center border border-dashed border-subtle hover:border-accent bg-surface-2/50 hover:bg-surface-2 rounded-md p-20 text-center cursor-pointer transition-all"
                    >
                        <input
                            x-ref="fileInput"
                            type="file"
                            name="diagram"
                            accept=".png,.jpg,.jpeg,.svg"
                            class="hidden"
                            @change="handleFileSelect($event)"
                        >

                        <div class="w-[40px] h-[40px] rounded-sm bg-surface-3 border border-subtle flex items-center justify-center text-accent mb-8 group-hover:scale-105 transition-transform">
                            <x-icon name="upload" class="w-[20px] h-[20px] text-accent" />
                        </div>

                        <template x-if="!fileName">
                            <div>
                                <span class="text-xs sm:text-sm font-semibold text-accent hover:underline">Pilih file diagram</span>
                                <p class="text-[11px] text-ink-secondary mt-2">Format didukung: JPG, PNG, SVG</p>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center gap-8 px-12 py-6 bg-surface-3 border border-subtle rounded-sm text-ink text-xs max-w-full">
                                <span class="truncate max-w-[240px] font-mono text-[11px]" x-text="fileName"></span>
                                <button
                                    type="button"
                                    @click.stop="clearFile()"
                                    class="text-ink-secondary hover:text-error p-2 transition-colors cursor-pointer"
                                    title="Hapus file"
                                >
                                    <x-icon name="x" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Divider & Buttons -->
                <div class="pt-20 border-t border-subtle mt-24 flex items-center justify-end gap-12">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="h-[40px] px-24 rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] text-xs sm:text-sm font-bold transition-all cursor-pointer shadow-sm"
                    >
                        Buat Chord
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div
    x-show="showCreateModal"
    x-cloak
    class="fixed inset-0 z-modal overflow-y-auto"
    aria-labelledby="create-chord-title"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="showCreateModal = false"
>
    <!-- Backdrop Overlay (Gelap proporsional dengan backdrop blur) -->
    <div
        x-show="showCreateModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 backdrop-blur-sm transition-opacity"
        style="background-color: rgba(5, 8, 17, 0.70);"
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
            class="relative w-full max-w-[560px] transform rounded-2xl bg-surface-1 border border-subtle p-28 sm:p-36 text-left shadow-2xl transition-all"
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
                <h2 id="create-chord-title" class="text-2xl sm:text-3xl font-semibold text-ink tracking-tight">
                    Buat Chord Baru
                </h2>
                <p class="text-sm text-ink-secondary mt-6">
                    Masukkan informasi dasar chord.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('chords.store') }}" enctype="multipart/form-data" class="mt-28 space-y-20">
                @csrf

                <!-- Chord Name Input -->
                <div class="space-y-6">
                    <label for="create_chord_name" class="block text-xs font-medium text-ink-secondary">
                        Chord
                    </label>
                    <input
                        id="create_chord_name"
                        type="text"
                        name="name"
                        required
                        placeholder="nama chord"
                        class="w-full h-[44px] px-14 bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                    >
                </div>

                <!-- Cara Baca Input -->
                <div class="space-y-6">
                    <label for="create_chord_pronunciation" class="block text-xs font-medium text-ink-secondary">
                        Cara Baca (contoh : C Sharp Minor)
                    </label>
                    <input
                        id="create_chord_pronunciation"
                        type="text"
                        name="pronunciation"
                        required
                        placeholder="cara baca"
                        class="w-full h-[44px] px-14 bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                    >
                </div>

                <!-- Upload Diagram (Opsional) -->
                <div class="space-y-6">
                    <label class="block text-xs font-medium text-ink-secondary">
                        Upload Diagram (Opsional)
                    </label>

                    <div
                        @click="$refs.fileInput.click()"
                        class="group relative flex flex-col items-center justify-center border border-dashed border-[#2C384E] hover:border-accent/60 bg-[#161D2B]/80 hover:bg-[#1A2334] rounded-2xl p-24 text-center cursor-pointer transition-colors"
                    >
                        <input
                            x-ref="fileInput"
                            type="file"
                            name="diagram"
                            accept=".png,.jpg,.jpeg,.svg"
                            class="hidden"
                            @change="handleFileSelect($event)"
                        >

                        <div class="w-10 h-10 rounded-xl bg-surface-2 flex items-center justify-center text-accent mb-10 group-hover:scale-110 transition-transform">
                            <x-icon name="upload" class="w-6 h-6 text-accent" />
                        </div>

                        <template x-if="!fileName">
                            <div>
                                <span class="text-sm font-medium text-accent">Upload</span>
                                <p class="text-xs text-ink-secondary mt-4">Pilih file JPG, PNG</p>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center gap-8 px-12 py-6 bg-surface-2 rounded-lg text-ink text-xs max-w-full">
                                <span class="truncate max-w-[240px]" x-text="fileName"></span>
                                <button
                                    type="button"
                                    @click.stop="clearFile()"
                                    class="text-ink-secondary hover:text-danger p-2"
                                    title="Hapus file"
                                >
                                    <x-icon name="x" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Divider & Buttons -->
                <div class="pt-20 border-t border-subtle flex items-center justify-end gap-12">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="px-20 h-[40px] rounded-xl text-sm font-medium border border-[#E06C75] text-[#E06C75] hover:bg-[#E06C75]/10 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-20 h-[40px] rounded-xl text-sm font-medium bg-[#5B8DF6] hover:bg-[#4C7EE7] text-white shadow-button transition-colors flex items-center justify-center"
                    >
                        Buat Chord
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@props(['artists' => []])

<div
    x-show="showCreateModal"
    x-cloak
    class="fixed inset-0 z-modal overflow-y-auto"
    aria-labelledby="modal-title"
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

    <!-- Modal Content Centering Wrapper -->
    <div class="flex min-h-full items-center justify-center p-16 sm:p-24 text-center">
        <div
            x-show="showCreateModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-[540px] transform rounded-lg bg-surface-1 border border-subtle p-24 sm:p-28 text-left shadow-2xl transition-all"
            x-data="{ visibility: 'public' }"
        >
            <!-- Modal Header -->
            <div>
                <h2 id="modal-title" class="text-xl sm:text-2xl font-extrabold text-ink tracking-tight">
                    Buat Lagu Baru
                </h2>
                <p class="text-xs sm:text-sm text-ink-secondary mt-1">
                    Masukkan informasi dasar sebelum menyusun chord sheet.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('songs.store') }}" class="mt-20 space-y-16">
                @csrf

                <!-- Judul Lagu -->
                <div class="space-y-4">
                    <label for="modal_song_title" class="block text-xs font-semibold text-ink-secondary">
                        Judul Lagu
                    </label>
                    <input
                        id="modal_song_title"
                        type="text"
                        name="title"
                        required
                        autofocus
                        placeholder="Masukkan Judul Lagu Anda"
                        class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors"
                    >
                </div>

                <!-- Artist -->
                <div class="space-y-4">
                    <label for="modal_song_artist" class="block text-xs font-semibold text-ink-secondary">
                        Artist
                    </label>
                    <div class="relative">
                        <input
                            id="modal_song_artist"
                            type="text"
                            name="artist"
                            list="modal-artists-list"
                            required
                            placeholder="Pilih Artist"
                            class="w-full h-[40px] px-14 pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors"
                        >
                        <datalist id="modal-artists-list">
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->name }}"></option>
                            @endforeach
                        </datalist>
                        <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <!-- Row: BPM & Time Signature -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-14">
                    <!-- BPM -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label for="modal_song_bpm" class="block text-xs font-semibold text-ink-secondary">
                                BPM
                            </label>
                            <span class="text-[11px] text-ink-tertiary font-mono">60 - 140</span>
                        </div>
                        <input
                            id="modal_song_bpm"
                            type="number"
                            name="bpm"
                            value="120"
                            min="60"
                            max="140"
                            required
                            placeholder="Masukkan BPM (60 - 140)"
                            class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors font-mono"
                        >
                    </div>

                    <!-- Time Signature -->
                    <div class="space-y-4">
                        <label for="modal_song_time_signature" class="block text-xs font-semibold text-ink-secondary">
                            Time Signature
                        </label>
                        <div class="relative">
                            <select
                                id="modal_song_time_signature"
                                name="time_signature"
                                required
                                class="w-full h-[40px] bg-surface-2 border border-subtle rounded-sm px-14 pr-[36px] text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors font-mono"
                            >
                                <option value="4/4" selected>4/4</option>
                                <option value="3/4">3/4</option>
                                <option value="2/4">2/4</option>
                                <option value="6/8">6/8</option>
                                <option value="12/8">12/8</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                                <x-icon name="chevron-down" class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visibilitas Option Cards -->
                <div class="space-y-6">
                    <label class="block text-xs font-semibold text-ink-secondary">
                        Visibilitas
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-12">
                        <!-- Option Public -->
                        <label
                            @click="visibility = 'public'"
                            :class="visibility === 'public'
                                ? 'border-accent bg-accent-soft-bg/35 text-ink ring-1 ring-accent'
                                : 'border-subtle bg-surface-2 text-ink-secondary hover:text-ink hover:border-subtle/80 hover:bg-surface-3'"
                            class="relative rounded-md border p-14 cursor-pointer transition-all flex flex-col justify-between select-none"
                        >
                            <input type="radio" name="visibility" value="public" x-model="visibility" class="sr-only">
                            <div class="flex items-start gap-12">
                                <div
                                    class="w-[18px] h-[18px] mt-2 rounded-full border flex items-center justify-center shrink-0 transition-all"
                                    :class="visibility === 'public' ? 'border-accent bg-accent' : 'border-subtle bg-surface-3'"
                                >
                                    <div
                                        class="w-[8px] h-[8px] rounded-full transition-all"
                                        :class="visibility === 'public' ? 'bg-[#0A0F1A]' : 'bg-transparent'"
                                    ></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-8">
                                        <span :class="visibility === 'public' ? 'text-accent' : 'text-ink-secondary'" class="transition-colors">
                                            <x-icon name="world" class="w-icon-control h-icon-control shrink-0" />
                                        </span>
                                        <span class="text-xs sm:text-sm font-bold text-ink">
                                            Publik
                                        </span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs mt-6 text-ink-secondary leading-relaxed">
                                        Lagu dapat dilihat oleh semua pengguna ChordCue.
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Option Private -->
                        <label
                            @click="visibility = 'private'"
                            :class="visibility === 'private'
                                ? 'border-accent bg-accent-soft-bg/35 text-ink ring-1 ring-accent'
                                : 'border-subtle bg-surface-2 text-ink-secondary hover:text-ink hover:border-subtle/80 hover:bg-surface-3'"
                            class="relative rounded-md border p-14 cursor-pointer transition-all flex flex-col justify-between select-none"
                        >
                            <input type="radio" name="visibility" value="private" x-model="visibility" class="sr-only">
                            <div class="flex items-start gap-12">
                                <div
                                    class="w-[18px] h-[18px] mt-2 rounded-full border flex items-center justify-center shrink-0 transition-all"
                                    :class="visibility === 'private' ? 'border-accent bg-accent' : 'border-subtle bg-surface-3'"
                                >
                                    <div
                                        class="w-[8px] h-[8px] rounded-full transition-all"
                                        :class="visibility === 'private' ? 'bg-[#0A0F1A]' : 'bg-transparent'"
                                    ></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-8">
                                        <span :class="visibility === 'private' ? 'text-accent' : 'text-ink-secondary'" class="transition-colors">
                                            <x-icon name="lock" class="w-icon-control h-icon-control shrink-0" />
                                        </span>
                                        <span class="text-xs sm:text-sm font-bold text-ink">
                                            Privat
                                        </span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs mt-6 text-ink-secondary leading-relaxed">
                                        Hanya kamu yang dapat melihat lagu ini.
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Modal Actions Footer (Divider + Buttons) -->
                <div class="border-t border-subtle mt-24 pt-20 flex items-center justify-end gap-12">
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
                        Buat Lagu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


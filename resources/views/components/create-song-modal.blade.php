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
            class="relative w-full max-w-[560px] transform rounded-2xl bg-surface-1 border border-subtle p-28 sm:p-36 text-left shadow-2xl transition-all"
            x-data="{ visibility: 'public' }"
        >
            <!-- Modal Header -->
            <div>
                <h2 id="modal-title" class="text-2xl sm:text-3xl font-semibold text-ink tracking-tight">
                    Buat Lagu Baru
                </h2>
                <p class="text-sm text-ink-secondary mt-6">
                    Masukkan informasi dasar sebelum menyusun chord sheet.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('songs.store') }}" class="mt-28 space-y-20">
                @csrf

                <!-- Judul Lagu -->
                <div class="space-y-6">
                    <label for="modal_song_title" class="block text-xs font-medium text-ink-secondary">
                        Judul Lagu
                    </label>
                    <input
                        id="modal_song_title"
                        type="text"
                        name="title"
                        required
                        autofocus
                        placeholder="Masukkan Judul Lagu Anda"
                        class="w-full h-[44px] px-14 bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                    >
                </div>

                <!-- Artist -->
                <div class="space-y-6">
                    <label for="modal_song_artist" class="block text-xs font-medium text-ink-secondary">
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
                            class="w-full h-[44px] px-14 pr-[36px] bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-16">
                    <!-- BPM -->
                    <div class="space-y-6">
                        <label for="modal_song_bpm" class="block text-xs font-medium text-ink-secondary">
                            BPM
                        </label>
                        <input
                            id="modal_song_bpm"
                            type="number"
                            name="bpm"
                            value="120"
                            min="40"
                            max="240"
                            required
                            placeholder="Masukan BPM (40 - 240)"
                            class="w-full h-[44px] px-14 bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                        >
                    </div>

                    <!-- Time Signature -->
                    <div class="space-y-6">
                        <label for="modal_song_time_signature" class="block text-xs font-medium text-ink-secondary">
                            Time Signature
                        </label>
                        <div class="relative">
                            <select
                                id="modal_song_time_signature"
                                name="time_signature"
                                required
                                class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[36px] text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
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
                <div class="space-y-8">
                    <label class="block text-xs font-medium text-ink-secondary">
                        Visibilitas
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-16">
                        <!-- Option Public -->
                        <label
                            @click="visibility = 'public'"
                            :class="visibility === 'public' ? 'bg-accent text-white shadow-sm ring-1 ring-accent' : 'bg-surface-2 border border-subtle text-ink-secondary hover:text-ink hover:bg-surface-3'"
                            class="relative rounded-xl p-16 cursor-pointer transition-all flex flex-col justify-between select-none"
                        >
                            <input type="radio" name="visibility" value="public" x-model="visibility" class="sr-only">
                            <div class="flex items-center gap-10">
                                <!-- Radio Dot Indicator -->
                                <div
                                    class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                    :class="visibility === 'public' ? 'border-white bg-white' : 'border-ink-secondary bg-transparent'"
                                >
                                    <div class="w-2 h-2 rounded-full" :class="visibility === 'public' ? 'bg-accent' : 'bg-transparent'"></div>
                                </div>
                                <!-- Icon -->
                                <x-icon name="world" class="w-5 h-5 shrink-0" />
                                <span class="text-sm font-semibold" :class="visibility === 'public' ? 'text-white' : 'text-ink'">
                                    Public
                                </span>
                            </div>
                            <p class="text-xs mt-8 pl-26 leading-relaxed" :class="visibility === 'public' ? 'text-white/85' : 'text-ink-secondary'">
                                Lagu dapat dilihat oleh semua pengguna ChordCue.
                            </p>
                        </label>

                        <!-- Option Private -->
                        <label
                            @click="visibility = 'private'"
                            :class="visibility === 'private' ? 'bg-accent text-white shadow-sm ring-1 ring-accent' : 'bg-surface-2 border border-subtle text-ink-secondary hover:text-ink hover:bg-surface-3'"
                            class="relative rounded-xl p-16 cursor-pointer transition-all flex flex-col justify-between select-none"
                        >
                            <input type="radio" name="visibility" value="private" x-model="visibility" class="sr-only">
                            <div class="flex items-center gap-10">
                                <!-- Radio Dot Indicator -->
                                <div
                                    class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                    :class="visibility === 'private' ? 'border-white bg-white' : 'border-ink-secondary bg-transparent'"
                                >
                                    <div class="w-2 h-2 rounded-full" :class="visibility === 'private' ? 'bg-accent' : 'bg-transparent'"></div>
                                </div>
                                <!-- Icon -->
                                <x-icon name="lock" class="w-5 h-5 shrink-0" />
                                <span class="text-sm font-semibold" :class="visibility === 'private' ? 'text-white' : 'text-ink'">
                                    Private
                                </span>
                            </div>
                            <p class="text-xs mt-8 pl-26 leading-relaxed" :class="visibility === 'private' ? 'text-white/85' : 'text-ink-secondary'">
                                Hanya kamu yang dapat melihat lagu ini.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Modal Actions Footer (Divider + Buttons) -->
                <div class="border-t border-subtle/70 mt-28 pt-20 flex items-center justify-end gap-12">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="h-[40px] px-20 rounded-xl bg-surface-2 hover:bg-surface-3 border border-[#E06D63]/60 text-ink text-sm font-medium transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="h-[40px] px-20 rounded-xl bg-accent hover:bg-accent-hover text-white text-sm font-medium transition-colors cursor-pointer shadow-sm"
                    >
                        Buat Lagu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


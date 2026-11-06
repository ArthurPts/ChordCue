<x-app-layout>
    <div x-data="{ showCreateModal: false }">
        @if (!($hasAnySongs ?? false))
            <!-- Empty State Khusus User yang Belum Memiliki Lagu -->
            <div class="min-h-[calc(100vh-180px)] flex flex-col items-center justify-center px-16 py-28">
                <!-- Information Card -->
                <div class="bg-surface-1 border border-subtle rounded-lg p-24 sm:p-32 max-w-[460px] w-full flex items-center gap-20 shadow-sm">
                    <div class="w-[48px] h-[48px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                        <x-icon name="music" class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-ink">
                            Anda Belum Memiliki Lagu Pribadi.
                        </h2>
                        <p class="text-xs sm:text-sm text-ink-secondary mt-2">
                            Ayo buat lagu pertamamu.
                        </p>
                    </div>
                </div>

                <!-- Buat Lagu Button (Trigger Modal) -->
                <div class="mt-20">
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="h-[42px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] font-bold text-sm inline-flex items-center gap-8 shadow-sm transition-all cursor-pointer"
                    >
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Buat Lagu</span>
                    </button>
                </div>
            </div>
        @else
            <div class="max-w-content mx-auto w-full px-16 sm:px-24 py-24 sm:py-32 pb-[88px]">
                <!-- Header Row (Title, Subtitle & Buat Lagu Button) -->
                <div class="flex items-center justify-between gap-12">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                            Lagu Saya
                        </h1>
                        <p class="text-xs sm:text-sm text-ink-secondary mt-1">
                            Kelola chord sheet yang kamu buat.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="h-[40px] px-16 rounded-sm bg-accent hover:bg-accent-hover text-[#080C14] font-bold text-xs sm:text-sm inline-flex items-center gap-6 transition-all shadow-sm cursor-pointer whitespace-nowrap shrink-0"
                    >
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Buat Lagu</span>
                    </button>
                </div>

                <!-- Session Status Flash Alert -->
                @if (session('status'))
                    <div class="mt-16 p-12 rounded-md bg-accent/15 border border-accent/30 text-accent text-xs sm:text-sm flex items-center gap-8">
                        <x-icon name="check" class="w-4 h-4 shrink-0" />
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mt-16 p-12 rounded-md bg-error-tint/80 border border-error/40 text-error text-xs sm:text-sm flex items-center gap-8">
                        <x-icon name="alert-circle" class="w-4 h-4 shrink-0" />
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <!-- Search and Visibility Filters Row -->
                <form method="GET" action="{{ route('my.songs') }}" class="mt-20 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-12">
                    <button type="submit" class="hidden" aria-hidden="true"></button>

                    <!-- Search Input Box -->
                    <div class="relative flex-1 sm:max-w-[320px]">
                        <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                            <x-icon name="search" class="w-4 h-4" />
                        </button>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari judul lagu atau artis..."
                            class="w-full h-[40px] pl-[38px] pr-[34px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors"
                        >
                        @if (request('q'))
                            <a href="{{ route('my.songs', ['visibility' => $visibility ?? 'public']) }}" class="absolute inset-y-0 right-0 pr-12 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                                <x-icon name="x" class="w-3.5 h-3.5" />
                            </a>
                        @endif
                    </div>

                    <!-- Visibility Segmented Control (Public & Private) -->
                    <div class="flex items-center gap-4 bg-surface-2 border border-subtle p-3 rounded-md self-start sm:self-auto">
                        @php
                            $isPublicActive = ($visibility ?? 'public') === 'public';
                            $isPrivateActive = ($visibility ?? 'public') === 'private';
                        @endphp
                        <button
                            type="submit"
                            name="visibility"
                            value="public"
                            class="h-[32px] px-14 rounded-sm font-semibold text-xs inline-flex items-center gap-6 transition-all cursor-pointer {{ $isPublicActive ? 'bg-accent text-[#080C14] shadow-sm' : 'text-ink-secondary hover:text-ink' }}"
                        >
                            <x-icon name="world" class="w-3.5 h-3.5" />
                            <span>Publik</span>
                        </button>

                        <button
                            type="submit"
                            name="visibility"
                            value="private"
                            class="h-[32px] px-14 rounded-sm font-semibold text-xs inline-flex items-center gap-6 transition-all cursor-pointer {{ $isPrivateActive ? 'bg-accent text-[#080C14] shadow-sm' : 'text-ink-secondary hover:text-ink' }}"
                        >
                            <x-icon name="lock" class="w-3.5 h-3.5" />
                            <span>Privat</span>
                        </button>
                    </div>
                </form>

                <!-- Song Cards Grid -->
                <div class="mt-20">
                    @if (isset($songs) && $songs->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-16 md:gap-20">
                            @foreach ($songs as $song)
                                <x-song-card :song="$song" :can-manage="true" />
                            @endforeach
                        </div>
                    @else
                        <div class="bg-surface-1 border border-subtle rounded-lg p-32 text-center max-w-[460px] mx-auto mt-12">
                            <x-icon name="music" class="w-10 h-10 text-ink-tertiary mx-auto mb-12" />
                            <h3 class="text-base font-bold text-ink">Tidak Ada Lagu Ditemukan</h3>
                            <p class="text-xs sm:text-sm text-ink-secondary mt-4">
                                Tidak ada lagu yang cocok dengan kriteria pencarian atau filter yang kamu pilih.
                            </p>
                            <div class="mt-16">
                                <a href="{{ route('my.songs') }}" class="h-[38px] px-18 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs font-semibold inline-flex items-center justify-center transition-colors">
                                    Atur Ulang Filter
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Pagination Section -->
                @if (isset($songs) && $songs->count() > 0)
                    <div class="mt-28 mb-12 flex justify-center">
                        <x-pagination :paginator="$songs" />
                    </div>
                @endif
            </div>
        @endif

        <!-- Reusable Modal Buat Lagu Baru -->
        <x-create-song-modal :artists="$artists ?? []" />
    </div>
</x-app-layout>

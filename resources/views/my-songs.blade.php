<x-app-layout>
    <div x-data="{ showCreateModal: false }">
        @if (!($hasAnySongs ?? false))
            <!-- Empty State Khusus User yang Belum Memiliki Lagu Sesuai Target Desain UI -->
            <div class="min-h-[calc(100vh-160px)] flex flex-col items-center justify-center px-16 py-36">
                <!-- Information Card -->
                <div class="bg-surface-1 border border-subtle rounded-2xl p-24 sm:p-28 max-w-[460px] w-full flex items-center gap-20">
                    <div class="w-[52px] h-[52px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-ink-secondary shrink-0">
                        <x-icon name="search" class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-medium text-ink">
                            Anda Belum Memiliki Lagu Pribadi.
                        </h2>
                        <p class="text-sm text-ink-secondary mt-4">
                            Ayo buat lagu pertamamu.
                        </p>
                    </div>
                </div>

                <!-- Buat Lagu Button (Trigger Modal) -->
                <div class="mt-24">
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="h-[44px] px-24 rounded-xl bg-accent hover:bg-accent-hover text-white font-medium text-sm inline-flex items-center gap-8 shadow-sm transition-colors cursor-pointer"
                    >
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Buat Lagu</span>
                    </button>
                </div>
            </div>
        @else
            <div class="max-w-content mx-auto w-full px-16 md:px-24 py-36">
                <!-- Header Row (Title, Subtitle & Buat Lagu Button) -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-16">
                    <div>
                        <h1 class="text-3xl font-semibold text-ink tracking-tight">
                            My Songs
                        </h1>
                        <p class="text-base text-ink-secondary mt-4">
                            Kelola chord sheet yang kamu buat.
                        </p>
                    </div>

                    <div>
                        <button
                            type="button"
                            @click="showCreateModal = true"
                            class="h-[42px] px-18 rounded-xl bg-accent hover:bg-accent-hover text-white font-medium text-sm inline-flex items-center gap-8 transition-colors self-start sm:self-auto cursor-pointer shadow-sm"
                        >
                            <x-icon name="plus" class="w-4 h-4" />
                            <span>Buat Lagu</span>
                        </button>
                    </div>
                </div>

                <!-- Session Status Flash Alert -->
                @if (session('status'))
                    <div class="mt-20 p-16 rounded-xl bg-accent/15 border border-accent/30 text-accent text-sm flex items-center gap-10">
                        <x-icon name="check" class="w-5 h-5 shrink-0" />
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mt-20 p-16 rounded-xl bg-[#DE6D62]/15 border border-[#DE6D62]/30 text-[#DE6D62] text-sm flex items-center gap-10">
                        <x-icon name="alert-circle" class="w-5 h-5 shrink-0" />
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <!-- Search and Visibility Filters Row -->
                <form method="GET" action="{{ route('my.songs') }}" class="mt-24 flex flex-col sm:flex-row items-stretch sm:items-center gap-12">
                    <button type="submit" class="hidden" aria-hidden="true"></button>

                    <!-- Search Input Box -->
                    <div class="relative flex-1 sm:max-w-[280px]">
                        <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                            <x-icon name="search" class="w-4 h-4" />
                        </button>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari judul lagu atau artis..."
                            class="w-full h-[44px] pl-[40px] pr-[36px] bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                        >
                        @if (request('q'))
                            <a href="{{ route('my.songs', ['visibility' => $visibility ?? 'public']) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                                <x-icon name="x" class="w-3.5 h-3.5" />
                            </a>
                        @endif
                    </div>

                    <!-- Visibility Filter Buttons (Public & Private) -->
                    <div class="flex items-center gap-8">
                        @php
                            $isPublicActive = ($visibility ?? 'public') === 'public';
                            $isPrivateActive = ($visibility ?? 'public') === 'private';
                        @endphp
                        <button
                            type="submit"
                            name="visibility"
                            value="public"
                            class="h-[44px] px-16 rounded-xl font-medium text-sm inline-flex items-center gap-8 transition-colors cursor-pointer {{ $isPublicActive ? 'bg-accent text-white shadow-sm' : 'bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink' }}"
                        >
                            <x-icon name="world" class="w-4 h-4" />
                            <span>Public</span>
                        </button>

                        <button
                            type="submit"
                            name="visibility"
                            value="private"
                            class="h-[44px] px-16 rounded-xl font-medium text-sm inline-flex items-center gap-8 transition-colors cursor-pointer {{ $isPrivateActive ? 'bg-accent text-white shadow-sm' : 'bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink' }}"
                        >
                            <x-icon name="lock" class="w-4 h-4" />
                            <span>Private</span>
                        </button>
                    </div>
                </form>

                <!-- Song Cards Grid -->
                <div class="mt-24">
                    @if (isset($songs) && $songs->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-24">
                            @foreach ($songs as $song)
                                <x-song-card :song="$song" :can-manage="true" />
                            @endforeach
                        </div>
                    @else
                        <div class="bg-surface-1 border border-subtle rounded-2xl p-40 text-center max-w-[480px] mx-auto">
                            <x-icon name="music" class="w-12 h-12 text-ink-tertiary mx-auto mb-16" />
                            <h3 class="text-lg font-medium text-ink">Tidak Ada Lagu Ditemukan</h3>
                            <p class="text-sm text-ink-secondary mt-6">
                                Tidak ada lagu yang cocok dengan kriteria pencarian atau filter yang dipilih.
                            </p>
                            <div class="mt-20">
                                <a href="{{ route('my.songs') }}" class="h-[40px] px-20 rounded-lg bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-sm font-medium inline-flex items-center justify-center transition-colors">
                                    Reset Filter
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Pagination Section -->
                @if (isset($songs) && $songs->count() > 0)
                    <div class="mt-40 mb-20 flex justify-center">
                        <x-pagination :paginator="$songs" />
                    </div>
                @endif
            </div>
        @endif

        <!-- Reusable Modal Buat Lagu Baru -->
        <x-create-song-modal :artists="$artists ?? []" />
    </div>
</x-app-layout>

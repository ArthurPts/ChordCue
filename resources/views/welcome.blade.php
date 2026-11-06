<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 sm:px-24 py-24 sm:py-32 pb-[88px]">
        <!-- Hero Section: Clean & Focused Header -->
        <div class="text-center max-w-[560px] mx-auto mb-24">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                Temukan Lagu
            </h1>
            <p class="text-xs sm:text-sm text-ink-secondary mt-2">
                Pilih lagu dan mulai sesi latihan chord interaktifmu.
            </p>
        </div>

        <!-- Search & Filter Controls: Rhythmic & Compact -->
        <form method="GET" action="{{ route('home') }}" class="space-y-12">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Search Bar -->
            <div class="relative w-full">
                <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                    <x-icon name="search" class="w-4 h-4" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari judul lagu atau artis..."
                    class="w-full h-[44px] pl-[40px] pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                >
                @if (request('q'))
                    <a href="{{ route('home', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                        <x-icon name="x" class="w-3.5 h-3.5" />
                    </a>
                @endif
            </div>

            <!-- Dropdown Filters Row (4 Columns on Desktop, 2 on Tablet, Compact) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <!-- Filter Artis -->
                <div class="space-y-4">
                    <label class="block text-[11px] font-medium text-ink-tertiary">Artis</label>
                    <div class="relative">
                        <select
                            name="artist_id"
                            onchange="this.form.submit()"
                            class="w-full h-[38px] bg-surface-2 border border-subtle rounded-lg px-12 pr-[34px] text-ink-secondary focus:text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Artis</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                    {{ $artist->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </div>

                <!-- Filter Genre -->
                <div class="space-y-4">
                    <label class="block text-[11px] font-medium text-ink-tertiary">Genre</label>
                    <div class="relative">
                        <select
                            name="genre"
                            onchange="this.form.submit()"
                            class="w-full h-[38px] bg-surface-2 border border-subtle rounded-lg px-12 pr-[34px] text-ink-secondary focus:text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Genre</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </div>

                <!-- Filter Mood -->
                <div class="space-y-4">
                    <label class="block text-[11px] font-medium text-ink-tertiary">Mood</label>
                    <div class="relative">
                        <select
                            name="mood"
                            onchange="this.form.submit()"
                            class="w-full h-[38px] bg-surface-2 border border-subtle rounded-lg px-12 pr-[34px] text-ink-secondary focus:text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Mood</option>
                            @foreach ($moods as $mood)
                                <option value="{{ $mood->id }}" {{ request('mood') == $mood->id ? 'selected' : '' }}>
                                    {{ $mood->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </div>

                <!-- Filter Lokasi -->
                <div class="space-y-4">
                    <label class="block text-[11px] font-medium text-ink-tertiary">Lokasi</label>
                    <div class="relative">
                        <select
                            name="location"
                            onchange="this.form.submit()"
                            class="w-full h-[38px] bg-surface-2 border border-subtle rounded-lg px-12 pr-[34px] text-ink-secondary focus:text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Lokasi</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('location') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Song Cards Grid: Balanced Spacing & Proportions -->
        <div class="mt-24">
            @if (isset($songs) && $songs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-16 md:gap-20">
                    @foreach ($songs as $song)
                        <x-song-card :song="$song" />
                    @endforeach
                </div>
            @elseif (request()->hasAny(['q', 'artist_id', 'genre', 'mood', 'location']))
                <div class="bg-surface-1 border border-subtle rounded-lg p-32 text-center max-w-[460px] mx-auto">
                    <x-icon name="music" class="w-10 h-10 text-ink-tertiary mx-auto mb-12" />
                    <h3 class="text-base font-bold text-ink">Tidak Ada Lagu Ditemukan</h3>
                    <p class="text-xs sm:text-sm text-ink-secondary mt-4">
                        Coba gunakan kata kunci pencarian yang lain atau atur ulang filter yang dipilih.
                    </p>
                    <div class="mt-16">
                        <a href="{{ route('home') }}" class="h-[38px] px-18 rounded-lg bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs font-semibold inline-flex items-center justify-center transition-colors">
                            Atur Ulang Filter
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-16 md:gap-20">
                    @for ($i = 0; $i < 6; $i++)
                        <x-song-card />
                    @endfor
                </div>
            @endif
        </div>

        <!-- Pagination Section -->
        <div class="mt-28 mb-12 flex justify-center">
            <x-pagination :paginator="$songs ?? null" />
        </div>
    </div>
</x-app-layout>

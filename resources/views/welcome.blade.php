<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 md:px-24 py-36">
        <!-- Hero Section -->
        <div class="text-center max-w-[680px] mx-auto">
            <h1 class="text-3xl md:text-4xl font-semibold text-ink tracking-tight">
                Temukan Lagu
            </h1>
            <p class="text-base text-ink-secondary mt-8">
                Cari lagu favoritmu dan mulai berlatih sekarang.
            </p>
        </div>

        <!-- Search & Filter Controls -->
        <form method="GET" action="{{ route('home') }}" class="mt-32 space-y-16">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Search Bar -->
            <div class="relative w-full">
                <button type="submit" class="absolute inset-y-0 left-0 pl-16 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                    <x-icon name="search" class="w-5 h-5" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari judul lagu atau artis..."
                    class="w-full h-[48px] pl-[46px] pr-[40px] bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                >
                @if (request('q'))
                    <a href="{{ route('home', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-16 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                        <x-icon name="x" class="w-4 h-4" />
                    </a>
                @endif
            </div>

            <!-- Dropdown Filters Row (4 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-16">
                <!-- Filter Artis -->
                <div class="space-y-6">
                    <label class="block text-xs font-medium text-ink-secondary">Artis</label>
                    <div class="relative">
                        <select
                            name="artist_id"
                            onchange="this.form.submit()"
                            class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[38px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Artis</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                    {{ $artist->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-14 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <!-- Filter Genre -->
                <div class="space-y-6">
                    <label class="block text-xs font-medium text-ink-secondary">Genre</label>
                    <div class="relative">
                        <select
                            name="genre"
                            onchange="this.form.submit()"
                            class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[38px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Genre</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-14 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <!-- Filter Mood -->
                <div class="space-y-6">
                    <label class="block text-xs font-medium text-ink-secondary">Mood</label>
                    <div class="relative">
                        <select
                            name="mood"
                            onchange="this.form.submit()"
                            class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[38px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Mood</option>
                            @foreach ($moods as $mood)
                                <option value="{{ $mood->id }}" {{ request('mood') == $mood->id ? 'selected' : '' }}>
                                    {{ $mood->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-14 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <!-- Filter Lokasi -->
                <div class="space-y-6">
                    <label class="block text-xs font-medium text-ink-secondary">Lokasi</label>
                    <div class="relative">
                        <select
                            name="location"
                            onchange="this.form.submit()"
                            class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[38px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                        >
                            <option value="">Semua Lokasi</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('location') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-14 flex items-center pointer-events-none text-ink-secondary">
                            <x-icon name="chevron-down" class="w-4 h-4" />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Song Cards Grid -->
        <div class="mt-36">
            @if (isset($songs) && $songs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-24">
                    @foreach ($songs as $song)
                        <x-song-card :song="$song" />
                    @endforeach
                </div>
            @elseif (request()->hasAny(['q', 'artist_id', 'genre', 'mood', 'location']))
                <div class="bg-surface-1 border border-subtle rounded-2xl p-40 text-center max-w-[480px] mx-auto">
                    <x-icon name="music" class="w-12 h-12 text-ink-tertiary mx-auto mb-16" />
                    <h3 class="text-lg font-medium text-ink">Tidak Ada Lagu Ditemukan</h3>
                    <p class="text-sm text-ink-secondary mt-6">
                        Coba gunakan kata kunci pencarian yang lain atau reset filter yang dipilih.
                    </p>
                    <div class="mt-20">
                        <a href="{{ route('home') }}" class="h-[40px] px-20 rounded-lg bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-sm font-medium inline-flex items-center justify-center transition-colors">
                            Reset Filter
                        </a>
                    </div>
                </div>
            @else
                {{-- Fallback Tampilan 6 Kartu Contoh Sesuai Mockup Desain --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-24">
                    @for ($i = 0; $i < 6; $i++)
                        <x-song-card />
                    @endfor
                </div>
            @endif
        </div>

        <!-- Pagination Section -->
        <div class="mt-40 mb-20 flex justify-center">
            <x-pagination :paginator="$songs ?? null" />
        </div>
    </div>
</x-app-layout>

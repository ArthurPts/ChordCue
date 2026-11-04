<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 md:px-24 py-36">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-semibold text-ink tracking-tight">
                Kelola Lagu
            </h1>
            <p class="text-base text-ink-secondary mt-4">
                Kelola semua lagu yang tersedia di aplikasi ChordCue.
            </p>
        </div>

        <!-- Session Status Flash Alert -->
        @if (session('status'))
            <div class="mt-20 p-16 rounded-xl bg-accent/15 border border-accent/30 text-accent text-sm flex items-center gap-10">
                <x-icon name="check" class="w-5 h-5 shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Filter Controls (Cari Lagu, Visibility, Pemilik) -->
        <form method="GET" action="{{ route('admin.songs.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-16 mt-24">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Cari Lagu (6 cols) -->
            <div class="sm:col-span-6 space-y-6">
                <label class="block text-xs font-medium text-ink-secondary">Cari Lagu</label>
                <div class="relative">
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
                        <a href="{{ route('admin.songs.index', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian judul">
                            <x-icon name="x" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </div>

            <!-- Visibility Dropdown (3 cols) -->
            <div class="sm:col-span-3 space-y-6">
                <label class="block text-xs font-medium text-ink-secondary">Visibility</label>
                <div class="relative">
                    <select
                        name="visibility"
                        onchange="this.form.submit()"
                        class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[36px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                    >
                        <option value="all" {{ ($visibility ?? 'all') === 'all' ? 'selected' : '' }}>Semua (Public & Private)</option>
                        <option value="public" {{ ($visibility ?? 'all') === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ ($visibility ?? 'all') === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                        <x-icon name="chevron-down" class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Pemilik (3 cols) -->
            <div class="sm:col-span-3 space-y-6">
                <label class="block text-xs font-medium text-ink-secondary">Pemilik</label>
                <div class="relative">
                    <input
                        type="text"
                        name="owner"
                        value="{{ request('owner') }}"
                        placeholder="Cari username"
                        class="w-full h-[44px] px-14 pr-[36px] bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                    >
                    @if (request('owner'))
                        <a href="{{ route('admin.songs.index', request()->except('owner', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian pemilik">
                            <x-icon name="x" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Stat Cards Row (2 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-16 md:gap-24 mt-24">
            <!-- Card 1: Total Lagu -->
            <div class="bg-surface-1 border border-subtle rounded-2xl p-20 flex items-center gap-16">
                <div class="w-[52px] h-[52px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="music" class="w-6 h-6" />
                </div>
                <div>
                    <span class="text-sm font-medium text-ink-secondary">Total Lagu</span>
                    <div class="mt-4 flex items-center gap-10">
                        <span class="text-xl font-semibold text-ink">{{ $totalSongs }}</span>
                        <div class="h-[6px] w-[60px] rounded-full bg-accent/60"></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Public & Private Stats -->
            <div class="bg-surface-1 border border-subtle rounded-2xl p-20 flex items-center justify-around gap-16">
                <!-- Public Sub-stat -->
                <div class="flex items-center gap-12">
                    <div class="w-[44px] h-[44px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                        <x-icon name="world" class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-sm font-medium text-ink-secondary">Public</span>
                        <div class="mt-2 flex items-center gap-8">
                            <span class="text-lg font-semibold text-ink">{{ $publicSongsCount }}</span>
                            <div class="h-[5px] w-[44px] rounded-full bg-accent/60"></div>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="h-10 w-[1px] bg-subtle"></div>

                <!-- Private Sub-stat -->
                <div class="flex items-center gap-12">
                    <div class="w-[44px] h-[44px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                        <x-icon name="lock" class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-sm font-medium text-ink-secondary">Private</span>
                        <div class="mt-2 flex items-center gap-8">
                            <span class="text-lg font-semibold text-ink">{{ $privateSongsCount }}</span>
                            <div class="h-[5px] w-[44px] rounded-full bg-accent/60"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Songs Data Table Container -->
        <div class="mt-24 bg-surface-1 border border-subtle rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-subtle bg-surface-2/40 text-ink-secondary text-xs font-medium">
                            <th scope="col" class="py-16 px-20">Judul Lagu</th>
                            <th scope="col" class="py-16 px-20">Artis</th>
                            <th scope="col" class="py-16 px-20">Pemilik</th>
                            <th scope="col" class="py-16 px-20">BPM</th>
                            <th scope="col" class="py-16 px-20">Time Signature</th>
                            <th scope="col" class="py-16 px-20">Visibility</th>
                            <th scope="col" class="py-16 px-20 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-subtle/40">
                        @forelse ($songs as $song)
                            <tr class="hover:bg-surface-2/30 transition-colors">
                                <!-- Judul Lagu -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm font-medium text-ink">
                                    {{ $song->title }}
                                </td>

                                <!-- Artis -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $song->artist?->name ?? 'Artis Tidak Diketahui' }}
                                </td>

                                <!-- Pemilik -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $song->user?->username ?? 'Tidak Diketahui' }}
                                </td>

                                <!-- BPM -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $song->bpm }}
                                </td>

                                <!-- Time Signature -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $song->time_signature_numerator }}/{{ $song->time_signature_denominator }}
                                </td>

                                <!-- Visibility Badge -->
                                <td class="py-16 px-20 whitespace-nowrap">
                                    <span class="inline-flex items-center px-12 py-3 rounded-full text-xs font-medium border border-subtle bg-surface-2 text-ink-secondary capitalize">
                                        {{ $song->visibility }}
                                    </span>
                                </td>

                                <!-- Aksi (Putar, Edit, Hapus) -->
                                <td class="py-16 px-20 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-8">
                                        <!-- Putar -->
                                        <a href="{{ route('chords.index') }}" class="h-[32px] px-12 rounded-lg bg-accent hover:bg-accent-hover text-white text-xs font-medium inline-flex items-center gap-4 transition-colors">
                                            <x-icon name="play" class="w-3.5 h-3.5" />
                                            <span>Putar</span>
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('songs.edit', $song) }}" class="h-[32px] px-12 rounded-lg bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs font-medium inline-flex items-center justify-center transition-colors">
                                            <span>Edit</span>
                                        </a>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('songs.destroy', $song) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lagu ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-[32px] px-12 rounded-lg bg-[#DE6D62] hover:bg-[#D55F55] text-[#131C2E] text-xs font-medium inline-flex items-center justify-center transition-colors cursor-pointer">
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-40 text-center text-sm text-ink-secondary">
                                    Tidak ada lagu yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Section -->
        <div class="mt-40 mb-20 flex justify-center">
            <x-pagination :paginator="$songs" />
        </div>
    </div>
</x-app-layout>

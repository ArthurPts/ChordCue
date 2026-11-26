<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 md:px-24 py-24 sm:py-32 pb-[88px]">
        <!-- Header -->
        <div>
            <h1 class="text-display font-medium text-ink leading-heading">
                Kelola Lagu
            </h1>
            <p class="text-body text-ink-secondary mt-4">
                Kelola semua lagu yang tersedia di aplikasi ChordCue.
            </p>
        </div>

        <!-- Session Status Flash Alert -->
        @if (session('status'))
            <div role="status" class="mt-16 p-14 rounded-md bg-success-tint border border-success/30 text-success text-body flex items-center gap-10">
                <x-icon name="check" class="w-icon-control h-icon-control shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Stat Cards Row (3 Cards: Total Lagu, Public, Private) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-12 sm:gap-16 mt-20">
            <!-- Card 1: Total Lagu -->
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="music" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-caption font-medium text-ink-secondary">Total Lagu</span>
                    <div class="mt-4 flex items-baseline gap-10">
                        <span class="text-display font-medium font-mono text-ink leading-heading">{{ $totalSongs }}</span>
                        <span class="text-caption text-ink-tertiary">Katalog sistem</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Public -->
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="world" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-caption font-medium text-ink-secondary">Public</span>
                    <div class="mt-4 flex items-baseline gap-10">
                        <span class="text-display font-medium font-mono text-ink leading-heading">{{ $publicSongsCount }}</span>
                        <span class="text-caption text-ink-tertiary">Akses publik</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Private -->
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="lock" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-caption font-medium text-ink-secondary">Private</span>
                    <div class="mt-4 flex items-baseline gap-10">
                        <span class="text-display font-medium font-mono text-ink leading-heading">{{ $privateSongsCount }}</span>
                        <span class="text-caption text-ink-tertiary">Akses pemilik</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls Toolbar (Cari Lagu, Visibility, Pemilik) -->
        <form method="GET" action="{{ route('admin.songs.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-12 mt-20 p-14 sm:p-16 rounded-lg bg-surface-1 border border-subtle">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Cari Lagu (6 cols) -->
            <div class="sm:col-span-6 space-y-4">
                <label class="block text-caption font-medium text-ink-secondary">Cari Lagu</label>
                <div class="relative">
                    <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                        <x-icon name="search" class="w-icon-control h-icon-control" />
                    </button>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari judul lagu atau artis..."
                        class="w-full h-[40px] pl-[38px] pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                    >
                    @if (request('q'))
                        <a href="{{ route('admin.songs.index', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian judul">
                            <x-icon name="x" class="w-icon-chip h-icon-chip" />
                        </a>
                    @endif
                </div>
            </div>

            <!-- Visibility Dropdown (3 cols) -->
            <div class="sm:col-span-3 space-y-4">
                <label class="block text-caption font-medium text-ink-secondary">Visibility</label>
                <div class="relative">
                    <select
                        name="visibility"
                        onchange="this.form.submit()"
                        class="w-full h-[40px] bg-surface-2 border border-subtle rounded-sm px-14 pr-[36px] text-ink text-body appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                    >
                        <option value="all" {{ ($visibility ?? 'all') === 'all' ? 'selected' : '' }}>Semua (Public &amp; Private)</option>
                        <option value="public" {{ ($visibility ?? 'all') === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ ($visibility ?? 'all') === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                        <x-icon name="chevron-down" class="w-icon-control h-icon-control" />
                    </div>
                </div>
            </div>

            <!-- Pemilik (3 cols) -->
            <div class="sm:col-span-3 space-y-4">
                <label class="block text-caption font-medium text-ink-secondary">Pemilik</label>
                <div class="relative">
                    <input
                        type="text"
                        name="owner"
                        value="{{ request('owner') }}"
                        placeholder="Cari username..."
                        class="w-full h-[40px] px-14 pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                    >
                    @if (request('owner'))
                        <a href="{{ route('admin.songs.index', request()->except('owner', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian pemilik">
                            <x-icon name="x" class="w-icon-chip h-icon-chip" />
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Songs Data Table Container -->
        <div class="mt-20 bg-surface-1 border border-subtle rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-subtle bg-surface-2/60 text-ink-secondary text-caption font-medium">
                            <th scope="col" class="py-12 px-16 sm:px-20">Judul Lagu</th>
                            <th scope="col" class="py-12 px-16 sm:px-20">Artis</th>
                            <th scope="col" class="py-12 px-16 sm:px-20">Pemilik</th>
                            <th scope="col" class="py-12 px-16 sm:px-20 font-mono">BPM</th>
                            <th scope="col" class="py-12 px-16 sm:px-20 font-mono">Time Signature</th>
                            <th scope="col" class="py-12 px-16 sm:px-20">Visibility</th>
                            <th scope="col" class="py-12 px-16 sm:px-20 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-subtle/50">
                        @forelse ($songs as $song)
                            <tr class="hover:bg-surface-2/40 transition-colors">
                                <!-- Judul Lagu -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body font-medium text-ink">
                                    {{ $song->title }}
                                </td>

                                <!-- Artis -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body text-ink-secondary">
                                    {{ $song->artist?->name ?? 'Artis Tidak Diketahui' }}
                                </td>

                                <!-- Pemilik -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body text-ink-secondary">
                                    <div class="flex items-center gap-6">
                                        <x-icon name="user" class="w-icon-chip h-icon-chip text-ink-tertiary" />
                                        <span>{{ $song->user?->username ?? 'Tidak Diketahui' }}</span>
                                    </div>
                                </td>

                                <!-- BPM -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body font-mono text-ink-secondary">
                                    {{ $song->bpm }}
                                </td>

                                <!-- Time Signature -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body font-mono text-ink-secondary">
                                    {{ $song->time_signature_numerator }}/{{ $song->time_signature_denominator }}
                                </td>

                                <!-- Visibility Badge -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                    @if ($song->visibility === 'public')
                                        <span class="inline-flex items-center gap-4 px-10 py-4 rounded-full text-caption font-medium border border-accent/40 bg-accent-soft-bg text-accent-soft-text">
                                            <x-icon name="world" class="w-icon-chip h-icon-chip" />
                                            <span>Public</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-4 px-10 py-4 rounded-full text-caption font-medium border border-subtle bg-surface-2 text-ink-secondary">
                                            <x-icon name="lock" class="w-icon-chip h-icon-chip" />
                                            <span>Private</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi (Putar, Edit, Hapus) -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-8">
                                        <!-- Putar -->
                                        <a href="{{ route('songs.show', $song) }}" class="h-[32px] px-12 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-caption font-medium inline-flex items-center gap-4 transition-colors">
                                            <x-icon name="play" class="w-icon-chip h-icon-chip" />
                                            <span>Putar</span>
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('songs.editor.simple', $song) }}" class="h-[32px] px-12 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center justify-center transition-colors">
                                            <span>Edit</span>
                                        </a>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('songs.destroy', $song) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lagu ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-[32px] px-12 rounded-sm bg-error-tint/70 hover:bg-error-tint border border-error/30 text-error text-caption font-medium inline-flex items-center justify-center transition-colors cursor-pointer">
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-32 text-center text-body text-ink-secondary">
                                    Tidak ada lagu yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fixed Pagination Floating Footer -->
        <x-pagination :paginator="$songs" />
    </div>
</x-app-layout>

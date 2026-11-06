<x-app-layout>
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
        $initialChordJson = $selectedChord ? [
            'id' => $selectedChord->id,
            'name' => $selectedChord->name,
            'pronunciation' => $selectedChord->pronunciation,
            'diagram_url' => $selectedChord->diagram_url,
            'fret_data' => $selectedChord->fret_data,
        ] : null;
    @endphp

    <div
        x-data="{
            showCreateModal: false,
            showEditModal: false,
            selectedChord: @js($initialChordJson),
            selectChord(chord) {
                this.selectedChord = chord;
            }
        }"
        class="min-h-screen py-24 sm:py-32 pb-[88px]"
    >
        <div class="max-w-content mx-auto px-16 sm:px-24">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-20 p-12 bg-success/15 border border-success/30 rounded-md text-success text-xs sm:text-sm flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-20 p-12 bg-error-tint/80 border border-error/40 text-error text-xs sm:text-sm flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-20 p-12 bg-error-tint/80 border border-error/40 text-error text-xs sm:text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Page Header --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-16 mb-24 md:mb-32">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-ink">
                        Pustaka Chord
                    </h1>
                    <p class="text-xs sm:text-sm text-ink-secondary mt-1">
                        Jelajahi bentuk chord dan pelajari posisi jari pada gitar.
                    </p>
                </div>

                {{-- Header Actions: Search & (Admin) + Tambah Chord --}}
                <div class="flex items-center gap-12 w-full md:w-auto">
                    {{-- Search Form --}}
                    <form method="GET" action="{{ route('chords.index') }}" class="relative flex-1 md:w-[260px]">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-14 flex items-center pointer-events-none text-ink-secondary">
                                <x-icon name="search" class="w-4 h-4" />
                            </div>
                            <input
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Cari Chord..."
                                class="w-full h-[40px] pl-[38px] pr-[34px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary text-xs sm:text-sm focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition-colors"
                            >
                            @if (request('q'))
                                <a
                                    href="{{ route('chords.index') }}"
                                    class="absolute inset-y-0 right-0 pr-12 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer"
                                    title="Hapus pencarian"
                                >
                                    <x-icon name="x" class="w-3.5 h-3.5" />
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="hidden" aria-hidden="true"></button>
                    </form>

                    {{-- Admin Mode: + Tambah Chord Button --}}
                    @if ($isAdmin)
                        <button
                            type="button"
                            @click="showCreateModal = true"
                            class="h-[40px] px-16 bg-accent hover:bg-accent-hover text-[#080C14] rounded-sm text-xs sm:text-sm font-bold flex items-center gap-6 shrink-0 transition-all shadow-sm cursor-pointer whitespace-nowrap"
                        >
                            <x-icon name="plus" class="w-4 h-4" />
                            <span>Tambah Chord</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Main Layout: 2 Columns with Subtle Divider --}}
            <div class="flex flex-col lg:flex-row gap-24 lg:gap-32 items-start">
                {{-- Left Column: Chord Cards Grid (25 Chords) --}}
                <div class="w-full lg:w-[62%] xl:w-[68%]">
                    @if ($chords->count() > 0)
                        {{-- 5-Column Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-10 sm:gap-12">
                            @foreach ($chords as $chord)
                                <x-chord-card
                                    :chord="$chord"
                                    :selected="$selectedChord && $selectedChord->id === $chord->id"
                                />
                            @endforeach
                        </div>

                        {{-- Pagination (Rendered as Fixed Footer) --}}
                        <x-pagination :paginator="$chords" />
                    @else
                        {{-- Empty State --}}
                        <div class="p-32 bg-surface-1 border border-subtle rounded-lg text-center max-w-[460px] mx-auto">
                            <div class="w-10 h-10 rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent mx-auto mb-14">
                                <x-icon name="search" class="w-5 h-5" />
                            </div>
                            <h3 class="text-base font-bold text-ink">Chord Tidak Ditemukan</h3>
                            <p class="text-xs sm:text-sm text-ink-secondary mt-4 max-w-sm mx-auto">
                                Tidak ada chord yang cocok dengan kata kunci pencarian "{{ request('q') }}".
                            </p>
                            @if (request('q'))
                                <a
                                    href="{{ route('chords.index') }}"
                                    class="inline-block mt-16 px-16 h-[36px] leading-[36px] rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs font-semibold transition-colors cursor-pointer"
                                >
                                    Reset Pencarian
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Vertical Divider Line --}}
                <div class="hidden lg:block w-[1px] bg-subtle self-stretch"></div>

                {{-- Right Column: Selected Chord Preview Card (Sticky di Desktop - Tidak ikut scroll) --}}
                <div class="w-full lg:w-[38%] xl:w-[32%] lg:sticky lg:top-[88px] self-start">
                    <div class="bg-surface-1 border border-subtle rounded-lg p-20 sm:p-24 shadow-sm">
                        {{-- Top Header of Preview Card (Nama Chord & Pelafalan) --}}
                        <div class="flex items-center justify-between gap-12 mb-16">
                            <div>
                                <h2
                                    class="text-[36px] sm:text-[44px] font-extrabold text-ink tracking-tight font-mono leading-none"
                                    x-text="selectedChord ? selectedChord.name : '—'"
                                >
                                    {{ $selectedChord?->name ?? '—' }}
                                </h2>
                                <p
                                    class="text-xs sm:text-sm text-ink-secondary mt-4 font-normal"
                                    x-show="selectedChord && selectedChord.pronunciation"
                                    x-text="selectedChord ? selectedChord.pronunciation : ''"
                                >
                                    {{ $selectedChord?->pronunciation }}
                                </p>
                            </div>

                            {{-- Admin Edit Button --}}
                            @if ($isAdmin)
                                <template x-if="selectedChord">
                                    <button
                                        type="button"
                                        @click="showEditModal = true"
                                        class="h-[36px] px-16 bg-surface-2 hover:bg-surface-3 border border-subtle text-ink rounded-sm text-xs font-semibold transition-colors flex items-center justify-center shrink-0 cursor-pointer shadow-sm"
                                    >
                                        Edit
                                    </button>
                                </template>
                            @endif
                        </div>

                        {{-- Big Diagram Container (Dark Studio Surface) --}}
                        <div class="bg-surface-2 border border-subtle/80 rounded-md p-16 sm:p-20 flex flex-col items-center justify-center min-h-[260px] sm:min-h-[300px] overflow-hidden">
                            <template x-if="selectedChord">
                                <div class="w-full flex flex-col items-center justify-center">
                                    <img
                                        :src="selectedChord.diagram_url || '{{ asset('storage/diagrams/No_Image_Available.jpg') }}'"
                                        :alt="selectedChord.name"
                                        class="max-h-[240px] max-w-full object-contain opacity-90"
                                    >
                                </div>
                            </template>

                            {{-- Fallback when no chord is selected --}}
                            <template x-if="!selectedChord">
                                <div class="text-center text-ink-secondary text-xs sm:text-sm p-16">
                                    <x-icon name="music" class="w-8 h-8 text-ink-tertiary mx-auto mb-8" />
                                    <span>Pilih chord untuk melihat diagram</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Admin Modals --}}
        @if ($isAdmin)
            <x-create-chord-modal />
            <x-edit-chord-modal />
        @endif
    </div>
</x-app-layout>

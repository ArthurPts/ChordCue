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
        class="min-h-screen py-24 md:py-32"
    >
        <div class="max-w-content mx-auto px-16 sm:px-24">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-20 p-14 bg-success/15 border border-success/30 rounded-xl text-success text-sm flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-20 p-14 bg-danger/15 border border-danger/30 rounded-xl text-danger text-sm flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-20 p-14 bg-danger/15 border border-danger/30 rounded-xl text-danger text-sm">
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
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-ink">
                        Pustaka Chord
                    </h1>
                    <p class="text-sm text-ink-secondary mt-6">
                        Jelajahi bentuk chord dan pelajari posisi jari pada gitar.
                    </p>
                </div>

                {{-- Header Actions: Search & (Admin) + Tambah Chord --}}
                <div class="flex items-center gap-12 w-full md:w-auto">
                    {{-- Search Form --}}
                    <form method="GET" action="{{ route('chords.index') }}" class="relative flex-1 md:flex-initial">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-14 flex items-center pointer-events-none text-ink-secondary">
                                <x-icon name="search" class="w-4 h-4" />
                            </div>
                            <input
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Cari Chord..."
                                class="w-full md:w-[280px] h-[44px] pl-40 pr-36 bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary text-sm focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition-colors"
                            >
                            @if (request('q'))
                                <a
                                    href="{{ route('chords.index') }}"
                                    class="absolute inset-y-0 right-0 pr-12 flex items-center text-ink-secondary hover:text-ink"
                                    title="Hapus filter"
                                >
                                    <x-icon name="x" class="w-4 h-4" />
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
                            class="h-[44px] px-18 bg-[#5B8DF6] hover:bg-[#4C7EE7] text-white rounded-xl text-sm font-medium flex items-center gap-8 shrink-0 transition-colors shadow-button"
                        >
                            <x-icon name="plus" class="w-4 h-4" />
                            <span>Tambah Chord</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Main Layout: 2 Columns with Vertical Divider --}}
            <div class="flex flex-col lg:flex-row gap-24 lg:gap-32 items-start">
                {{-- Left Column: Chord Cards Grid & Pagination --}}
                <div class="w-full lg:w-[62%] xl:w-[65%] flex flex-col justify-between">
                    @if ($chords->count() > 0)
                        {{-- 4-Column Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-12 sm:gap-16">
                            @foreach ($chords as $chord)
                                @php
                                    $chordPayload = [
                                        'id' => $chord->id,
                                        'name' => $chord->name,
                                        'pronunciation' => $chord->pronunciation,
                                        'diagram_url' => $chord->diagram_url,
                                        'fret_data' => $chord->fret_data,
                                    ];
                                @endphp
                                <x-chord-card
                                    :chord="$chord"
                                    :selected="$selectedChord && $selectedChord->id === $chord->id"
                                    ::class="selectedChord && selectedChord.id === {{ $chord->id }} ? 'border-accent ring-2 ring-accent/30' : ''"
                                    @click="selectChord(@js($chordPayload))"
                                />
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-28">
                            <x-pagination :paginator="$chords" />
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="p-36 bg-surface-1 border border-subtle rounded-2xl text-center">
                            <div class="w-12 h-12 rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent mx-auto mb-16">
                                <x-icon name="search" class="w-6 h-6" />
                            </div>
                            <h3 class="text-base font-medium text-ink">Chord Tidak Ditemukan</h3>
                            <p class="text-sm text-ink-secondary mt-6 max-w-sm mx-auto">
                                Tidak ada chord yang cocok dengan kata kunci pencarian "{{ request('q') }}".
                            </p>
                            @if (request('q'))
                                <a
                                    href="{{ route('chords.index') }}"
                                    class="inline-block mt-16 px-16 py-8 rounded-xl bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-xs font-medium transition-colors"
                                >
                                    Reset Pencarian
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Vertical Divider Line --}}
                <div class="hidden lg:block w-[1px] bg-subtle self-stretch"></div>

                {{-- Right Column: Selected Chord Preview Card (Static / Tetap di Tempat) --}}
                <div class="w-full lg:w-[38%] xl:w-[35%]">
                    <div class="bg-surface-1 border border-subtle rounded-2xl p-20 sm:p-24 shadow-card">
                        {{-- Top Header of Preview Card (Cukup Nama Chord Saja) --}}
                        <div class="flex items-center justify-between gap-12 mb-20">
                            <div>
                                <h2
                                    class="text-4xl sm:text-5xl font-bold text-ink tracking-tight"
                                    x-text="selectedChord ? selectedChord.name : '—'"
                                >
                                    {{ $selectedChord?->name ?? '—' }}
                                </h2>
                                <p
                                    class="text-xs text-ink-secondary mt-4 font-normal"
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
                                        class="px-20 h-[38px] bg-[#5B8DF6] hover:bg-[#4C7EE7] text-white rounded-xl text-sm font-medium transition-colors shadow-button flex items-center justify-center shrink-0"
                                    >
                                        Edit
                                    </button>
                                </template>
                            @endif
                        </div>

                        {{-- Big Diagram Container (Periwinkle / Light Blue) --}}
                        <div class="bg-[#9DB9FB] rounded-2xl p-20 sm:p-28 flex items-center justify-center min-h-[300px] sm:min-h-[340px] overflow-hidden">
                            <template x-if="selectedChord">
                                <img
                                    :src="selectedChord.diagram_url || '{{ asset('storage/diagrams/No_Image_Available.jpg') }}'"
                                    :alt="selectedChord.name"
                                    class="max-h-[260px] max-w-full object-contain rounded-xl"
                                >
                            </template>

                            {{-- Fallback when no chord is selected --}}
                            <template x-if="!selectedChord">
                                <div class="text-center text-ink-secondary text-sm">
                                    Pilih chord untuk melihat diagram
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
